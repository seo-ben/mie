import 'client_model.dart';

class LoanModel {
  final int id;
  final String loanNumber;
  final int clientId;
  final ClientModel? client;
  final double requestedAmount;
  final double approvedAmount;
  final double interestRate;
  final int durationMonths;
  final double monthlyPayment;
  final double totalAmountDue;
  final double outstandingPrincipal;
  final double outstandingInterest;
  final double totalPaid;
  final double penaltyAmount;
  final int daysOverdue;
  final String status;
  final String? purpose;
  final String? collateralDescription;
  final DateTime? applicationDate;
  final DateTime? approvedAt;
  final DateTime? disbursedAt;
  final DateTime? maturityDate;
  final String? disbursementMethod;
  final String? disbursementReference;

  const LoanModel({
    required this.id,
    required this.loanNumber,
    required this.clientId,
    this.client,
    required this.requestedAmount,
    required this.approvedAmount,
    this.interestRate = 12.0,
    this.durationMonths = 6,
    this.monthlyPayment = 0.0,
    this.totalAmountDue = 0.0,
    this.outstandingPrincipal = 0.0,
    this.outstandingInterest = 0.0,
    this.totalPaid = 0.0,
    this.penaltyAmount = 0.0,
    this.daysOverdue = 0,
    required this.status,
    this.purpose,
    this.collateralDescription,
    this.applicationDate,
    this.approvedAt,
    this.disbursedAt,
    this.maturityDate,
    this.disbursementMethod,
    this.disbursementReference,
  });

  bool get isPending => status == 'pending' || status == 'under_review';
  bool get isApproved => status == 'approved';
  bool get isDisbursed => status == 'disbursed' || status == 'active';
  bool get isCompleted => status == 'completed' || status == 'repaid';
  bool get isRejected => status == 'rejected';
  bool get isOverdue => isDisbursed && daysOverdue > 0;

  double get effectiveAmount => approvedAmount > 0 ? approvedAmount : requestedAmount;
  double get effectiveTotalDue => totalAmountDue > 0 ? totalAmountDue : effectiveAmount;
  double get remainingDue {
    final rem = effectiveTotalDue - totalPaid;
    return rem > 0 ? rem : 0.0;
  }

  double get repaymentProgress {
    if (effectiveTotalDue <= 0) return 0.0;
    final p = totalPaid / effectiveTotalDue;
    return p.clamp(0.0, 1.0);
  }

  factory LoanModel.fromJson(Map<String, dynamic> json) {
    return LoanModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      loanNumber: json['loan_number']?.toString() ?? 'PRT-N/A',
      clientId: json['client_id'] is int ? json['client_id'] : int.tryParse(json['client_id']?.toString() ?? '0') ?? 0,
      client: json['client'] is Map<String, dynamic> ? ClientModel.fromJson(json['client'] as Map<String, dynamic>) : null,
      requestedAmount: double.tryParse(json['requested_amount']?.toString() ?? '0') ?? 0.0,
      approvedAmount: double.tryParse(json['approved_amount']?.toString() ?? json['requested_amount']?.toString() ?? '0') ?? 0.0,
      interestRate: double.tryParse(json['interest_rate']?.toString() ?? '17') ?? 17.0,
      durationMonths: int.tryParse(json['duration_months']?.toString() ?? '6') ?? 6,
      monthlyPayment: double.tryParse(json['monthly_payment']?.toString() ?? '0') ?? 0.0,
      totalAmountDue: double.tryParse(json['total_amount_due']?.toString() ?? '0') ?? 0.0,
      outstandingPrincipal: double.tryParse(json['outstanding_principal']?.toString() ?? '0') ?? 0.0,
      outstandingInterest: double.tryParse(json['outstanding_interest']?.toString() ?? '0') ?? 0.0,
      totalPaid: double.tryParse(json['total_paid']?.toString() ?? '0') ?? 0.0,
      penaltyAmount: double.tryParse(json['penalty_amount']?.toString() ?? '0') ?? 0.0,
      daysOverdue: int.tryParse(json['days_overdue']?.toString() ?? '0') ?? 0,
      status: json['status']?.toString() ?? 'pending',
      purpose: json['purpose']?.toString(),
      collateralDescription: json['collateral_description']?.toString(),
      applicationDate: json['application_date'] != null ? DateTime.tryParse(json['application_date'].toString()) : null,
      approvedAt: json['approved_at'] != null ? DateTime.tryParse(json['approved_at'].toString()) : null,
      disbursedAt: json['disbursed_at'] != null ? DateTime.tryParse(json['disbursed_at'].toString()) : null,
      maturityDate: json['maturity_date'] != null ? DateTime.tryParse(json['maturity_date'].toString()) : null,
      disbursementMethod: json['disbursement_method']?.toString(),
      disbursementReference: json['disbursement_reference']?.toString(),
    );
  }

  LoanModel copyWith({
    int? id,
    String? loanNumber,
    int? clientId,
    ClientModel? client,
    double? requestedAmount,
    double? approvedAmount,
    double? interestRate,
    int? durationMonths,
    double? monthlyPayment,
    double? totalAmountDue,
    double? outstandingPrincipal,
    double? outstandingInterest,
    double? totalPaid,
    double? penaltyAmount,
    int? daysOverdue,
    String? status,
    String? purpose,
    String? collateralDescription,
    DateTime? applicationDate,
    DateTime? approvedAt,
    DateTime? disbursedAt,
    DateTime? maturityDate,
    String? disbursementMethod,
    String? disbursementReference,
  }) {
    return LoanModel(
      id: id ?? this.id,
      loanNumber: loanNumber ?? this.loanNumber,
      clientId: clientId ?? this.clientId,
      client: client ?? this.client,
      requestedAmount: requestedAmount ?? this.requestedAmount,
      approvedAmount: approvedAmount ?? this.approvedAmount,
      interestRate: interestRate ?? this.interestRate,
      durationMonths: durationMonths ?? this.durationMonths,
      monthlyPayment: monthlyPayment ?? this.monthlyPayment,
      totalAmountDue: totalAmountDue ?? this.totalAmountDue,
      outstandingPrincipal: outstandingPrincipal ?? this.outstandingPrincipal,
      outstandingInterest: outstandingInterest ?? this.outstandingInterest,
      totalPaid: totalPaid ?? this.totalPaid,
      penaltyAmount: penaltyAmount ?? this.penaltyAmount,
      daysOverdue: daysOverdue ?? this.daysOverdue,
      status: status ?? this.status,
      purpose: purpose ?? this.purpose,
      collateralDescription: collateralDescription ?? this.collateralDescription,
      applicationDate: applicationDate ?? this.applicationDate,
      approvedAt: approvedAt ?? this.approvedAt,
      disbursedAt: disbursedAt ?? this.disbursedAt,
      maturityDate: maturityDate ?? this.maturityDate,
      disbursementMethod: disbursementMethod ?? this.disbursementMethod,
      disbursementReference: disbursementReference ?? this.disbursementReference,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'loan_number': loanNumber,
      'client_id': clientId,
      'client': client?.toJson(),
      'requested_amount': requestedAmount,
      'approved_amount': approvedAmount,
      'interest_rate': interestRate,
      'duration_months': durationMonths,
      'monthly_payment': monthlyPayment,
      'total_amount_due': totalAmountDue,
      'outstanding_principal': outstandingPrincipal,
      'outstanding_interest': outstandingInterest,
      'total_paid': totalPaid,
      'penalty_amount': penaltyAmount,
      'days_overdue': daysOverdue,
      'status': status,
      'purpose': purpose,
      'collateral_description': collateralDescription,
      'application_date': applicationDate?.toIso8601String(),
      'approved_at': approvedAt?.toIso8601String(),
      'disbursed_at': disbursedAt?.toIso8601String(),
      'maturity_date': maturityDate?.toIso8601String(),
      'disbursement_method': disbursementMethod,
      'disbursement_reference': disbursementReference,
    };
  }
}

class LoanPaymentModel {
  final int id;
  final int loanId;
  final int paymentNumber;
  final String dueDate;
  final String? paidDate;
  final double expectedAmount;
  final double principalAmount;
  final double interestAmount;
  final double paidAmount;
  final double penaltyAmount;
  final String status;
  final int daysOverdue;

  const LoanPaymentModel({
    required this.id,
    required this.loanId,
    required this.paymentNumber,
    required this.dueDate,
    this.paidDate,
    required this.expectedAmount,
    this.principalAmount = 0.0,
    this.interestAmount = 0.0,
    this.paidAmount = 0.0,
    this.penaltyAmount = 0.0,
    required this.status,
    this.daysOverdue = 0,
  });

  bool get isPaid => status == 'paid';
  bool get isPartial => status == 'partial';
  bool get isPending => status == 'pending';
  bool get isOverdue => status == 'overdue' || daysOverdue > 0;

  factory LoanPaymentModel.fromJson(Map<String, dynamic> json) {
    return LoanPaymentModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      loanId: json['loan_id'] is int ? json['loan_id'] : int.tryParse(json['loan_id']?.toString() ?? '0') ?? 0,
      paymentNumber: int.tryParse(json['payment_number']?.toString() ?? '1') ?? 1,
      dueDate: json['due_date']?.toString() ?? '',
      paidDate: json['paid_date']?.toString(),
      expectedAmount: double.tryParse(json['expected_amount']?.toString() ?? '0') ?? 0.0,
      principalAmount: double.tryParse(json['principal_amount']?.toString() ?? '0') ?? 0.0,
      interestAmount: double.tryParse(json['interest_amount']?.toString() ?? '0') ?? 0.0,
      paidAmount: double.tryParse(json['paid_amount']?.toString() ?? '0') ?? 0.0,
      penaltyAmount: double.tryParse(json['penalty_amount']?.toString() ?? '0') ?? 0.0,
      status: json['status']?.toString() ?? 'pending',
      daysOverdue: int.tryParse(json['days_overdue']?.toString() ?? '0') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'loan_id': loanId,
      'payment_number': paymentNumber,
      'due_date': dueDate,
      'paid_date': paidDate,
      'expected_amount': expectedAmount,
      'principal_amount': principalAmount,
      'interest_amount': interestAmount,
      'paid_amount': paidAmount,
      'penalty_amount': penaltyAmount,
      'status': status,
      'days_overdue': daysOverdue,
    };
  }
}

class LoanMetricsModel {
  final double totalDisbursed;
  final double totalRepaid;
  final double totalOutstanding;
  final int activeLoansCount;
  final int pendingLoansCount;
  final double repaymentRate;

  const LoanMetricsModel({
    this.totalDisbursed = 0.0,
    this.totalRepaid = 0.0,
    this.totalOutstanding = 0.0,
    this.activeLoansCount = 0,
    this.pendingLoansCount = 0,
    this.repaymentRate = 0.0,
  });

  factory LoanMetricsModel.fromJson(Map<String, dynamic> json) {
    return LoanMetricsModel(
      totalDisbursed: double.tryParse(json['total_disbursed']?.toString() ?? '0') ?? 0.0,
      totalRepaid: double.tryParse(json['total_repaid']?.toString() ?? '0') ?? 0.0,
      totalOutstanding: double.tryParse(json['total_outstanding']?.toString() ?? '0') ?? 0.0,
      activeLoansCount: int.tryParse(json['active_loans_count']?.toString() ?? '0') ?? 0,
      pendingLoansCount: int.tryParse(json['pending_loans_count']?.toString() ?? '0') ?? 0,
      repaymentRate: double.tryParse(json['repayment_rate']?.toString() ?? '0') ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'total_disbursed': totalDisbursed,
      'total_repaid': totalRepaid,
      'total_outstanding': totalOutstanding,
      'active_loans_count': activeLoansCount,
      'pending_loans_count': pendingLoansCount,
      'repayment_rate': repaymentRate,
    };
  }
}
