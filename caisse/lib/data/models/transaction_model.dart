class TransactionModel {
  final int id;
  final String reference;
  final String transactionType; // 'deposit', 'withdrawal', 'transfer', 'fee'
  final int? accountId;
  final double amount;
  final double? fee;
  final double? netAmount;
  final String status; // 'completed', 'pending', 'cancelled'
  final String? description;
  final String? accountType;
  final String? accountNumber;
  final String? clientName;
  final String? agentName;
  final DateTime? createdAt;

  TransactionModel({
    required this.id,
    required this.reference,
    required this.transactionType,
    this.accountId,
    required this.amount,
    this.fee,
    this.netAmount,
    required this.status,
    this.description,
    this.accountType,
    this.accountNumber,
    this.clientName,
    this.agentName,
    this.createdAt,
  });

  String get type => transactionType;

  bool get isDeposit => transactionType.toLowerCase() == 'deposit' || transactionType.toLowerCase() == 'depot' || transactionType.toLowerCase() == 'transfer_in';
  bool get isWithdrawal => transactionType.toLowerCase() == 'withdrawal' || transactionType.toLowerCase() == 'retrait' || transactionType.toLowerCase() == 'transfer_out';
  bool get isTransfer => transactionType.toLowerCase().contains('transfer') || transactionType.toLowerCase().contains('virement');

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    final rawReference = json['reference'] ?? json['transaction_reference'] ?? json['transaction_number'] ?? json['code'];
    final rawFee = json['fee'] ?? json['fee_amount'];
    
    // Resolve client name
    String? resolvedClientName;
    if (json['client'] != null && json['client'] is Map) {
      resolvedClientName = '${json['client']['first_name'] ?? ''} ${json['client']['last_name'] ?? ''}'.trim();
    } else if (json['account'] != null && json['account'] is Map && json['account']['client'] != null && json['account']['client'] is Map) {
      resolvedClientName = '${json['account']['client']['first_name'] ?? ''} ${json['account']['client']['last_name'] ?? ''}'.trim();
    } else if (json['client_name'] != null) {
      resolvedClientName = json['client_name']?.toString();
    } else if (json['client_full_name'] != null) {
      resolvedClientName = json['client_full_name']?.toString();
    }

    // Resolve agent name
    String? resolvedAgentName;
    if (json['agent'] != null && json['agent'] is Map) {
      resolvedAgentName = json['agent']['name']?.toString();
    } else if (json['processed_by'] != null && json['processed_by'] is Map) {
      resolvedAgentName = json['processed_by']['name']?.toString();
    } else if (json['processedBy'] != null && json['processedBy'] is Map) {
      resolvedAgentName = json['processedBy']['name']?.toString();
    } else if (json['agent_name'] != null) {
      resolvedAgentName = json['agent_name']?.toString();
    }

    // Resolve account number and type and id
    String? resolvedAccountNumber;
    String? resolvedAccountType;
    int? resolvedAccountId = int.tryParse(json['account_id']?.toString() ?? '');
    if (json['account'] != null && json['account'] is Map) {
      resolvedAccountNumber = json['account']['account_number']?.toString();
      resolvedAccountType = json['account']['account_type']?.toString();
      resolvedAccountId ??= int.tryParse(json['account']['id']?.toString() ?? '');
    }
    resolvedAccountNumber ??= json['account_number']?.toString();
    resolvedAccountType ??= json['account_type']?.toString();

    return TransactionModel(
      id: json['id'] is int ? json['id'] : (int.tryParse(json['id']?.toString() ?? '0') ?? 0),
      reference: rawReference?.toString() ?? 'TX-${json['id'] ?? 'N/A'}',
      transactionType: (json['transaction_type'] ?? json['type'] ?? 'deposit').toString(),
      accountId: resolvedAccountId,
      amount: double.tryParse(json['amount']?.toString() ?? '0') ?? 0.0,
      fee: rawFee != null ? double.tryParse(rawFee.toString()) : 0.0,
      netAmount: json['net_amount'] != null ? double.tryParse(json['net_amount'].toString()) : null,
      status: (json['status'] ?? 'completed').toString(),
      description: json['description']?.toString(),
      accountType: resolvedAccountType,
      accountNumber: resolvedAccountNumber,
      clientName: (resolvedClientName != null && resolvedClientName.isNotEmpty) ? resolvedClientName : null,
      agentName: (resolvedAgentName != null && resolvedAgentName.isNotEmpty) ? resolvedAgentName : null,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'reference': reference,
      'transaction_type': transactionType,
      'account_id': accountId,
      'amount': amount,
      'fee': fee,
      'net_amount': netAmount,
      'status': status,
      'description': description,
      'account_type': accountType,
      'account_number': accountNumber,
      'client_name': clientName,
      'agent_name': agentName,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
