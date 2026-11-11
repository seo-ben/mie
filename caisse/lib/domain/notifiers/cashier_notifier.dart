import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/repository_providers.dart';
import '../../core/services/sync_queue_service.dart';
import '../../data/models/client_model.dart';
import '../../data/models/account_model.dart';
import '../../data/models/transaction_model.dart';
import '../../data/models/cashier_session_model.dart';
import '../entities/dashboard_stats.dart';
import '../repositories/i_cashier_repository.dart';

class CashierState {
  final DashboardStats stats;
  final List<ClientModel> clients;
  final List<TransactionModel> operations;
  final CashierSessionModel? currentSession;
  final List<Map<String, dynamic>> sessionHistory;
  final DateTime? selectedArchiveDate;
  final Map<String, dynamic>? selectedArchiveSession;
  final bool isLoading;
  final bool isSessionLoading;
  final String? errorMessage;

  const CashierState({
    this.stats = const DashboardStats(),
    this.clients = const [],
    this.operations = const [],
    this.currentSession,
    this.sessionHistory = const [],
    this.selectedArchiveDate,
    this.selectedArchiveSession,
    this.isLoading = false,
    this.isSessionLoading = false,
    this.errorMessage,
  });

  // Convenience getters for UI
  double get todayTotalIn => stats.todayTotalIn;
  double get todayTotalOut => stats.todayTotalOut;
  double get cashBalance => stats.cashBalance;
  double get openingBalance => currentSession?.openingBalance ?? stats.openingBalance;
  int get todayTxCount => stats.todayTxCount;
  int get agencyClientCount => stats.agencyClientCount;
  List<TransactionModel> get recentTransactions => stats.recentTransactions;
  bool get isSessionOpen => currentSession?.isOpen ?? false;
  bool get isArchiveMode => selectedArchiveDate != null || selectedArchiveSession != null;

  CashierState copyWith({
    DashboardStats? stats,
    List<ClientModel>? clients,
    List<TransactionModel>? operations,
    CashierSessionModel? currentSession,
    List<Map<String, dynamic>>? sessionHistory,
    DateTime? selectedArchiveDate,
    bool clearArchiveDate = false,
    Map<String, dynamic>? selectedArchiveSession,
    bool clearArchiveSession = false,
    bool? isLoading,
    bool? isSessionLoading,
    String? errorMessage,
    bool clearError = false,
  }) {
    return CashierState(
      stats: stats ?? this.stats,
      clients: clients ?? this.clients,
      operations: operations ?? this.operations,
      currentSession: currentSession ?? this.currentSession,
      sessionHistory: sessionHistory ?? this.sessionHistory,
      selectedArchiveDate: clearArchiveDate ? null : (selectedArchiveDate ?? this.selectedArchiveDate),
      selectedArchiveSession: clearArchiveSession ? null : (selectedArchiveSession ?? this.selectedArchiveSession),
      isLoading: isLoading ?? this.isLoading,
      isSessionLoading: isSessionLoading ?? this.isSessionLoading,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}

class CashierNotifier extends StateNotifier<CashierState> {
  final ICashierRepository _cashierRepository;

  CashierNotifier(this._cashierRepository) : super(const CashierState());

  Future<void> fetchDashboardStats({String? period, DateTime? date, int? sessionId}) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final targetDate = (period != null) ? date : (date ?? state.selectedArchiveDate);
      final targetSession = (period != null)
          ? sessionId
          : (sessionId ?? (state.selectedArchiveSession != null ? (state.selectedArchiveSession!['id'] as int?) : null));

      var stats = await _cashierRepository.getDashboardStats(
        period: period,
        date: targetDate,
        sessionId: targetSession,
      );
      final session = await _cashierRepository.getCurrentSession();

      // Fusionner les transactions hors-ligne en attente dans recentTransactions et recalculer les totaux
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();
      if (pendingModels.isNotEmpty) {
        final existingRefs = stats.recentTransactions.map((t) => t.reference).toSet();
        final pendingToAdd = pendingModels.where((p) => !existingRefs.contains(p.reference)).toList();
        if (pendingToAdd.isNotEmpty) {
          double pendingIn = 0;
          double pendingOut = 0;
          for (final p in pendingToAdd) {
            final type = p.type.toLowerCase();
            if (type.contains('depot') || type.contains('deposit') || type == 'credit' || type.contains('loan_repayment')) {
              pendingIn += p.amount;
            } else if (type.contains('retrait') || type.contains('withdraw') || type == 'debit') {
              pendingOut += p.amount;
            }
          }
          stats = stats.copyWith(
            todayTotalIn: stats.todayTotalIn + pendingIn,
            todayTotalOut: stats.todayTotalOut + pendingOut,
            cashBalance: stats.cashBalance + pendingIn - pendingOut,
            todayTxCount: stats.todayTxCount + pendingToAdd.length,
            recentTransactions: [...pendingToAdd, ...stats.recentTransactions],
          );
        }
      }

      state = state.copyWith(
        stats: stats,
        currentSession: session,
        isLoading: false,
        clearError: true,
      );

      // Mettre en cache local les statistiques et la session courante
      if (date == null && sessionId == null && (period == null || period == 'today')) {
        SyncQueueService.cacheDashboardStats(stats);
        if (session != null) {
          SyncQueueService.cacheCurrentSession(session);
        }
      }
    } catch (e) {
      // Fallback vers les statistiques et session en cache local hors-ligne
      var cachedStats = await SyncQueueService.getCachedDashboardStats();
      final cachedSession = await SyncQueueService.getCachedCurrentSession();
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();

      if (cachedStats != null || cachedSession != null || pendingModels.isNotEmpty) {
        if (cachedStats != null && pendingModels.isNotEmpty) {
          final existingRefs = cachedStats.recentTransactions.map((t) => t.reference).toSet();
          final pendingToAdd = pendingModels.where((p) => !existingRefs.contains(p.reference)).toList();
          if (pendingToAdd.isNotEmpty) {
            double pendingIn = 0;
            double pendingOut = 0;
            for (final p in pendingToAdd) {
              final type = p.type.toLowerCase();
              if (type.contains('depot') || type.contains('deposit') || type == 'credit' || type.contains('loan_repayment')) {
                pendingIn += p.amount;
              } else if (type.contains('retrait') || type.contains('withdraw') || type == 'debit') {
                pendingOut += p.amount;
              }
            }
            cachedStats = cachedStats.copyWith(
              todayTotalIn: cachedStats.todayTotalIn + pendingIn,
              todayTotalOut: cachedStats.todayTotalOut + pendingOut,
              cashBalance: cachedStats.cashBalance + pendingIn - pendingOut,
              todayTxCount: cachedStats.todayTxCount + pendingToAdd.length,
              recentTransactions: [...pendingToAdd, ...cachedStats.recentTransactions],
            );
          }
        }

        state = state.copyWith(
          stats: cachedStats ?? state.stats,
          currentSession: cachedSession ?? state.currentSession,
          isLoading: false,
          clearError: true,
        );
        return;
      }
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
    }
  }

  Future<List<Map<String, dynamic>>> fetchSessionHistory({String? status, DateTime? date}) async {
    try {
      final list = await _cashierRepository.getSessionHistory(status: status, date: date);
      state = state.copyWith(sessionHistory: list, clearError: true);
      return list;
    } catch (_) {
      return state.sessionHistory;
    }
  }

  Future<void> selectArchiveDate(DateTime date) async {
    state = state.copyWith(
      selectedArchiveDate: date,
      clearArchiveSession: true,
      isLoading: true,
    );
    await fetchDashboardStats(date: date);
    await fetchOperations(date: date);
  }

  Future<void> selectArchiveSession(Map<String, dynamic> session) async {
    final sessionId = session['id'] as int;
    final openedStr = session['opened_at'] as String?;
    final openedDate = openedStr != null ? DateTime.tryParse(openedStr) : null;

    state = state.copyWith(
      selectedArchiveSession: session,
      selectedArchiveDate: openedDate,
      isLoading: true,
    );
    await fetchDashboardStats(sessionId: sessionId, date: openedDate);
    await fetchOperations(sessionId: sessionId, date: openedDate);
  }

  Future<void> resetToLiveSession() async {
    state = state.copyWith(
      clearArchiveDate: true,
      clearArchiveSession: true,
      isLoading: true,
    );
    await fetchDashboardStats();
    await fetchOperations();
  }

  Future<CashierSessionModel?> fetchCurrentSession() async {
    state = state.copyWith(isSessionLoading: true);
    try {
      final session = await _cashierRepository.getCurrentSession();
      state = state.copyWith(currentSession: session, isSessionLoading: false, clearError: true);
      if (session != null) {
        SyncQueueService.cacheCurrentSession(session);
      }
      return session;
    } catch (e) {
      final cached = await SyncQueueService.getCachedCurrentSession();
      if (cached != null) {
        state = state.copyWith(currentSession: cached, isSessionLoading: false, clearError: true);
        return cached;
      }
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isSessionLoading: false, errorMessage: msg);
      return null;
    }
  }

  Future<CashierSessionModel?> openSession({
    required double openingBalance,
    String? notes,
  }) async {
    state = state.copyWith(isSessionLoading: true, clearError: true);
    try {
      final session = await _cashierRepository.openSession(
        openingBalance: openingBalance,
        notes: notes,
      );
      state = state.copyWith(currentSession: session, isSessionLoading: false, clearError: true);
      await fetchDashboardStats();
      return session;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isSessionLoading: false, errorMessage: msg);
      return null;
    }
  }

  Future<Map<String, dynamic>?> closeSession({
    required double closingBalance,
    String? notes,
  }) async {
    state = state.copyWith(isSessionLoading: true, clearError: true);
    try {
      final result = await _cashierRepository.closeSession(
        closingBalance: closingBalance,
        notes: notes,
      );
      state = state.copyWith(
        currentSession: CashierSessionModel.closed(),
        isSessionLoading: false,
        clearError: true,
      );
      await fetchDashboardStats();
      return result;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isSessionLoading: false, errorMessage: msg);
      return null;
    }
  }

  Future<List<ClientModel>> fetchClients({String? query}) async {
    try {
      final list = await _cashierRepository.getClients(query: query);
      final pending = await SyncQueueService.getPendingTransactions();
      final mergedList = _mergePendingTransactionsIntoClients(list, pending);

      state = state.copyWith(clients: mergedList, clearError: true);
      if (query == null || query.isEmpty) {
        SyncQueueService.cacheClients(mergedList);
      }
      return mergedList;
    } catch (e) {
      // Fallback vers les clients en cache local hors-ligne
      final cached = await SyncQueueService.getCachedClients();
      final pending = await SyncQueueService.getPendingTransactions();
      final mergedCached = _mergePendingTransactionsIntoClients(cached, pending);

      if (mergedCached.isNotEmpty) {
        final filtered = (query != null && query.isNotEmpty)
            ? mergedCached.where((c) {
                final q = query.toLowerCase();
                return c.fullName.toLowerCase().contains(q) ||
                    c.clientNumber.toLowerCase().contains(q) ||
                    (c.phone != null && c.phone!.contains(q));
              }).toList()
            : mergedCached;
        state = state.copyWith(clients: filtered, clearError: true);
        return filtered;
      }
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(errorMessage: msg);
      return [];
    }
  }

  List<ClientModel> _mergePendingTransactionsIntoClients(
    List<ClientModel> baseClients,
    List<Map<String, dynamic>> pending,
  ) {
    if (pending.isEmpty) return baseClients;

    final Map<int, ClientModel> clientsById = {};
    final Map<String, int> clientIdByPhone = {};

    for (final c in baseClients) {
      clientsById[c.id] = c;
      if (c.phone != null && c.phone!.isNotEmpty) {
        clientIdByPhone[c.phone!] = c.id;
      }
    }

    // 1. Ajouter les clients créés en mode hors-ligne
    for (final item in pending) {
      final action = item['action']?.toString() ?? item['transaction_type']?.toString();
      final offlineId = item['offline_id']?.toString() ?? item['id']?.toString() ?? '';

      if (action == 'create_client' || offlineId.startsWith('CLIENT-')) {
        final phone = item['phone']?.toString();
        final tempId = int.tryParse(item['temp_client_id']?.toString() ?? '') ??
            (int.tryParse(offlineId.replaceAll(RegExp(r'[^0-9\-]'), '')) ?? -DateTime.now().millisecondsSinceEpoch);

        final alreadyExists = (phone != null && clientIdByPhone.containsKey(phone)) || clientsById.containsKey(tempId);
        if (!alreadyExists) {
          final now = DateTime.now();
          final savingsAcc = {
            'id': tempId,
            'account_number': 'OFF-SAV-${tempId.abs() % 10000}',
            'account_type': 'savings',
            'balance': 0.0,
            'status': 'active',
            'client_id': tempId,
          };
          List<dynamic> accounts = [savingsAcc];
          if (item['create_tontine'] == true || item['initial_tontine_amount'] != null) {
            final initAmount = double.tryParse(item['initial_tontine_amount']?.toString() ?? '1000') ?? 1000.0;
            accounts.add({
              'id': tempId - 1,
              'account_number': 'OFF-TON-${(tempId.abs() + 1) % 10000}',
              'account_type': 'tontine',
              'balance': 0.0,
              'status': 'active',
              'client_id': tempId,
              'daily_amount': initAmount,
            });
          }

          final offClient = ClientModel(
            id: tempId,
            firstName: item['first_name']?.toString() ?? 'Client',
            lastName: item['last_name']?.toString() ?? '(Hors-Ligne)',
            phone: phone,
            email: item['email']?.toString(),
            address: item['address']?.toString() ?? 'Lomé',
            idType: item['id_type']?.toString() ?? 'cni',
            idNumber: item['id_number']?.toString() ?? 'OFF-CLI-${tempId.abs() % 10000}',
            kycStatus: 'pending',
            createdAt: now,
            accounts: accounts,
            loans: [],
          );
          clientsById[tempId] = offClient;
          if (phone != null) clientIdByPhone[phone] = tempId;
        }
      }
    }

    // 2. Ajuster les soldes selon les dépôts/retraits en attente
    for (final item in pending) {
      final action = item['action']?.toString() ?? item['transaction_type']?.toString();
      if (action == 'deposit' || action == 'withdrawal') {
        final rawAccId = item['account_id'];
        final accNum = item['account_number']?.toString();
        final amount = double.tryParse(item['amount']?.toString() ?? '0') ?? 0.0;
        final isDep = action == 'deposit';

        if (amount > 0) {
          for (final entry in clientsById.entries) {
            final client = entry.value;
            if (client.accounts != null) {
              bool modified = false;
              final updatedAccs = client.accounts!.map((acc) {
                if (acc is Map) {
                  final matchesId = rawAccId != null && (acc['id'] == rawAccId || acc['id']?.toString() == rawAccId.toString());
                  final matchesNum = accNum != null && acc['account_number']?.toString() == accNum;
                  if (matchesId || matchesNum) {
                    modified = true;
                    final curBal = double.tryParse(acc['balance']?.toString() ?? '0') ?? 0.0;
                    final newBal = isDep ? curBal + amount : (curBal - amount).clamp(0.0, double.infinity);
                    final m = Map<String, dynamic>.from(acc);
                    m['balance'] = newBal;
                    return m;
                  }
                }
                return acc;
              }).toList();

              if (modified) {
                clientsById[entry.key] = ClientModel(
                  id: client.id,
                  firstName: client.firstName,
                  lastName: client.lastName,
                  phone: client.phone,
                  email: client.email,
                  address: client.address,
                  city: client.city,
                  profession: client.profession,
                  idType: client.idType,
                  idNumber: client.idNumber,
                  kycStatus: client.kycStatus,
                  createdAt: client.createdAt,
                  accounts: updatedAccs,
                  loans: client.loans,
                );
              }
            }
          }
        }
      }
    }

    return clientsById.values.toList();
  }

  void recordLocalOfflineTransaction({
    required String type,
    required double amount,
    int? accountId,
    int? loanId,
    TransactionModel? transaction,
  }) {
    final currentStats = state.stats;
    final isDep = type == 'deposit' || type == 'loan_repayment';
    final newIn = isDep ? currentStats.todayTotalIn + amount : currentStats.todayTotalIn;
    final newOut = !isDep ? currentStats.todayTotalOut + amount : currentStats.todayTotalOut;
    final newBalance = isDep ? currentStats.cashBalance + amount : currentStats.cashBalance - amount;

    // Mise à jour immédiate du solde du compte dans la liste locale des clients
    List<ClientModel> updatedClients = List.from(state.clients);
    if (accountId != null) {
      updatedClients = updatedClients.map((client) {
        if (client.accounts != null) {
          bool clientModified = false;
          final updatedAccounts = client.accounts!.map((acc) {
            if (acc is Map && (acc['id'] == accountId || acc['id']?.toString() == accountId.toString())) {
              clientModified = true;
              final currentBal = double.tryParse(acc['balance']?.toString() ?? '0') ?? 0.0;
              final newBal = isDep ? currentBal + amount : (currentBal - amount).clamp(0.0, double.infinity);
              final updatedAcc = Map<String, dynamic>.from(acc);
              updatedAcc['balance'] = newBal;
              return updatedAcc;
            }
            return acc;
          }).toList();

          if (clientModified) {
            return ClientModel(
              id: client.id,
              firstName: client.firstName,
              lastName: client.lastName,
              phone: client.phone,
              email: client.email,
              address: client.address,
              city: client.city,
              profession: client.profession,
              idType: client.idType,
              idNumber: client.idNumber,
              kycStatus: client.kycStatus,
              createdAt: client.createdAt,
              accounts: updatedAccounts,
              loans: client.loans,
            );
          }
        }
        return client;
      }).toList();
    }

    final updatedRecent = transaction != null
        ? [transaction, ...currentStats.recentTransactions]
        : currentStats.recentTransactions;

    final updatedOperations = transaction != null
        ? [transaction, ...state.operations]
        : state.operations;

    final updatedStats = DashboardStats(
      openingBalance: currentStats.openingBalance,
      todayTotalIn: newIn,
      todayTotalOut: newOut,
      cashBalance: newBalance,
      todayTxCount: currentStats.todayTxCount + 1,
      agencyClientCount: currentStats.agencyClientCount,
      recentTransactions: updatedRecent,
    );

    state = state.copyWith(
      stats: updatedStats,
      clients: updatedClients,
      operations: updatedOperations,
    );

    // Persistance immédiate dans le cache SharedPreferences
    SyncQueueService.cacheDashboardStats(updatedStats);
    SyncQueueService.cacheClients(updatedClients);
    SyncQueueService.cacheOperations(updatedOperations);
  }

  Future<List<ClientModel>> searchClients(String query) async {
    return fetchClients(query: query);
  }

  Future<bool> approveClientKyc(int clientId) async {
    try {
      final ok = await _cashierRepository.approveClientKyc(clientId);
      if (ok) {
        await fetchClients();
      }
      return ok;
    } catch (_) {
      return false;
    }
  }

  Future<List<TransactionModel>> fetchAccountTransactions(int accountId) async {
    try {
      final txs = await _cashierRepository.getAccountTransactions(accountId);
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();
      final accountPending = pendingModels.where((p) => p.accountId == accountId || p.accountId?.toString() == accountId.toString()).toList();
      final existingRefs = txs.map((t) => t.reference).toSet();
      final pendingToAdd = accountPending.where((p) => !existingRefs.contains(p.reference)).toList();
      return [...pendingToAdd, ...txs];
    } catch (_) {
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();
      return pendingModels.where((p) => p.accountId == accountId || p.accountId?.toString() == accountId.toString()).toList();
    }
  }

  Future<List<TransactionModel>> fetchOperations({
    String? search,
    DateTime? dateStart,
    DateTime? dateEnd,
    DateTime? date,
    int? sessionId,
  }) async {
    try {
      final ops = await _cashierRepository.getOperations(
        search: search,
        dateStart: dateStart,
        dateEnd: dateEnd,
        date: date ?? state.selectedArchiveDate,
        sessionId: sessionId ?? (state.selectedArchiveSession != null ? (state.selectedArchiveSession!['id'] as int?) : null),
      );

      // Fusionner les opérations en attente de synchronisation locale
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();
      final existingRefs = ops.map((t) => t.reference).toSet();
      final pendingToAdd = pendingModels.where((p) => !existingRefs.contains(p.reference)).toList();

      final combined = [...pendingToAdd, ...ops];
      state = state.copyWith(operations: combined, clearError: true);
      SyncQueueService.cacheOperations(combined);
      return combined;
    } catch (e) {
      // Fallback vers le cache local
      final cachedOps = await SyncQueueService.getCachedOperations();
      final pendingModels = await SyncQueueService.getPendingTransactionsAsModels();
      final existingRefs = cachedOps.map((t) => t.reference).toSet();
      final pendingToAdd = pendingModels.where((p) => !existingRefs.contains(p.reference)).toList();
      final combined = [...pendingToAdd, ...cachedOps];

      if (combined.isNotEmpty) {
        state = state.copyWith(operations: combined, clearError: true);
        return combined;
      }

      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(errorMessage: msg);
      return state.operations;
    }
  }

  Future<AccountModel?> searchAccount(String query) async {
    try {
      return await _cashierRepository.searchAccount(query);
    } catch (_) {
      return null;
    }
  }

  Future<TransactionModel?> processDeposit({
    required int accountId,
    required double amount,
    String? note,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final tx = await _cashierRepository.processDeposit(
        accountId: accountId,
        amount: amount,
        note: note,
      );
      // Refresh both dashboard stats AND client data so balances update in real-time
      await Future.wait([fetchDashboardStats(), fetchClients()]);
      state = state.copyWith(isLoading: false, clearError: true);
      return tx;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return null;
    }
  }

  Future<TransactionModel?> processWithdrawal({
    required int accountId,
    required double amount,
    String? note,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final tx = await _cashierRepository.processWithdrawal(
        accountId: accountId,
        amount: amount,
        note: note,
      );
      // Refresh both dashboard stats AND client data so balances update in real-time
      await Future.wait([fetchDashboardStats(), fetchClients()]);
      state = state.copyWith(isLoading: false, clearError: true);
      return tx;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return null;
    }
  }

  Future<TransactionModel?> processTransfer({
    required int sourceAccountId,
    required int destinationAccountId,
    required double amount,
    String? note,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final tx = await _cashierRepository.processTransfer(
        sourceAccountId: sourceAccountId,
        destinationAccountId: destinationAccountId,
        amount: amount,
        note: note,
      );
      // Refresh stats and client data in real time
      await Future.wait([fetchDashboardStats(), fetchClients()]);
      state = state.copyWith(isLoading: false, clearError: true);
      return tx;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return null;
    }
  }

  Future<ClientModel?> registerExpressClient({
    required String firstName,
    required String lastName,
    required String phone,
    String? email,
    String? address,
    String? gender,
    String? idType,
    String? idNumber,
    double initialTontineAmount = 1000,
    bool createTontine = false,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final client = await _cashierRepository.registerExpressClient(
        firstName: firstName,
        lastName: lastName,
        phone: phone,
        email: email,
        address: address,
        gender: gender,
        idType: idType,
        idNumber: idNumber,
        initialTontineAmount: initialTontineAmount,
        createTontine: createTontine,
      );
      await fetchClients();
      await fetchDashboardStats();
      state = state.copyWith(isLoading: false, clearError: true);
      return client;
    } catch (e) {
      // Enregistrement hors-ligne si serveur inaccessible
      final now = DateTime.now();
      final tempId = -now.millisecondsSinceEpoch;
      final tempAccountNum = 'OFF-SAV-${now.day}${now.month}-${now.millisecondsSinceEpoch % 10000}';

      final savingsAcc = {
        'id': tempId,
        'account_number': tempAccountNum,
        'account_type': 'savings',
        'balance': 0.0,
        'status': 'active',
        'client_id': tempId,
      };

      List<dynamic> accounts = [savingsAcc];
      if (createTontine) {
        final tempTontineNum = 'OFF-TON-${now.day}${now.month}-${(now.millisecondsSinceEpoch + 1) % 10000}';
        accounts.add({
          'id': tempId - 1,
          'account_number': tempTontineNum,
          'account_type': 'tontine',
          'balance': 0.0,
          'status': 'active',
          'client_id': tempId,
          'daily_amount': initialTontineAmount,
          'tontine_account': {
            'tontine_amount': initialTontineAmount,
            'cycle_duration_months': 12,
            'payment_frequency': 'daily',
          },
        });
      }

      final offlineClient = ClientModel(
        id: tempId,
        firstName: firstName,
        lastName: lastName,
        phone: phone,
        email: email,
        address: address,
        idType: idType,
        idNumber: idNumber ?? 'CLI-OFF-${now.millisecondsSinceEpoch % 10000}',
        kycStatus: 'pending',
        createdAt: now,
        accounts: accounts,
        loans: [],
      );

      // Mettre en file d'attente pour synchronisation ultérieure
      await SyncQueueService.addOfflineTransaction({
        'offline_id': 'CLIENT-$tempId',
        'id': 'CLIENT-$tempId',
        'action': 'create_client',
        'transaction_type': 'create_client',
        'temp_client_id': tempId,
        'first_name': firstName,
        'last_name': lastName,
        'phone': phone,
        'email': email,
        'address': address,
        'gender': gender,
        'id_type': idType,
        'id_number': idNumber,
        'initial_tontine_amount': initialTontineAmount,
        'create_tontine': createTontine,
        'offline_created_at': now.toIso8601String(),
      });

      final updatedClients = [offlineClient, ...state.clients];
      state = state.copyWith(
        clients: updatedClients,
        isLoading: false,
        clearError: true,
      );
      await SyncQueueService.cacheClients(updatedClients);

      return offlineClient;
    }
  }

  Future<AccountModel?> createAccount({
    required int clientId,
    required String accountType,
    double? targetAmount,
    int? cycleDurationMonths,
    String? paymentFrequency,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final account = await _cashierRepository.createAccount(
        clientId: clientId,
        accountType: accountType,
        targetAmount: targetAmount,
        cycleDurationMonths: cycleDurationMonths,
        paymentFrequency: paymentFrequency,
      );
      await fetchClients();
      await fetchDashboardStats();
      state = state.copyWith(isLoading: false, clearError: true);
      return account;
    } catch (e) {
      // Création de compte hors-ligne
      final now = DateTime.now();
      final tempAccountId = -now.millisecondsSinceEpoch;
      final prefix = accountType == 'tontine' ? 'TON' : 'SAV';
      final tempAccountNum = 'OFF-$prefix-${now.day}${now.month}-${now.millisecondsSinceEpoch % 10000}';

      final offlineAccMap = {
        'id': tempAccountId,
        'account_number': tempAccountNum,
        'account_type': accountType,
        'balance': 0.0,
        'status': 'active',
        'client_id': clientId,
        'daily_amount': targetAmount,
        'cycle_days': 31,
        'payment_frequency': paymentFrequency ?? 'daily',
        'cycle_duration_months': cycleDurationMonths ?? 12,
        if (accountType == 'tontine')
          'tontine_account': {
            'tontine_amount': targetAmount ?? 500,
            'cycle_duration_months': cycleDurationMonths ?? 12,
            'payment_frequency': paymentFrequency ?? 'daily',
          },
      };

      final offlineAccountModel = AccountModel.fromJson(offlineAccMap);

      // Ajouter le nouveau compte au client dans state.clients
      final updatedClients = state.clients.map((c) {
        if (c.id == clientId) {
          final currentAccs = List<dynamic>.from(c.accounts ?? []);
          currentAccs.add(offlineAccMap);
          return ClientModel(
            id: c.id,
            firstName: c.firstName,
            lastName: c.lastName,
            phone: c.phone,
            email: c.email,
            address: c.address,
            city: c.city,
            profession: c.profession,
            idType: c.idType,
            idNumber: c.idNumber,
            kycStatus: c.kycStatus,
            createdAt: c.createdAt,
            accounts: currentAccs,
            loans: c.loans,
          );
        }
        return c;
      }).toList();

      // Mettre en file d'attente pour synchronisation ultérieure
      await SyncQueueService.addOfflineTransaction({
        'offline_id': 'ACC-$tempAccountId',
        'id': 'ACC-$tempAccountId',
        'action': 'create_account',
        'transaction_type': 'create_account',
        'temp_account_id': tempAccountId,
        'account_number': tempAccountNum,
        'client_id': clientId,
        'account_type': accountType,
        'target_amount': targetAmount,
        'cycle_duration_months': cycleDurationMonths,
        'payment_frequency': paymentFrequency,
        'offline_created_at': now.toIso8601String(),
      });

      state = state.copyWith(
        clients: updatedClients,
        isLoading: false,
        clearError: true,
      );
      await SyncQueueService.cacheClients(updatedClients);

      return offlineAccountModel;
    }
  }

  Future<ClientModel?> updateClient({
    required int clientId,
    required String firstName,
    required String lastName,
    required String phone,
    String? email,
    String? address,
    String? profession,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final updated = await _cashierRepository.updateClient(
        clientId: clientId,
        firstName: firstName,
        lastName: lastName,
        phone: phone,
        email: email,
        address: address,
        profession: profession,
      );
      await fetchClients();
      state = state.copyWith(isLoading: false, clearError: true);
      return updated;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return null;
    }
  }

  Future<bool> performVaultTransfer({
    required String type,
    required double amount,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      await _cashierRepository.vaultTransfer(
        type: type,
        amount: amount,
        notes: notes,
      );
      await fetchDashboardStats();
      await fetchOperations();
      state = state.copyWith(isLoading: false, clearError: true);
      return true;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return false;
    }
  }

  Future<bool> reverseTransaction({
    required int transactionId,
    required String supervisorPin,
    required String reason,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      await _cashierRepository.reverseTransaction(
        transactionId: transactionId,
        supervisorPin: supervisorPin,
        reason: reason,
      );
      await fetchDashboardStats();
      await fetchOperations();
      await fetchClients();
      state = state.copyWith(isLoading: false, clearError: true);
      return true;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: msg,
      );
      return false;
    }
  }
}

final cashierNotifierProvider = StateNotifierProvider<CashierNotifier, CashierState>((ref) {
  final cashierRepository = ref.watch(cashierRepositoryProvider);
  return CashierNotifier(cashierRepository);
});
