class CashierSessionModel {
  final int? id;
  final bool hasOpenSession;
  final String status;
  final DateTime? openedAt;
  final DateTime? closedAt;
  final double openingBalance;
  final double closingBalance;
  final double expectedClosingBalance;
  final double totalDeposits;
  final double totalWithdrawals;
  final int transactionsCount;
  final String? notes;

  const CashierSessionModel({
    this.id,
    required this.hasOpenSession,
    required this.status,
    this.openedAt,
    this.closedAt,
    this.openingBalance = 0.0,
    this.closingBalance = 0.0,
    this.expectedClosingBalance = 0.0,
    this.totalDeposits = 0.0,
    this.totalWithdrawals = 0.0,
    this.transactionsCount = 0,
    this.notes,
  });

  bool get isOpen => status == 'open';

  factory CashierSessionModel.fromJson(Map<String, dynamic> json) {
    final session = json['session'] is Map<String, dynamic> ? json['session'] as Map<String, dynamic> : json;
    final statusStr = session['status']?.toString() ?? json['status']?.toString() ?? (json['has_open_session'] == true ? 'open' : 'closed');
    final hasOpen = json['has_open_session'] == true || statusStr == 'open';

    return CashierSessionModel(
      id: session['id'] is int ? session['id'] : int.tryParse(session['id']?.toString() ?? json['id']?.toString() ?? ''),
      hasOpenSession: hasOpen,
      status: statusStr,
      openedAt: session['opened_at'] != null
          ? DateTime.tryParse(session['opened_at'].toString())
          : (json['opened_at'] != null ? DateTime.tryParse(json['opened_at'].toString()) : null),
      closedAt: session['closed_at'] != null
          ? DateTime.tryParse(session['closed_at'].toString())
          : (json['closed_at'] != null ? DateTime.tryParse(json['closed_at'].toString()) : null),
      openingBalance: double.tryParse(session['opening_balance']?.toString() ?? json['opening_balance']?.toString() ?? '0') ?? 0.0,
      closingBalance: double.tryParse(session['closing_balance']?.toString() ?? json['closing_balance']?.toString() ?? '0') ?? 0.0,
      expectedClosingBalance: double.tryParse(session['expected_closing_balance']?.toString() ?? json['expected_closing_balance']?.toString() ?? '0') ?? 0.0,
      totalDeposits: double.tryParse(session['total_deposits']?.toString() ?? json['total_deposits']?.toString() ?? '0') ?? 0.0,
      totalWithdrawals: double.tryParse(session['total_withdrawals']?.toString() ?? json['total_withdrawals']?.toString() ?? '0') ?? 0.0,
      transactionsCount: int.tryParse(json['transactions_count']?.toString() ?? session['transactions_count']?.toString() ?? '0') ?? 0,
      notes: session['notes']?.toString() ?? json['notes']?.toString(),
    );
  }

  factory CashierSessionModel.closed() => const CashierSessionModel(
        hasOpenSession: false,
        status: 'closed',
      );

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'has_open_session': hasOpenSession,
      'status': status,
      'opened_at': openedAt?.toIso8601String(),
      'closed_at': closedAt?.toIso8601String(),
      'opening_balance': openingBalance,
      'closing_balance': closingBalance,
      'expected_closing_balance': expectedClosingBalance,
      'total_deposits': totalDeposits,
      'total_withdrawals': totalWithdrawals,
      'transactions_count': transactionsCount,
      'notes': notes,
    };
  }
}
