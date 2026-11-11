import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/repository_providers.dart';
import '../../core/services/sync_queue_service.dart';
import '../../data/models/loan_model.dart';
import '../../data/models/client_eligibility_model.dart';
import '../repositories/i_cashier_repository.dart';
import 'cashier_notifier.dart';

class LoanState {
  final List<LoanModel> loans;
  final LoanMetricsModel metrics;
  final bool isLoading;
  final String statusFilter; // 'all', 'disbursed', 'pending', 'approved', 'completed', 'overdue'
  final String searchQuery;
  final String? errorMessage;

  const LoanState({
    this.loans = const [],
    this.metrics = const LoanMetricsModel(),
    this.isLoading = false,
    this.statusFilter = 'all',
    this.searchQuery = '',
    this.errorMessage,
  });

  LoanState copyWith({
    List<LoanModel>? loans,
    LoanMetricsModel? metrics,
    bool? isLoading,
    String? statusFilter,
    String? searchQuery,
    String? errorMessage,
    bool clearError = false,
  }) {
    return LoanState(
      loans: loans ?? this.loans,
      metrics: metrics ?? this.metrics,
      isLoading: isLoading ?? this.isLoading,
      statusFilter: statusFilter ?? this.statusFilter,
      searchQuery: searchQuery ?? this.searchQuery,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}

class LoanNotifier extends StateNotifier<LoanState> {
  final ICashierRepository _cashierRepository;
  final Ref _ref;

  LoanNotifier(this._cashierRepository, this._ref) : super(const LoanState());

  Future<void> fetchLoans({String? status, String? search}) async {
    state = state.copyWith(
      isLoading: true,
      statusFilter: status ?? state.statusFilter,
      searchQuery: search ?? state.searchQuery,
      clearError: true,
    );
    try {
      final res = await _cashierRepository.getLoans(
        status: status ?? state.statusFilter,
        search: search ?? state.searchQuery,
      );
      state = state.copyWith(
        loans: res.loans,
        metrics: res.metrics,
        isLoading: false,
        clearError: true,
      );
      // Mettre en cache local lorsque la liste globale est récupérée
      if ((search == null || search.isEmpty) && (status == null || status == 'all')) {
        SyncQueueService.cacheLoans(res.loans, res.metrics);
      }
    } catch (e) {
      // Fallback vers les prêts et métriques en cache local hors-ligne
      final cachedLoans = await SyncQueueService.getCachedLoans();
      final cachedMetrics = await SyncQueueService.getCachedLoanMetrics();
      if (cachedLoans.isNotEmpty) {
        var filtered = cachedLoans;
        final targetStatus = status ?? state.statusFilter;
        if (targetStatus != 'all') {
          filtered = filtered.where((l) => l.status == targetStatus).toList();
        }
        final query = search ?? state.searchQuery;
        if (query.isNotEmpty) {
          final q = query.toLowerCase();
          filtered = filtered.where((l) {
            final clientName = l.client?.fullName.toLowerCase() ?? '';
            return l.loanNumber.toLowerCase().contains(q) || clientName.contains(q);
          }).toList();
        }
        state = state.copyWith(
          loans: filtered,
          metrics: cachedMetrics ?? state.metrics,
          isLoading: false,
          clearError: true,
        );
        return;
      }
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isLoading: false, errorMessage: msg);
    }
  }

  Future<Map<String, dynamic>> getLoanConfig() async {
    return await _cashierRepository.getLoanConfig();
  }

  Future<ClientEligibilityModel?> checkClientEligibility(int clientId) async {
    try {
      return await _cashierRepository.getClientLoanEligibility(clientId);
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(errorMessage: msg);
      return null;
    }
  }

  Future<Map<String, dynamic>?> simulateLoan({
    required double amount,
    required int durationMonths,
    double? interestRate,
  }) async {
    try {
      return await _cashierRepository.simulateLoan(
        amount: amount,
        durationMonths: durationMonths,
        interestRate: interestRate,
      );
    } catch (e) {
      return null;
    }
  }

  Future<LoanModel?> applyLoan({
    required int clientId,
    required double requestedAmount,
    required int durationMonths,
    String? purpose,
    String? collateralDescription,
    double? interestRate,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final loan = await _cashierRepository.applyLoan(
        clientId: clientId,
        requestedAmount: requestedAmount,
        durationMonths: durationMonths,
        purpose: purpose,
        collateralDescription: collateralDescription,
        interestRate: interestRate,
      );
      await fetchLoans();
      return loan;
    } catch (e) {
      // Mode hors-ligne : création locale optimiste et mise en file d'attente
      final tempId = -DateTime.now().millisecondsSinceEpoch;
      final tempLoanNumber = 'PRET-OFF-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}';
      final rate = interestRate ?? 17.0;
      final totalAmount = requestedAmount + (requestedAmount * (rate / 100));

      final localLoan = LoanModel(
        id: tempId,
        loanNumber: tempLoanNumber,
        clientId: clientId,
        requestedAmount: requestedAmount,
        approvedAmount: requestedAmount,
        interestRate: rate,
        durationMonths: durationMonths,
        totalAmountDue: totalAmount,
        outstandingPrincipal: requestedAmount,
        totalPaid: 0,
        status: 'pending',
        purpose: purpose,
        collateralDescription: collateralDescription,
        applicationDate: DateTime.now(),
      );

      final updatedLoans = <LoanModel>[localLoan, ...state.loans];
      state = state.copyWith(loans: updatedLoans, isLoading: false, clearError: true);
      SyncQueueService.cacheLoans(updatedLoans, state.metrics);

      await SyncQueueService.addOfflineTransaction({
        'id': 'OFF-LOAN-${DateTime.now().millisecondsSinceEpoch}',
        'action': 'apply_loan',
        'type': 'apply_loan',
        'client_id': clientId,
        'requested_amount': requestedAmount,
        'duration_months': durationMonths,
        'purpose': purpose,
        'collateral_description': collateralDescription,
        'interest_rate': interestRate,
        'offline_created_at': DateTime.now().toIso8601String(),
      });

      return localLoan;
    }
  }

  Future<LoanModel?> approveLoan({
    required int loanId,
    double? approvedAmount,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final loan = await _cashierRepository.approveLoan(
        loanId: loanId,
        approvedAmount: approvedAmount,
        notes: notes,
      );
      await fetchLoans();
      return loan;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isLoading: false, errorMessage: msg);
      return null;
    }
  }

  Future<Map<String, dynamic>?> disburseLoan({
    required int loanId,
    String? paymentMethod,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final result = await _cashierRepository.disburseLoan(
        loanId: loanId,
        paymentMethod: paymentMethod,
        notes: notes,
      );
      await fetchLoans();
      return result;
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(isLoading: false, errorMessage: msg);
      return null;
    }
  }

  Future<Map<String, dynamic>?> repayLoan({
    required int loanId,
    required double amount,
    String? paymentMethod,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final result = await _cashierRepository.repayLoan(
        loanId: loanId,
        amount: amount,
        paymentMethod: paymentMethod,
        notes: notes,
      );
      await fetchLoans();
      return result;
    } catch (e) {
      // Mode hors-ligne : mise à jour locale du prêt et enregistrement dans la file
      final updatedLoans = state.loans.map<LoanModel>((l) {
        if (l.id == loanId) {
          final newPaid = l.totalPaid + amount;
          final totalDue = l.effectiveTotalDue;
          final isCompleted = newPaid >= totalDue;
          return l.copyWith(
            totalPaid: newPaid,
            status: isCompleted ? 'completed' : l.status,
          );
        }
        return l;
      }).toList();

      state = state.copyWith(loans: updatedLoans, isLoading: false, clearError: true);
      SyncQueueService.cacheLoans(updatedLoans, state.metrics);

      // Met à jour la caisse locale (entrée d'argent)
      _ref.read(cashierNotifierProvider.notifier).recordLocalOfflineTransaction(
            type: 'loan_repayment',
            amount: amount,
            loanId: loanId,
          );

      final offlineId = 'OFF-REM-${DateTime.now().millisecondsSinceEpoch}';
      await SyncQueueService.addOfflineTransaction({
        'id': offlineId,
        'offline_id': offlineId,
        'action': 'loan_repayment',
        'type': 'loan_repayment',
        'transaction_type': 'loan_repayment',
        'loan_id': loanId,
        'amount': amount,
        'payment_method': paymentMethod ?? 'cash',
        'description': notes ?? 'Remboursement de prêt hors-ligne',
        'offline_created_at': DateTime.now().toIso8601String(),
      });

      return {
        'success': true,
        'message': 'Remboursement enregistré hors-ligne',
        'is_offline': true,
        'transaction_id': offlineId,
        'amount': amount,
      };
    }
  }

  Future<List<LoanPaymentModel>> getLoanSchedule(int loanId) async {
    return _cashierRepository.getLoanSchedule(loanId);
  }
}

final loanNotifierProvider = StateNotifierProvider<LoanNotifier, LoanState>((ref) {
  final cashierRepository = ref.watch(cashierRepositoryProvider);
  return LoanNotifier(cashierRepository, ref);
});
