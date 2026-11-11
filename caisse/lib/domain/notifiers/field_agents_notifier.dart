import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/models/field_agent_model.dart';
import '../../data/repositories/cashier_repository.dart';
import '../../domain/repositories/i_cashier_repository.dart';

class FieldAgentsState {
  final DateTime selectedDate;
  final DateTime? startDate;
  final DateTime? endDate;
  final String period;
  final String searchQuery;
  final bool isLoading;
  final String? errorMessage;
  final AgencyFieldTeamResponse? teamData;
  final bool isDetailLoading;
  final FieldAgentDetailResponse? agentDetail;
  final String? detailErrorMessage;

  const FieldAgentsState({
    required this.selectedDate,
    this.startDate,
    this.endDate,
    this.period = 'today',
    this.searchQuery = '',
    this.isLoading = false,
    this.errorMessage,
    this.teamData,
    this.isDetailLoading = false,
    this.agentDetail,
    this.detailErrorMessage,
  });

  bool get isRangeMode => startDate != null && endDate != null && (startDate != endDate);

  FieldAgentsState copyWith({
    DateTime? selectedDate,
    DateTime? startDate,
    bool clearStartDate = false,
    DateTime? endDate,
    bool clearEndDate = false,
    String? period,
    String? searchQuery,
    bool? isLoading,
    String? errorMessage,
    bool clearError = false,
    AgencyFieldTeamResponse? teamData,
    bool? isDetailLoading,
    FieldAgentDetailResponse? agentDetail,
    String? detailErrorMessage,
    bool clearDetailError = false,
  }) {
    return FieldAgentsState(
      selectedDate: selectedDate ?? this.selectedDate,
      startDate: clearStartDate ? null : (startDate ?? this.startDate),
      endDate: clearEndDate ? null : (endDate ?? this.endDate),
      period: period ?? this.period,
      searchQuery: searchQuery ?? this.searchQuery,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      teamData: teamData ?? this.teamData,
      isDetailLoading: isDetailLoading ?? this.isDetailLoading,
      agentDetail: agentDetail ?? this.agentDetail,
      detailErrorMessage: clearDetailError ? null : (detailErrorMessage ?? this.detailErrorMessage),
    );
  }
}

class FieldAgentsNotifier extends StateNotifier<FieldAgentsState> {
  final ICashierRepository _repository;

  FieldAgentsNotifier(this._repository)
      : super(FieldAgentsState(selectedDate: DateTime.now()));

  Future<void> fetchFieldAgents({
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
    String? search,
  }) async {
    final targetDate = date ?? state.selectedDate;
    final targetStart = dateStart ?? state.startDate;
    final targetEnd = dateEnd ?? state.endDate;
    final targetPeriod = period ?? state.period;
    final targetSearch = search ?? state.searchQuery;

    state = state.copyWith(
      isLoading: true,
      clearError: true,
      selectedDate: targetDate,
      startDate: targetStart,
      endDate: targetEnd,
      period: targetPeriod,
      searchQuery: targetSearch,
    );

    try {
      final res = await _repository.getFieldAgents(
        date: (targetStart == null && targetEnd == null) ? targetDate : null,
        dateStart: targetStart,
        dateEnd: targetEnd,
        period: targetPeriod,
        search: targetSearch.isNotEmpty ? targetSearch : null,
      );
      state = state.copyWith(
        isLoading: false,
        teamData: res,
        clearError: true,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception:', '').trim(),
      );
    }
  }

  void changeDate(DateTime newDate) {
    state = state.copyWith(
      selectedDate: newDate,
      clearStartDate: true,
      clearEndDate: true,
      period: 'custom',
    );
    fetchFieldAgents(date: newDate, dateStart: null, dateEnd: null, period: 'custom');
  }

  void changeDateRange(DateTime start, DateTime end) {
    state = state.copyWith(
      selectedDate: start,
      startDate: start,
      endDate: end,
      period: 'custom_range',
    );
    fetchFieldAgents(dateStart: start, dateEnd: end, period: 'custom_range');
  }

  void changePeriod(String period) {
    state = state.copyWith(
      period: period,
      clearStartDate: true,
      clearEndDate: true,
    );
    fetchFieldAgents(period: period, dateStart: null, dateEnd: null);
  }

  void search(String query) {
    state = state.copyWith(searchQuery: query);
    fetchFieldAgents(search: query);
  }

  Future<void> fetchAgentDetail(
    int agentId, {
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
  }) async {
    final targetDate = date ?? state.selectedDate;
    final targetStart = dateStart ?? state.startDate;
    final targetEnd = dateEnd ?? state.endDate;
    final targetPeriod = period ?? state.period;

    state = state.copyWith(
      isDetailLoading: true,
      clearDetailError: true,
    );

    try {
      final res = await _repository.getFieldAgentDetails(
        agentId,
        date: (targetStart == null && targetEnd == null) ? targetDate : null,
        dateStart: targetStart,
        dateEnd: targetEnd,
        period: targetPeriod,
      );
      state = state.copyWith(
        isDetailLoading: false,
        agentDetail: res,
        clearDetailError: true,
      );
    } catch (e) {
      state = state.copyWith(
        isDetailLoading: false,
        detailErrorMessage: e.toString().replaceAll('Exception:', '').trim(),
      );
    }
  }
}

final fieldAgentsNotifierProvider =
    StateNotifierProvider<FieldAgentsNotifier, FieldAgentsState>((ref) {
  final repository = CashierRepository();
  return FieldAgentsNotifier(repository);
});
