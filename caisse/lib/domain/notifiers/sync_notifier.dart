import 'dart:async';
import 'dart:io';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/constants/api_constants.dart';
import '../../core/services/sync_queue_service.dart';
import '../../data/models/transaction_model.dart';
import '../../data/repositories/cashier_repository.dart';
import '../repositories/i_cashier_repository.dart';
import 'cashier_notifier.dart';

class SyncState {
  final bool isSyncing;
  final bool isOnline;
  final int pendingCount;
  final DateTime? lastSyncedAt;
  final int lastSyncSuccessCount;
  final String? errorMessage;

  const SyncState({
    this.isSyncing = false,
    this.isOnline = true,
    this.pendingCount = 0,
    this.lastSyncedAt,
    this.lastSyncSuccessCount = 0,
    this.errorMessage,
  });

  SyncState copyWith({
    bool? isSyncing,
    bool? isOnline,
    int? pendingCount,
    DateTime? lastSyncedAt,
    int? lastSyncSuccessCount,
    String? errorMessage,
    bool clearError = false,
  }) {
    return SyncState(
      isSyncing: isSyncing ?? this.isSyncing,
      isOnline: isOnline ?? this.isOnline,
      pendingCount: pendingCount ?? this.pendingCount,
      lastSyncedAt: lastSyncedAt ?? this.lastSyncedAt,
      lastSyncSuccessCount: lastSyncSuccessCount ?? this.lastSyncSuccessCount,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}

class SyncNotifier extends StateNotifier<SyncState> {
  final ICashierRepository _repository;
  final Ref _ref;
  Timer? _autoSyncTimer;

  SyncNotifier(this._repository, this._ref) : super(const SyncState()) {
    _init();
  }

  Future<void> _init() async {
    final count = await SyncQueueService.getPendingCount();
    final lastSync = await SyncQueueService.getLastSyncTime();
    state = state.copyWith(pendingCount: count, lastSyncedAt: lastSync);

    // Vérification initiale de connectivité réelle
    await checkConnectivity();

    // Auto-sync heartbeat toutes les 15 secondes
    _autoSyncTimer = Timer.periodic(const Duration(seconds: 15), (_) async {
      final online = await checkConnectivity();
      if (online && state.pendingCount > 0) {
        await syncNow();
      }
    });
  }

  @override
  void dispose() {
    _autoSyncTimer?.cancel();
    super.dispose();
  }

  /// Vérifier l'état réel de la connectivité réseau
  Future<bool> checkConnectivity() async {
    try {
      final host = Uri.tryParse(ApiConstants.baseUrl)?.host ?? 'www.fsdyayra.be';
      final result = await InternetAddress.lookup(host)
          .timeout(const Duration(seconds: 4));
      final online = result.isNotEmpty && result[0].rawAddress.isNotEmpty;
      state = state.copyWith(isOnline: online);
      return online;
    } catch (_) {
      try {
        final result = await InternetAddress.lookup('google.com')
            .timeout(const Duration(seconds: 3));
        final online = result.isNotEmpty && result[0].rawAddress.isNotEmpty;
        state = state.copyWith(isOnline: online);
        return online;
      } catch (_) {
        state = state.copyWith(isOnline: false);
        return false;
      }
    }
  }

  /// Rafraîchir le compteur de la file locale
  Future<void> refreshPendingCount() async {
    final count = await SyncQueueService.getPendingCount();
    state = state.copyWith(pendingCount: count);
  }

  /// Vider manuellement la file d'attente locale
  Future<void> clearQueue() async {
    await SyncQueueService.clearPendingQueue();
    await refreshPendingCount();
    try {
      final cashier = _ref.read(cashierNotifierProvider.notifier);
      await cashier.fetchDashboardStats();
      await cashier.fetchClients();
      await cashier.fetchOperations();
    } catch (_) {}
  }

  /// Enregistrer une opération hors-ligne et générer son reçu provisoire
  Future<TransactionModel> queueOfflineOperation({
    required String type,
    required int accountId,
    required double amount,
    String? clientName,
    String? accountNumber,
    int? loanId,
    String? description,
    String paymentMethod = 'cash',
  }) async {
    final timestamp = DateTime.now();
    final offlineId = 'OFF-${timestamp.millisecondsSinceEpoch}';
    final offlineRef = 'OFFLINE-${type.toUpperCase().substring(0, 3)}-${timestamp.day}${timestamp.month}-${timestamp.millisecondsSinceEpoch % 10000}';

    final payload = {
      'offline_id': offlineId,
      'transaction_type': type,
      'account_id': accountId,
      if (loanId != null) 'loan_id': loanId,
      'amount': amount,
      'payment_method': paymentMethod,
      'description': description ?? '$type enregistré hors-ligne',
      'offline_created_at': timestamp.toIso8601String(),
      'client_name': clientName,
      'account_number': accountNumber,
    };

    await SyncQueueService.addOfflineTransaction(payload);
    await refreshPendingCount();

    // Créer le modèle de transaction pour le reçu et la liste locale
    final tx = TransactionModel(
      id: -timestamp.millisecondsSinceEpoch,
      reference: offlineRef,
      transactionType: type,
      accountId: accountId,
      amount: amount,
      fee: 0,
      status: 'pending_sync',
      description: description ?? '$type (Enregistré Hors-Ligne)',
      createdAt: timestamp,
      clientName: clientName ?? 'Client Enregistré',
      accountNumber: accountNumber ?? 'ACC-LOCAL',
      agentName: 'Moi (Hors-Ligne)',
    );

    // Mettre à jour immédiatement l'état local du caissier et ajouter à la liste des opérations
    _ref.read(cashierNotifierProvider.notifier).recordLocalOfflineTransaction(
      type: type,
      amount: amount,
      accountId: accountId,
      loanId: loanId,
      transaction: tx,
    );

    // Tenter une synchronisation discrète si le réseau revient
    autoSyncIfPossible();

    return tx;
  }

  /// Synchronisation automatique discrète
  Future<void> autoSyncIfPossible() async {
    if (state.isSyncing) return;
    final count = await SyncQueueService.getPendingCount();
    if (count == 0) return;
    await syncNow();
  }

  /// Lancer manuellement ou automatiquement la synchronisation
  Future<bool> syncNow() async {
    if (state.isSyncing) return false;

    state = state.copyWith(isSyncing: true, clearError: true);

    final rawPending = await SyncQueueService.getPendingTransactions();
    if (rawPending.isEmpty) {
      state = state.copyWith(isSyncing: false, isOnline: true, pendingCount: 0, clearError: true);
      return true;
    }

    // 0. Tri strict par dépendances (Clients d'abord, puis Comptes, Prêts, et Transactions)
    final pending = List<Map<String, dynamic>>.from(rawPending);
    pending.sort((a, b) {
      int getWeight(Map<String, dynamic> item) {
        final act = item['action']?.toString() ?? item['transaction_type']?.toString() ?? '';
        final offId = (item['offline_id'] ?? item['id'])?.toString() ?? '';
        if (act == 'create_client' || offId.startsWith('CLIENT-')) return 1;
        if (act == 'create_account' || offId.startsWith('ACC-')) return 2;
        if (act == 'apply_loan' || offId.startsWith('OFF-LOAN-')) return 3;
        return 4;
      }
      return getWeight(a).compareTo(getWeight(b));
    });

    final Set<String> allSyncedIds = {};
    final List<String> syncErrors = [];
    final Map<dynamic, int> clientMap = {};
    final Map<dynamic, int> accountMap = {};

    // 1. TENTATIVE N°1 : Synchronisation par lot (Batch Sync Endpoint)
    try {
      final res = await _repository.syncTransactions(pending);
      List<dynamic>? rawSynced = res['synced_ids'] as List<dynamic>?;
      if (rawSynced == null && res['data'] is Map) {
        rawSynced = res['data']['synced_ids'] as List<dynamic>?;
      }
      if (rawSynced != null) {
        for (final item in rawSynced) {
          allSyncedIds.add(item.toString().trim());
        }
      }

      // Collecter les erreurs retournées par le serveur
      final serverErrors = res['errors'] ?? (res['data'] is Map ? res['data']['errors'] : null);
      if (serverErrors is List && serverErrors.isNotEmpty) {
        for (final err in serverErrors) {
          if (err is Map) {
            final errId = err['id'] ?? '';
            final errMsg = err['error'] ?? err['message'] ?? 'Erreur inconnue';
            syncErrors.add('$errId : $errMsg');
          } else if (err is String) {
            syncErrors.add(err);
          }
        }
      }
    } catch (e) {
      final msg = e.toString().replaceFirst("Exception: ", "").trim();
      syncErrors.add('Erreur lot : $msg');
    }

    // 2. TENTATIVE N°2 : Replay séquentiel pour tous les éléments non acquittés
    final remainingItems = pending.where((item) {
      final id = item['offline_id']?.toString() ?? item['id']?.toString();
      final tempCliId = item['temp_client_id']?.toString();
      final tempAccId = item['temp_account_id']?.toString();
      return (id != null && !allSyncedIds.contains(id)) &&
          (tempCliId == null || !allSyncedIds.contains(tempCliId)) &&
          (tempAccId == null || !allSyncedIds.contains(tempAccId));
    }).toList();

    for (final item in remainingItems) {
      final offlineId = item['offline_id']?.toString() ?? item['id']?.toString() ?? '';
      final action = item['action']?.toString() ?? item['transaction_type']?.toString() ?? 'deposit';

      try {
        // A. CRÉATION CLIENT
        if (action == 'create_client' || offlineId.startsWith('CLIENT-')) {
          final client = await _repository.registerExpressClient(
            firstName: item['first_name']?.toString() ?? 'Client',
            lastName: item['last_name']?.toString() ?? 'Hors-Ligne',
            phone: item['phone']?.toString() ?? '',
            email: item['email']?.toString(),
            address: item['address']?.toString(),
            gender: item['gender']?.toString(),
            idType: item['id_type']?.toString(),
            idNumber: item['id_number']?.toString(),
            initialTontineAmount: double.tryParse(item['initial_tontine_amount']?.toString() ?? '1000') ?? 1000.0,
            createTontine: item['create_tontine'] == true,
          );
          if (offlineId.isNotEmpty) clientMap[offlineId] = client.id;
          if (item['temp_client_id'] != null) clientMap[item['temp_client_id']] = client.id;
          if (item['id'] != null) clientMap[item['id']] = client.id;
          allSyncedIds.add(offlineId);
          continue;
        }

        // B. CRÉATION COMPTE
        if (action == 'create_account' || offlineId.startsWith('ACC-')) {
          final rawClientId = item['client_id'];
          int? realClientId = clientMap[rawClientId];
          if (realClientId == null && rawClientId != null) {
            final parsed = int.tryParse(rawClientId.toString());
            if (parsed != null && parsed > 0) realClientId = parsed;
          }

          if (realClientId != null) {
            final acc = await _repository.createAccount(
              clientId: realClientId,
              accountType: item['account_type']?.toString() ?? 'tontine',
              targetAmount: double.tryParse(item['target_amount']?.toString() ?? ''),
              cycleDurationMonths: int.tryParse(item['cycle_duration_months']?.toString() ?? ''),
              paymentFrequency: item['payment_frequency']?.toString(),
            );
            if (offlineId.isNotEmpty) accountMap[offlineId] = acc.id;
            if (item['temp_account_id'] != null) accountMap[item['temp_account_id']] = acc.id;
            if (item['account_number'] != null) accountMap[item['account_number']] = acc.id;
            allSyncedIds.add(offlineId);
          } else {
            syncErrors.add('Compte $offlineId : Client parent non trouvé');
          }
          continue;
        }

        // C. DEMANDE DE PRÊT
        if (action == 'apply_loan' || offlineId.startsWith('OFF-LOAN-')) {
          final rawClientId = item['client_id'];
          int? realClientId = clientMap[rawClientId];
          if (realClientId == null && rawClientId != null) {
            final parsed = int.tryParse(rawClientId.toString());
            if (parsed != null && parsed > 0) realClientId = parsed;
          }

          if (realClientId != null) {
            await _repository.applyLoan(
              clientId: realClientId,
              requestedAmount: double.tryParse(item['requested_amount']?.toString() ?? '0') ?? 0.0,
              durationMonths: int.tryParse(item['duration_months']?.toString() ?? '6') ?? 6,
              purpose: item['purpose']?.toString(),
              collateralDescription: item['collateral_description']?.toString(),
              interestRate: double.tryParse(item['interest_rate']?.toString() ?? ''),
            );
            allSyncedIds.add(offlineId);
          } else {
            syncErrors.add('Prêt $offlineId : Client parent non trouvé');
          }
          continue;
        }

        // D. REMBOURSEMENT DE PRÊT
        if (action == 'loan_repayment' || item['transaction_type'] == 'loan_repayment') {
          final loanId = int.tryParse(item['loan_id']?.toString() ?? '0') ?? 0;
          final amount = double.tryParse(item['amount']?.toString() ?? '0') ?? 0.0;
          if (loanId > 0 && amount > 0) {
            await _repository.repayLoan(
              loanId: loanId,
              amount: amount,
              paymentMethod: item['payment_method']?.toString(),
              notes: item['description']?.toString(),
            );
            allSyncedIds.add(offlineId);
          }
          continue;
        }

        // E. DÉPÔT / COTISATION TONTINE / ENCAISSEMENT
        if (action == 'deposit' || item['transaction_type'] == 'deposit') {
          final rawAccId = item['account_id'];
          int? realAccId = accountMap[rawAccId];
          if (realAccId == null && rawAccId != null) {
            final parsed = int.tryParse(rawAccId.toString());
            if (parsed != null && parsed > 0) realAccId = parsed;
          }

          if (realAccId == null && item['account_number'] != null) {
            final found = await _repository.searchAccount(item['account_number'].toString());
            if (found != null) realAccId = found.id;
          }

          final amount = double.tryParse(item['amount']?.toString() ?? '0') ?? 0.0;
          if (realAccId != null && amount > 0) {
            await _repository.processDeposit(
              accountId: realAccId,
              amount: amount,
              note: item['description']?.toString(),
            );
            allSyncedIds.add(offlineId);
          } else {
            syncErrors.add('Dépôt $offlineId : Compte introuvable (ID: $rawAccId, N°: ${item['account_number']})');
          }
          continue;
        }

        // F. RETRAIT / DÉCAISSEMENT
        if (action == 'withdrawal' || item['transaction_type'] == 'withdrawal') {
          final rawAccId = item['account_id'];
          int? realAccId = accountMap[rawAccId];
          if (realAccId == null && rawAccId != null) {
            final parsed = int.tryParse(rawAccId.toString());
            if (parsed != null && parsed > 0) realAccId = parsed;
          }

          if (realAccId == null && item['account_number'] != null) {
            final found = await _repository.searchAccount(item['account_number'].toString());
            if (found != null) realAccId = found.id;
          }

          final amount = double.tryParse(item['amount']?.toString() ?? '0') ?? 0.0;
          if (realAccId != null && amount > 0) {
            await _repository.processWithdrawal(
              accountId: realAccId,
              amount: amount,
              note: item['description']?.toString(),
            );
            allSyncedIds.add(offlineId);
          } else {
            syncErrors.add('Retrait $offlineId : Compte introuvable (ID: $rawAccId, N°: ${item['account_number']})');
          }
          continue;
        }
      } catch (e) {
        // EN CAS D'ERREUR : L'opération N'EST PAS acquittée pour ne jamais perdre de données locales
        syncErrors.add('Erreur opération $offlineId : ${e.toString().replaceFirst("Exception: ", "")}');
      }
    }

    // 3. Purger STRICTEMENT ET UNIQUEMENT les opérations synchronisées avec succès
    if (allSyncedIds.isNotEmpty) {
      await SyncQueueService.removeSyncedTransactions(allSyncedIds.toList());
    }

    final now = DateTime.now();
    await SyncQueueService.setLastSyncTime(now);
    final remainingCount = await SyncQueueService.getPendingCount();

    state = state.copyWith(
      isSyncing: false,
      isOnline: true,
      pendingCount: remainingCount,
      lastSyncedAt: now,
      lastSyncSuccessCount: allSyncedIds.length,
      errorMessage: syncErrors.isNotEmpty ? syncErrors.join('\n') : null,
      clearError: syncErrors.isEmpty,
    );

    // Rafraîchir l'ensemble des données
    try {
      final cashier = _ref.read(cashierNotifierProvider.notifier);
      await cashier.fetchDashboardStats();
      await cashier.fetchClients();
      await cashier.fetchOperations();
    } catch (_) {}

    return remainingCount == 0;
  }
}

final syncNotifierProvider = StateNotifierProvider<SyncNotifier, SyncState>((ref) {
  final repository = CashierRepository();
  return SyncNotifier(repository, ref);
});
