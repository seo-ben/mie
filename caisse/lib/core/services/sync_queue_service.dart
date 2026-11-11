import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../../data/models/client_model.dart';
import '../../data/models/loan_model.dart';
import '../../data/models/transaction_model.dart';
import '../../data/models/cashier_session_model.dart';
import '../../data/models/field_agent_model.dart';
import '../../domain/entities/dashboard_stats.dart';

class SyncQueueService {
  static const String _queueKey = 'offline_transactions_queue';
  static const String _cachedClientsKey = 'cached_clients_list';
  static const String _cachedLoansKey = 'cached_loans_list';
  static const String _cachedMetricsKey = 'cached_loans_metrics';
  static const String _cachedStatsKey = 'cached_dashboard_stats';
  static const String _cachedSessionKey = 'cached_current_session';
  static const String _lastSyncKey = 'last_sync_timestamp';

  /// Ajouter une transaction hors-ligne dans la file d'attente locale
  static Future<void> addOfflineTransaction(Map<String, dynamic> transaction) async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_queueKey) ?? [];

    rawList.add(jsonEncode(transaction));
    await prefs.setStringList(_queueKey, rawList);
  }

  /// Récupérer la liste des transactions hors-ligne en attente
  static Future<List<Map<String, dynamic>>> getPendingTransactions() async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_queueKey) ?? [];

    return rawList.map((str) {
      try {
        return jsonDecode(str) as Map<String, dynamic>;
      } catch (_) {
        return <String, dynamic>{};
      }
    }).where((map) => map.isNotEmpty).toList();
  }

  /// Retirer les transactions qui ont été synchronisées avec succès par le serveur
  static Future<void> removeSyncedTransactions(List<String> syncedIds) async {
    if (syncedIds.isEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_queueKey) ?? [];
    final syncedSet = <String>{};
    for (final s in syncedIds) {
      final str = s.toString().trim();
      syncedSet.add(str);
      // Extraire les identifiants numériques bruts
      final cleanNum = str.replaceAll(RegExp(r'[^0-9]'), '');
      if (cleanNum.isNotEmpty) {
        syncedSet.add(cleanNum);
        syncedSet.add('-$cleanNum');
      }
    }

    final updated = rawList.where((str) {
      try {
        final map = jsonDecode(str) as Map<String, dynamic>;
        final id = (map['offline_id'] ?? map['id'])?.toString().trim();
        final tempCliId = map['temp_client_id']?.toString().trim();
        final tempAccId = map['temp_account_id']?.toString().trim();
        final offRef = map['reference']?.toString().trim();

        bool isSynced = (id != null && syncedSet.contains(id)) ||
            (tempCliId != null && syncedSet.contains(tempCliId)) ||
            (tempAccId != null && syncedSet.contains(tempAccId)) ||
            (offRef != null && syncedSet.contains(offRef));

        if (!isSynced && id != null) {
          final cleanId = id.replaceAll(RegExp(r'[^0-9]'), '');
          if (cleanId.isNotEmpty && (syncedSet.contains(cleanId) || syncedSet.contains('-$cleanId'))) {
            isSynced = true;
          }
        }

        return !isSynced;
      } catch (_) {
        return false;
      }
    }).toList();

    await prefs.setStringList(_queueKey, updated);
  }

  /// Obtenir le nombre d'opérations en attente
  static Future<int> getPendingCount() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_queueKey)?.length ?? 0;
  }

  /// Enregistrer le timestamp de la dernière synchronisation
  static Future<void> setLastSyncTime(DateTime time) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_lastSyncKey, time.toIso8601String());
  }

  /// Obtenir le timestamp de la dernière synchronisation
  static Future<DateTime?> getLastSyncTime() async {
    final prefs = await SharedPreferences.getInstance();
    final str = prefs.getString(_lastSyncKey);
    return str != null ? DateTime.tryParse(str) : null;
  }

  /// Sauvegarder les clients en cache local
  static Future<void> cacheClients(List<ClientModel> clients) async {
    final prefs = await SharedPreferences.getInstance();
    final jsonList = clients.map((c) => jsonEncode(c.toJson())).toList();
    await prefs.setStringList(_cachedClientsKey, jsonList);
  }

  /// Récupérer les clients depuis le cache local
  static Future<List<ClientModel>> getCachedClients() async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_cachedClientsKey) ?? [];
    return rawList.map((str) {
      try {
        return ClientModel.fromJson(jsonDecode(str) as Map<String, dynamic>);
      } catch (_) {
        return null;
      }
    }).whereType<ClientModel>().toList();
  }

  /// Sauvegarder les prêts et métriques en cache local
  static Future<void> cacheLoans(List<LoanModel> loans, LoanMetricsModel metrics) async {
    final prefs = await SharedPreferences.getInstance();
    final jsonList = loans.map((l) => jsonEncode(l.toJson())).toList();
    await prefs.setStringList(_cachedLoansKey, jsonList);
    await prefs.setString(_cachedMetricsKey, jsonEncode(metrics.toJson()));
  }

  /// Récupérer les prêts depuis le cache local
  static Future<List<LoanModel>> getCachedLoans() async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_cachedLoansKey) ?? [];
    return rawList.map((str) {
      try {
        return LoanModel.fromJson(jsonDecode(str) as Map<String, dynamic>);
      } catch (_) {
        return null;
      }
    }).whereType<LoanModel>().toList();
  }

  /// Récupérer les métriques de prêts depuis le cache local
  static Future<LoanMetricsModel?> getCachedLoanMetrics() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cachedMetricsKey);
    if (raw == null) return null;
    try {
      return LoanMetricsModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  /// Sauvegarder les statistiques du tableau de bord
  static Future<void> cacheDashboardStats(DashboardStats stats) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cachedStatsKey, jsonEncode(stats.toJson()));
  }

  /// Récupérer les statistiques du tableau de bord depuis le cache
  static Future<DashboardStats?> getCachedDashboardStats() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cachedStatsKey);
    if (raw == null) return null;
    try {
      return DashboardStats.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  /// Sauvegarder la session actuelle
  static Future<void> cacheCurrentSession(CashierSessionModel session) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cachedSessionKey, jsonEncode(session.toJson()));
  }

  /// Récupérer la session actuelle depuis le cache
  static Future<CashierSessionModel?> getCachedCurrentSession() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cachedSessionKey);
    if (raw == null) return null;
    try {
      return CashierSessionModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  static const String _cachedOperationsKey = 'cached_operations_list';

  /// Récupérer les transactions hors-ligne sous forme de TransactionModel pour l'UI
  static Future<List<TransactionModel>> getPendingTransactionsAsModels() async {
    final pending = await getPendingTransactions();
    return pending.map((map) {
      final offlineId = map['offline_id']?.toString() ?? map['id']?.toString() ?? 'OFF-0';
      final type = map['transaction_type']?.toString() ?? map['action']?.toString() ?? 'deposit';
      final amount = double.tryParse(map['amount']?.toString() ?? '0') ?? 0.0;
      final createdAtStr = map['offline_created_at']?.toString();
      final dt = createdAtStr != null ? (DateTime.tryParse(createdAtStr) ?? DateTime.now()) : DateTime.now();

      return TransactionModel(
        id: -dt.millisecondsSinceEpoch,
        reference: offlineId.startsWith('OFF') ? offlineId : 'OFFLINE-$offlineId',
        transactionType: type,
        accountId: int.tryParse(map['account_id']?.toString() ?? ''),
        amount: amount,
        fee: 0,
        status: 'pending_sync',
        description: map['description']?.toString() ?? '$type (En attente de synchronisation)',
        createdAt: dt,
        clientName: map['client_name']?.toString() ?? 'Client Guichet',
        accountNumber: map['account_number']?.toString() ?? 'ACC-LOCAL',
        agentName: 'Moi (Hors-Ligne)',
      );
    }).toList();
  }

  /// Sauvegarder les opérations récentes en cache local
  static Future<void> cacheOperations(List<TransactionModel> operations) async {
    final prefs = await SharedPreferences.getInstance();
    final jsonList = operations.map((op) => jsonEncode(op.toJson())).toList();
    await prefs.setStringList(_cachedOperationsKey, jsonList);
  }

  /// Récupérer les opérations récentes depuis le cache local
  static Future<List<TransactionModel>> getCachedOperations() async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_cachedOperationsKey) ?? [];
    return rawList.map((str) {
      try {
        return TransactionModel.fromJson(jsonDecode(str) as Map<String, dynamic>);
      } catch (_) {
        return null;
      }
    }).whereType<TransactionModel>().toList();
  }

  static const String _cachedFieldAgentsKey = 'cached_field_agents_data';

  /// Sauvegarder les agents de terrain en cache local
  static Future<void> cacheFieldAgents(AgencyFieldTeamResponse data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cachedFieldAgentsKey, jsonEncode(data.toJson()));
  }

  /// Récupérer les agents de terrain depuis le cache local
  static Future<AgencyFieldTeamResponse?> getCachedFieldAgents() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cachedFieldAgentsKey);
    if (raw == null) return null;
    try {
      return AgencyFieldTeamResponse.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  /// Vider toute la file locale (en cas de réinitialisation)
  static Future<void> clearPendingQueue() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_queueKey);
  }
}
