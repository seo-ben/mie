class EligibilityCriterion {
  final String label;
  final bool passed;

  const EligibilityCriterion({required this.label, required this.passed});

  factory EligibilityCriterion.fromJson(Map<String, dynamic> json) {
    return EligibilityCriterion(
      label: json['label']?.toString() ?? '',
      passed: json['passed'] == true,
    );
  }
}

class ClientEligibilityModel {
  final int clientId;
  final String clientName;
  final String phone;
  final String kycStatus;
  final bool isEligible;
  final double eligibilityScore;
  final String riskLevel;
  final double maxBorrowingCapacity;
  final double savingsBalance;
  final double tontineBalance;
  final double totalAssets;
  final bool activeLoanExists;
  final int completedLoansCount;
  final int defaultedLoansCount;
  final double suggestedInterestRate;
  final double defaultInterestRate;
  final List<EligibilityCriterion> criteria;

  const ClientEligibilityModel({
    required this.clientId,
    required this.clientName,
    required this.phone,
    required this.kycStatus,
    required this.isEligible,
    required this.eligibilityScore,
    required this.riskLevel,
    required this.maxBorrowingCapacity,
    this.suggestedInterestRate = 17.0,
    this.defaultInterestRate = 17.0,
    this.savingsBalance = 0.0,
    this.tontineBalance = 0.0,
    this.totalAssets = 0.0,
    this.activeLoanExists = false,
    this.completedLoansCount = 0,
    this.defaultedLoansCount = 0,
    this.criteria = const [],
  });

  factory ClientEligibilityModel.fromJson(Map<String, dynamic> json) {
    final client = json['client'] as Map<String, dynamic>? ?? {};
    final criteriaList = (json['criteria'] as List<dynamic>?)
            ?.map((c) => EligibilityCriterion.fromJson(c as Map<String, dynamic>))
            .toList() ??
        [];

    return ClientEligibilityModel(
      clientId: client['id'] is int ? client['id'] : int.tryParse(client['id']?.toString() ?? '0') ?? 0,
      clientName: client['full_name']?.toString() ?? 'Client',
      phone: client['phone']?.toString() ?? '',
      kycStatus: client['kyc_status']?.toString() ?? 'pending',
      isEligible: json['is_eligible'] == true,
      eligibilityScore: double.tryParse(json['eligibility_score']?.toString() ?? '0') ?? 0.0,
      riskLevel: json['risk_level']?.toString() ?? 'medium',
      maxBorrowingCapacity: double.tryParse(json['max_borrowing_capacity']?.toString() ?? '0') ?? 0.0,
      suggestedInterestRate: double.tryParse(json['suggested_interest_rate']?.toString() ?? '17.0') ?? 17.0,
      defaultInterestRate: double.tryParse(json['default_interest_rate']?.toString() ?? '17.0') ?? 17.0,
      savingsBalance: double.tryParse(json['savings_balance']?.toString() ?? '0') ?? 0.0,
      tontineBalance: double.tryParse(json['tontine_balance']?.toString() ?? '0') ?? 0.0,
      totalAssets: double.tryParse(json['total_assets']?.toString() ?? '0') ?? 0.0,
      activeLoanExists: json['active_loan_exists'] == true,
      completedLoansCount: int.tryParse(json['completed_loans_count']?.toString() ?? '0') ?? 0,
      defaultedLoansCount: int.tryParse(json['defaulted_loans_count']?.toString() ?? '0') ?? 0,
      criteria: criteriaList,
    );
  }
}
