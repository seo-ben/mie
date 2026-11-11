class AccountModel {
  final int id;
  final String accountNumber;
  final String accountType; // 'epargne', 'tontine', 'credit'
  final double balance;
  final String status;
  final int clientId;
  final String? clientName;
  final String? clientPhone;
  final double? dailyAmount; // For tontine
  final int? cycleDays;
  final String? paymentFrequency;
  final int? cycleDurationMonths;

  AccountModel({
    required this.id,
    required this.accountNumber,
    required this.accountType,
    required this.balance,
    required this.status,
    required this.clientId,
    this.clientName,
    this.clientPhone,
    this.dailyAmount,
    this.cycleDays,
    this.paymentFrequency,
    this.cycleDurationMonths,
  });

  String get typeLabel {
    final type = accountType.toLowerCase();
    if (type == 'tontine') {
      if (dailyAmount != null && dailyAmount! > 0) {
        return 'Tontine (${dailyAmount!.toInt()} FCFA)';
      }
      return 'Compte Tontine';
    }
    if (type == 'savings' || type == 'epargne') {
      return 'Compte Épargne';
    }
    if (type == 'credit') {
      return 'Compte Prêt';
    }
    return accountType.toUpperCase();
  }

  factory AccountModel.fromJson(Map<String, dynamic> json) {
    // Extract tontine amount from various possible payload formats
    dynamic rawAmount = json['daily_amount'] ?? json['tontine_amount'];
    if (rawAmount == null && json['tontine_account'] is Map) {
      rawAmount = json['tontine_account']['tontine_amount'] ?? json['tontine_account']['expected_monthly_payment'];
    }
    if (rawAmount == null && json['tontineAccount'] is Map) {
      rawAmount = json['tontineAccount']['tontine_amount'] ?? json['tontineAccount']['expected_monthly_payment'];
    }

    String? freq;
    int? duration;
    if (json['tontine_account'] is Map) {
      freq = json['tontine_account']['payment_frequency']?.toString();
      duration = int.tryParse(json['tontine_account']['cycle_duration_months']?.toString() ?? '');
    } else if (json['tontineAccount'] is Map) {
      freq = json['tontineAccount']['payment_frequency']?.toString();
      duration = int.tryParse(json['tontineAccount']['cycle_duration_months']?.toString() ?? '');
    }

    return AccountModel(
      id: json['id'] is int ? json['id'] : (int.tryParse(json['id']?.toString() ?? '0') ?? 0),
      accountNumber: json['account_number']?.toString() ?? '',
      accountType: json['account_type']?.toString() ?? 'epargne',
      balance: double.tryParse(json['balance']?.toString() ?? '0') ?? 0.0,
      status: json['status']?.toString() ?? 'active',
      clientId: json['client_id'] is int ? json['client_id'] : (int.tryParse(json['client_id']?.toString() ?? '0') ?? 0),
      clientName: (json['client'] != null && json['client'] is Map)
          ? '${json['client']['first_name'] ?? ''} ${json['client']['last_name'] ?? ''}'.trim()
          : json['client_name']?.toString(),
      clientPhone: (json['client'] != null && json['client'] is Map)
          ? json['client']['phone']?.toString()
          : json['client_phone']?.toString(),
      dailyAmount: rawAmount != null ? double.tryParse(rawAmount.toString()) : null,
      cycleDays: json['cycle_days'] != null ? int.tryParse(json['cycle_days'].toString()) : 31,
      paymentFrequency: freq ?? json['payment_frequency']?.toString(),
      cycleDurationMonths: duration ?? (json['cycle_duration_months'] != null ? int.tryParse(json['cycle_duration_months'].toString()) : null),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'account_number': accountNumber,
      'account_type': accountType,
      'balance': balance,
      'status': status,
      'client_id': clientId,
      'client_name': clientName,
      'client_phone': clientPhone,
      'daily_amount': dailyAmount,
      'cycle_days': cycleDays,
      'payment_frequency': paymentFrequency,
      'cycle_duration_months': cycleDurationMonths,
    };
  }
}
