import '../../data/models/transaction_model.dart';

class DashboardStats {
  final double openingBalance;
  final double todayTotalIn;
  final double todayTotalOut;
  final double cashBalance;
  final int todayTxCount;
  final int agencyClientCount;
  final List<TransactionModel> recentTransactions;

  const DashboardStats({
    this.openingBalance = 0.0,
    this.todayTotalIn = 0.0,
    this.todayTotalOut = 0.0,
    this.cashBalance = 0.0,
    this.todayTxCount = 0,
    this.agencyClientCount = 0,
    this.recentTransactions = const [],
  });

  factory DashboardStats.empty() => const DashboardStats();

  factory DashboardStats.fromApiResponse(Map<String, dynamic> d) {
    final stats = d['stats'] ?? d['daily_stats'] ?? {};
    final overview = d['overview'] ?? {};
    final collections = d['collections'] ?? {};
    final clients = d['clients'] ?? {};
    final session = d['session'] ?? {};

    final opening = double.tryParse(
          session['opening_balance']?.toString() ??
              stats['opening_balance']?.toString() ??
              '0',
        ) ??
        0.0;

    final totalIn = double.tryParse(
          collections['total_collected']?.toString() ??
              stats['total_deposits']?.toString() ??
              stats['total_in']?.toString() ??
              '0',
        ) ??
        0.0;

    final totalOut = double.tryParse(
          collections['total_withdrawals']?.toString() ??
              stats['total_withdrawals']?.toString() ??
              stats['total_out']?.toString() ??
              '0',
        ) ??
        0.0;

    final isOpen = session['status'] == 'open';
    final explicitClosingCash = double.tryParse(session['cash_balance']?.toString() ?? session['closing_balance']?.toString() ?? '');

    final calculatedCash = opening + totalIn - totalOut;
    final explicitCash = double.tryParse(
      session['expected_closing_balance']?.toString() ??
          stats['cash_in_hand']?.toString() ??
          stats['balance']?.toString() ??
          '',
    );

    // If session is closed, use the verified closed cash balance
    final cash = (!isOpen && explicitClosingCash != null)
        ? explicitClosingCash
        : ((explicitCash != null && explicitCash != 0.0) ? explicitCash : calculatedCash);

    final txCount = int.tryParse(
          collections['transaction_count']?.toString() ??
              collections['count']?.toString() ??
              stats['transactions_count']?.toString() ??
              stats['tx_count']?.toString() ??
              '0',
        ) ??
        0;

    final clientCount = int.tryParse(
          clients['total']?.toString() ??
              overview['total_clients']?.toString() ??
              '0',
        ) ??
        0;

    List<TransactionModel> recent = [];
    final recentData = collections['recent'] ?? d['recent_transactions'];
    if (recentData is List) {
      recent = recentData
          .map((tx) => TransactionModel.fromJson(tx as Map<String, dynamic>))
          .toList();
    }

    return DashboardStats(
      openingBalance: opening,
      todayTotalIn: totalIn,
      todayTotalOut: totalOut,
      cashBalance: cash,
      todayTxCount: txCount,
      agencyClientCount: clientCount,
      recentTransactions: recent,
    );
  }

  DashboardStats copyWith({
    double? openingBalance,
    double? todayTotalIn,
    double? todayTotalOut,
    double? cashBalance,
    int? todayTxCount,
    int? agencyClientCount,
    List<TransactionModel>? recentTransactions,
  }) {
    return DashboardStats(
      openingBalance: openingBalance ?? this.openingBalance,
      todayTotalIn: todayTotalIn ?? this.todayTotalIn,
      todayTotalOut: todayTotalOut ?? this.todayTotalOut,
      cashBalance: cashBalance ?? this.cashBalance,
      todayTxCount: todayTxCount ?? this.todayTxCount,
      agencyClientCount: agencyClientCount ?? this.agencyClientCount,
      recentTransactions: recentTransactions ?? this.recentTransactions,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'opening_balance': openingBalance,
      'today_total_in': todayTotalIn,
      'today_total_out': todayTotalOut,
      'cash_balance': cashBalance,
      'today_tx_count': todayTxCount,
      'agency_client_count': agencyClientCount,
      'recent_transactions': recentTransactions.map((tx) => tx.toJson()).toList(),
    };
  }

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    List<TransactionModel> recent = [];
    if (json['recent_transactions'] is List) {
      recent = (json['recent_transactions'] as List)
          .map((tx) => TransactionModel.fromJson(tx as Map<String, dynamic>))
          .toList();
    }
    return DashboardStats(
      openingBalance: double.tryParse(json['opening_balance']?.toString() ?? '0') ?? 0.0,
      todayTotalIn: double.tryParse(json['today_total_in']?.toString() ?? '0') ?? 0.0,
      todayTotalOut: double.tryParse(json['today_total_out']?.toString() ?? '0') ?? 0.0,
      cashBalance: double.tryParse(json['cash_balance']?.toString() ?? '0') ?? 0.0,
      todayTxCount: int.tryParse(json['today_tx_count']?.toString() ?? '0') ?? 0,
      agencyClientCount: int.tryParse(json['agency_client_count']?.toString() ?? '0') ?? 0,
      recentTransactions: recent,
    );
  }
}
