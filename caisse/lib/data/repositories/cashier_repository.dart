import 'package:dio/dio.dart';
import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../../data/models/client_model.dart';
import '../../data/models/account_model.dart';
import '../../data/models/transaction_model.dart';
import '../../data/models/cashier_session_model.dart';
import '../../data/models/loan_model.dart';
import '../../data/models/client_eligibility_model.dart';
import '../../data/models/field_agent_model.dart';
import '../../domain/entities/dashboard_stats.dart';
import '../../domain/repositories/i_cashier_repository.dart';
import '../../core/services/sync_queue_service.dart';

class CashierRepository implements ICashierRepository {
  final ApiClient _api;

  CashierRepository({ApiClient? api}) : _api = api ?? ApiClient();

  @override
  Future<DashboardStats> getDashboardStats({
    String? period,
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    int? sessionId,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (period != null) params['period'] = period;
      if (date != null) params['date'] = _apiDate(date);
      if (dateStart != null) params['date_start'] = _apiDate(dateStart);
      if (dateEnd != null) params['date_end'] = _apiDate(dateEnd);
      if (sessionId != null) params['session_id'] = sessionId;

      final response = await _api.dio.get(
        ApiConstants.agentDashboard,
        queryParameters: params.isEmpty ? null : params,
      );
      final data = response.data;

      if (data['success'] == true && data['data'] != null) {
        return DashboardStats.fromApiResponse(data['data'] as Map<String, dynamic>);
      }
      return DashboardStats.empty();
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du chargement des statistiques.';
      throw Exception(msg);
    } catch (e) {
      throw Exception('Erreur lors du chargement des statistiques.');
    }
  }

  @override
  Future<List<Map<String, dynamic>>> getSessionHistory({String? status, DateTime? date}) async {
    try {
      final params = <String, dynamic>{};
      if (status != null && status.isNotEmpty) params['status'] = status;
      if (date != null) params['date'] = _apiDate(date);

      final response = await _api.dio.get(
        ApiConstants.sessionHistory,
        queryParameters: params.isEmpty ? null : params,
      );
      final data = response.data;
      if (data['success'] == true && data['data'] is List) {
        return List<Map<String, dynamic>>.from(data['data'] as List);
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  @override
  Future<CashierSessionModel> getCurrentSession() async {
    try {
      final response = await _api.dio.get(ApiConstants.sessionCurrent);
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return CashierSessionModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      return CashierSessionModel.closed();
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Impossible de récupérer la session active.';
      throw Exception(msg);
    } catch (e) {
      return CashierSessionModel.closed();
    }
  }

  @override
  Future<CashierSessionModel> openSession({required double openingBalance, String? notes}) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.sessionOpen,
        data: {
          'opening_balance': openingBalance,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return CashierSessionModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      throw Exception(data['message'] ?? 'Impossible d\'ouvrir la session.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'ouverture de caisse.';
      throw Exception(msg);
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  @override
  Future<Map<String, dynamic>> closeSession({required double closingBalance, String? notes}) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.sessionClose,
        data: {
          'closing_balance': closingBalance,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return (data['data'] as Map<String, dynamic>?) ?? {};
      }
      throw Exception(data['message'] ?? 'Impossible de clôturer la session.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de la clôture de caisse.';
      throw Exception(msg);
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  @override
  Future<List<ClientModel>> getClients({String? query}) async {
    try {
      final response = await _api.dio.get(
        ApiConstants.clients,
        queryParameters: query != null && query.isNotEmpty ? {'search': query} : null,
      );
      final data = response.data;
      if (data['data'] != null) {
        final list = data['data'] is List ? data['data'] : data['data']['data'] ?? [];
        return (list as List)
            .map((c) => ClientModel.fromJson(c as Map<String, dynamic>))
            .toList();
      }
      return [];
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du chargement des clients.';
      throw Exception(msg);
    } catch (e) {
      throw Exception('Erreur lors du chargement des clients.');
    }
  }

  @override
  Future<bool> approveClientKyc(int clientId) async {
    try {
      final response = await _api.dio.post(ApiConstants.approveClientKyc(clientId));
      final data = response.data;
      return data['data'] != null || data['success'] == true;
    } catch (_) {
      return false;
    }
  }

  @override
  Future<List<TransactionModel>> getAccountTransactions(int accountId) async {
    try {
      final response = await _api.dio.get(ApiConstants.accountTransactions(accountId));
      final data = response.data;
      final payload = data['data'];
      final list = payload is Map ? payload['transactions'] : payload;
      if (list is List) {
        return list
            .map((tx) => TransactionModel.fromJson(tx as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  @override
  Future<List<TransactionModel>> getOperations({
    String? search,
    DateTime? dateStart,
    DateTime? dateEnd,
    DateTime? date,
    int? sessionId,
  }) async {
    try {
      final parameters = <String, dynamic>{};
      if (search != null && search.trim().isNotEmpty) parameters['search'] = search.trim();
      if (dateStart != null) parameters['date_start'] = _apiDate(dateStart);
      if (dateEnd != null) parameters['date_end'] = _apiDate(dateEnd);
      if (date != null) parameters['date'] = _apiDate(date);
      if (sessionId != null) parameters['session_id'] = sessionId;

      final response = await _api.dio.get(
        ApiConstants.transactions,
        queryParameters: parameters.isEmpty ? null : parameters,
      );
      final data = response.data;
      if (data['data'] is List) {
        return (data['data'] as List)
            .map((tx) => TransactionModel.fromJson(tx as Map<String, dynamic>))
            .toList();
      }
      return [];
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du chargement des opérations.';
      throw Exception(msg);
    } catch (e) {
      throw Exception('Erreur lors du chargement des opérations.');
    }
  }

  @override
  Future<AccountModel?> searchAccount(String query) async {
    try {
      final response = await _api.dio.get(
        ApiConstants.searchAccount,
        queryParameters: {'query': query},
      );
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return AccountModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  @override
  Future<TransactionModel> processDeposit({
    required int accountId,
    required double amount,
    String? note,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.quickDeposit,
        data: {
          'account_id': accountId,
          'amount': amount,
          'description': note ?? 'Dépôt au guichet caisse',
        },
      );

      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return TransactionModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Échec du dépôt.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'encaissement.';
      throw Exception(msg);
    }
  }

  @override
  Future<TransactionModel> processWithdrawal({
    required int accountId,
    required double amount,
    String? note,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.quickWithdrawal,
        data: {
          'account_id': accountId,
          'amount': amount,
          'description': note ?? 'Retrait au guichet caisse',
        },
      );

      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return TransactionModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Échec du retrait.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du décaissement.';
      throw Exception(msg);
    }
  }

  @override
  Future<TransactionModel> processTransfer({
    required int sourceAccountId,
    required int destinationAccountId,
    required double amount,
    String? note,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.quickTransfer,
        data: {
          'source_account_id': sourceAccountId,
          'destination_account_id': destinationAccountId,
          'amount': amount,
          if (note != null && note.isNotEmpty) 'description': note,
        },
      );

      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        final txData = data['data']['transaction'] ?? data['data'];
        return TransactionModel.fromJson(txData as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Échec du virement.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du virement.';
      throw Exception(msg);
    }
  }

  @override
  Future<ClientModel> registerExpressClient({
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
    try {
      final response = await _api.dio.post(
        ApiConstants.clients,
        data: {
          'first_name': firstName,
          'last_name': lastName,
          'phone': phone,
          'email': email,
          'address': address ?? 'Lomé',
          'gender': gender ?? 'M',
          'id_type': idType ?? 'cni',
          'id_number': idNumber ?? 'N/A',
          'initial_tontine_amount': initialTontineAmount,
          'create_tontine': createTontine,
        },
      );

      final data = response.data;
      if (data['data'] != null && (data['success'] == true || response.statusCode == 201)) {
        return ClientModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Impossible d\'inscrire le client.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'enregistrement.';
      throw Exception(msg);
    }
  }

  @override
  Future<AccountModel> createAccount({
    required int clientId,
    required String accountType,
    double? targetAmount,
    int? cycleDurationMonths,
    String? paymentFrequency,
  }) async {
    try {
      final payload = <String, dynamic>{
        'account_type': accountType,
      };
      if (accountType == 'tontine') {
        payload['target_amount'] = targetAmount ?? 1000;
        payload['cycle_duration_months'] = cycleDurationMonths ?? 12;
        payload['payment_frequency'] = paymentFrequency ?? 'daily';
      }

      final response = await _api.dio.post(
        '/agent/accounts/clients/$clientId',
        data: payload,
      );

      final data = response.data;
      if (data['data'] != null && (data['success'] == true || response.statusCode == 201 || data['message'] != null)) {
        return AccountModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Impossible de créer le compte.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de la création du compte.';
      throw Exception(msg);
    }
  }

  @override
  Future<ClientModel> updateClient({
    required int clientId,
    required String firstName,
    required String lastName,
    required String phone,
    String? email,
    String? address,
    String? profession,
  }) async {
    try {
      final payload = <String, dynamic>{
        'first_name': firstName,
        'last_name': lastName,
        'phone': phone,
        if (email != null && email.isNotEmpty) 'email': email,
        'address': address ?? 'Lomé',
        if (profession != null && profession.isNotEmpty) 'profession': profession,
      };

      final response = await _api.dio.put(
        '/agent/clients/$clientId',
        data: payload,
      );

      final data = response.data;
      if (data['data'] != null) {
        return ClientModel.fromJson(data['data'] as Map<String, dynamic>);
      } else {
        throw Exception(data['message'] ?? 'Impossible de modifier le client.');
      }
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de la modification du client.';
      throw Exception(msg);
    }
  }

  @override
  Future<({List<LoanModel> loans, LoanMetricsModel metrics})> getLoans({String? status, String? search}) async {
    try {
      final response = await _api.dio.get(
        ApiConstants.loans,
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (search != null && search.isNotEmpty) 'search': search,
        },
      );
      final data = response.data;
      final rawList = data['data'] is Map && data['data']['data'] is List
          ? data['data']['data'] as List
          : (data['data'] is List ? data['data'] as List : []);

      final loans = rawList.map((l) => LoanModel.fromJson(l as Map<String, dynamic>)).toList();
      final metrics = data['metrics'] is Map<String, dynamic>
          ? LoanMetricsModel.fromJson(data['metrics'] as Map<String, dynamic>)
          : const LoanMetricsModel();

      return (loans: loans, metrics: metrics);
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du chargement des prêts.';
      throw Exception(msg);
    } catch (e) {
      throw Exception('Erreur lors du chargement des prêts : $e');
    }
  }

  @override
  Future<ClientEligibilityModel> getClientLoanEligibility(int clientId) async {
    try {
      final response = await _api.dio.get(ApiConstants.clientLoanEligibility(clientId));
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return ClientEligibilityModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      throw Exception(data['message'] ?? 'Impossible de vérifier l\'éligibilité.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de la vérification d\'éligibilité.';
      throw Exception(msg);
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  @override
  Future<Map<String, dynamic>> getLoanConfig() async {
    try {
      final response = await _api.dio.get(ApiConstants.loanConfig);
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return data['data'] as Map<String, dynamic>;
      }
      return {
        'default_interest_rate': 17.0,
        'min_amount': 10000.0,
        'max_amount': 5000000.0,
        'max_duration_months': 36,
      };
    } catch (_) {
      return {
        'default_interest_rate': 17.0,
        'min_amount': 10000.0,
        'max_amount': 5000000.0,
        'max_duration_months': 36,
      };
    }
  }

  @override
  Future<Map<String, dynamic>> simulateLoan({
    required double amount,
    required int durationMonths,
    double? interestRate,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.loanSimulate,
        data: {
          'amount': amount,
          'duration_months': durationMonths,
          if (interestRate != null) 'interest_rate': interestRate,
        },
      );
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return data['data'] as Map<String, dynamic>;
      }
      throw Exception(data['message'] ?? 'Erreur lors de la simulation.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de la simulation.';
      throw Exception(msg);
    }
  }

  @override
  Future<LoanModel> applyLoan({
    required int clientId,
    required double requestedAmount,
    required int durationMonths,
    String? purpose,
    String? collateralDescription,
    double? interestRate,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.loanApply,
        data: {
          'client_id': clientId,
          'requested_amount': requestedAmount,
          'duration_months': durationMonths,
          if (purpose != null) 'purpose': purpose,
          if (collateralDescription != null) 'collateral_description': collateralDescription,
          if (interestRate != null) 'interest_rate': interestRate,
        },
      );
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return LoanModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      throw Exception(data['message'] ?? 'Impossible d\'enregistrer le prêt.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'enregistrement du prêt.';
      throw Exception(msg);
    }
  }

  @override
  Future<LoanModel> approveLoan({
    required int loanId,
    double? approvedAmount,
    String? notes,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.loanApprove(loanId),
        data: {
          if (approvedAmount != null) 'approved_amount': approvedAmount,
          if (notes != null) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        return LoanModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      throw Exception(data['message'] ?? 'Impossible d\'approuver le prêt.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'approbation du prêt.';
      throw Exception(msg);
    }
  }

  @override
  Future<Map<String, dynamic>> disburseLoan({
    required int loanId,
    String? paymentMethod,
    String? notes,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.loanDisburse(loanId),
        data: {
          'payment_method': paymentMethod ?? 'cash',
          if (notes != null) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return data['data'] as Map<String, dynamic>? ?? {};
      }
      throw Exception(data['message'] ?? 'Impossible de décaisser le prêt.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du décaissement.';
      throw Exception(msg);
    }
  }

  @override
  Future<Map<String, dynamic>> repayLoan({
    required int loanId,
    required double amount,
    String? paymentMethod,
    String? notes,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.loanRepay(loanId),
        data: {
          'amount': amount,
          'payment_method': paymentMethod ?? 'cash',
          if (notes != null) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return data['data'] as Map<String, dynamic>? ?? {};
      }
      throw Exception(data['message'] ?? 'Impossible d\'enregistrer le remboursement.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du remboursement.';
      throw Exception(msg);
    }
  }

  @override
  Future<List<LoanPaymentModel>> getLoanSchedule(int loanId) async {
    try {
      final response = await _api.dio.get(ApiConstants.loanSchedule(loanId));
      final data = response.data;
      if (data['success'] == true && data['data']?['payments'] is List) {
        return (data['data']['payments'] as List)
            .map((p) => LoanPaymentModel.fromJson(p as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }


  @override
  Future<Map<String, dynamic>> vaultTransfer({
    required String type,
    required double amount,
    String? notes,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.sessionVaultTransfer,
        data: {
          'type': type,
          'amount': amount,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return (data['data'] as Map<String, dynamic>?) ?? {};
      }
      throw Exception(data['message'] ?? 'Erreur lors du transfert coffre.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du transfert avec le coffre-fort.';
      throw Exception(msg);
    }
  }

  @override
  Future<Map<String, dynamic>> reverseTransaction({
    required int transactionId,
    required String supervisorPin,
    required String reason,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.transactionReversal(transactionId),
        data: {
          'supervisor_pin': supervisorPin,
          'reason': reason,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return (data['data'] as Map<String, dynamic>?) ?? {};
      }
      throw Exception(data['message'] ?? 'Impossible d\'annuler la transaction.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors de l\'annulation de l\'opération.';
      throw Exception(msg);
    }
  }

  @override
  Future<Map<String, dynamic>> syncTransactions(List<Map<String, dynamic>> transactions) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.syncTransactions,
        data: {
          'transactions': transactions,
        },
      );
      final data = response.data;
      if (data['success'] == true) {
        return data as Map<String, dynamic>;
      }
      throw Exception(data['message'] ?? 'Erreur lors de la synchronisation.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Serveur injoignable pour la synchronisation.';
      throw Exception(msg);
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  @override
  Future<AgencyFieldTeamResponse> getFieldAgents({
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
    String? search,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (date != null) params['date'] = _apiDate(date);
      if (dateStart != null) params['date_start'] = _apiDate(dateStart);
      if (dateEnd != null) params['date_end'] = _apiDate(dateEnd);
      if (period != null) params['period'] = period;
      if (search != null && search.trim().isNotEmpty) params['search'] = search.trim();

      final response = await _api.dio.get(
        ApiConstants.fieldAgents,
        queryParameters: params.isEmpty ? null : params,
      );
      final data = response.data;
      if (data['success'] == true) {
        final result = AgencyFieldTeamResponse.fromJson(data as Map<String, dynamic>);
        await SyncQueueService.cacheFieldAgents(result);
        return result;
      }
      return AgencyFieldTeamResponse(
        date: _apiDate(date ?? DateTime.now()),
        summary: const AgencyFieldSummary(),
        agents: const [],
      );
    } on DioException catch (e) {
      final cached = await SyncQueueService.getCachedFieldAgents();
      if (cached != null) {
        return cached;
      }
      final msg = e.response?.data?['message'] ?? 'Erreur de connexion lors du chargement des agents.';
      throw Exception(msg);
    } catch (e) {
      final cached = await SyncQueueService.getCachedFieldAgents();
      if (cached != null) {
        return cached;
      }
      throw Exception('Erreur lors du chargement des agents de terrain : $e');
    }
  }

  @override
  Future<FieldAgentDetailResponse> getFieldAgentDetails(
    int agentId, {
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (date != null) params['date'] = _apiDate(date);
      if (dateStart != null) params['date_start'] = _apiDate(dateStart);
      if (dateEnd != null) params['date_end'] = _apiDate(dateEnd);
      if (period != null) params['period'] = period;

      final response = await _api.dio.get(
        ApiConstants.fieldAgentDetails(agentId),
        queryParameters: params.isEmpty ? null : params,
      );
      final data = response.data;
      if (data['success'] == true) {
        return FieldAgentDetailResponse.fromJson(data as Map<String, dynamic>);
      }
      throw Exception(data['message'] ?? 'Impossible de charger les détails de l\'agent.');
    } on DioException catch (e) {
      final msg = e.response?.data?['message'] ?? 'Erreur lors du chargement des détails de l\'agent.';
      throw Exception(msg);
    } catch (e) {
      throw Exception(e.toString());
    }
  }

  String _apiDate(DateTime date) {
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '${date.year}-$month-$day';
  }
}

