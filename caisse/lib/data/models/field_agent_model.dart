import 'client_model.dart';
import 'transaction_model.dart';

class AgentDailyCollectionsModel {
  final String date;
  final double totalCollected;
  final double totalWithdrawals;
  final double netCollected;
  final int operationsCount;
  final double savingsAmount;
  final double tontineAmount;
  final double loanRepaymentsAmount;

  const AgentDailyCollectionsModel({
    required this.date,
    this.totalCollected = 0.0,
    this.totalWithdrawals = 0.0,
    this.netCollected = 0.0,
    this.operationsCount = 0,
    this.savingsAmount = 0.0,
    this.tontineAmount = 0.0,
    this.loanRepaymentsAmount = 0.0,
  });

  factory AgentDailyCollectionsModel.fromJson(Map<String, dynamic> json) {
    return AgentDailyCollectionsModel(
      date: json['date']?.toString() ?? '',
      totalCollected: (json['total_collected'] as num?)?.toDouble() ?? 0.0,
      totalWithdrawals: (json['total_withdrawals'] as num?)?.toDouble() ?? 0.0,
      netCollected: (json['net_collected'] as num?)?.toDouble() ?? 0.0,
      operationsCount: (json['operations_count'] as num?)?.toInt() ?? 0,
      savingsAmount: (json['savings_amount'] as num?)?.toDouble() ?? 0.0,
      tontineAmount: (json['tontine_amount'] as num?)?.toDouble() ?? 0.0,
      loanRepaymentsAmount: (json['loan_repayments_amount'] as num?)?.toDouble() ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() => {
    'date': date,
    'total_collected': totalCollected,
    'total_withdrawals': totalWithdrawals,
    'net_collected': netCollected,
    'operations_count': operationsCount,
    'savings_amount': savingsAmount,
    'tontine_amount': tontineAmount,
    'loan_repayments_amount': loanRepaymentsAmount,
  };
}

class FieldAgentSummaryModel {
  final int id;
  final String firstName;
  final String lastName;
  final String fullName;
  final String? username;
  final String? phone;
  final String? email;
  final String role;
  final bool isActive;
  final int totalClients;
  final AgentDailyCollectionsModel collections;

  const FieldAgentSummaryModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.fullName,
    this.username,
    this.phone,
    this.email,
    required this.role,
    this.isActive = true,
    this.totalClients = 0,
    required this.collections,
  });

  factory FieldAgentSummaryModel.fromJson(Map<String, dynamic> json) {
    return FieldAgentSummaryModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      fullName: json['full_name']?.toString() ??
          '${json['first_name'] ?? ''} ${json['last_name'] ?? ''}'.trim(),
      username: json['username']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      role: json['role']?.toString() ?? 'agent_terrain',
      isActive: json['is_active'] == true || json['is_active'] == 1,
      totalClients: (json['total_clients'] as num?)?.toInt() ?? 0,
      collections: json['collections'] is Map<String, dynamic>
          ? AgentDailyCollectionsModel.fromJson(json['collections'] as Map<String, dynamic>)
          : AgentDailyCollectionsModel(date: json['date']?.toString() ?? ''),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'first_name': firstName,
    'last_name': lastName,
    'full_name': fullName,
    'username': username,
    'phone': phone,
    'email': email,
    'role': role,
    'is_active': isActive,
    'total_clients': totalClients,
    'collections': collections.toJson(),
  };
}

class AgencyFieldSummary {
  final int totalAgents;
  final int activeAgents;
  final double totalCollected;
  final int totalOperations;

  const AgencyFieldSummary({
    this.totalAgents = 0,
    this.activeAgents = 0,
    this.totalCollected = 0.0,
    this.totalOperations = 0,
  });

  factory AgencyFieldSummary.fromJson(Map<String, dynamic> json) {
    return AgencyFieldSummary(
      totalAgents: (json['total_agents'] as num?)?.toInt() ?? 0,
      activeAgents: (json['active_agents'] as num?)?.toInt() ?? 0,
      totalCollected: (json['total_collected'] as num?)?.toDouble() ?? 0.0,
      totalOperations: (json['total_operations'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
    'total_agents': totalAgents,
    'active_agents': activeAgents,
    'total_collected': totalCollected,
    'total_operations': totalOperations,
  };
}

class AgencyFieldTeamResponse {
  final String date;
  final AgencyFieldSummary summary;
  final List<FieldAgentSummaryModel> agents;

  const AgencyFieldTeamResponse({
    required this.date,
    required this.summary,
    required this.agents,
  });

  factory AgencyFieldTeamResponse.fromJson(Map<String, dynamic> json) {
    final list = json['data'] is List ? json['data'] as List : (json['agents'] is List ? json['agents'] as List : []);
    return AgencyFieldTeamResponse(
      date: json['date']?.toString() ?? '',
      summary: json['summary'] is Map<String, dynamic>
          ? AgencyFieldSummary.fromJson(json['summary'] as Map<String, dynamic>)
          : const AgencyFieldSummary(),
      agents: list
          .map((a) => FieldAgentSummaryModel.fromJson(a as Map<String, dynamic>))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() => {
    'date': date,
    'summary': summary.toJson(),
    'data': agents.map((a) => a.toJson()).toList(),
  };
}

class FieldAgentDetailItem {
  final int id;
  final String fullName;
  final String? username;
  final String? phone;
  final String? email;
  final String role;
  final bool isActive;
  final int totalClients;

  const FieldAgentDetailItem({
    required this.id,
    required this.fullName,
    this.username,
    this.phone,
    this.email,
    required this.role,
    this.isActive = true,
    this.totalClients = 0,
  });

  factory FieldAgentDetailItem.fromJson(Map<String, dynamic> json) {
    return FieldAgentDetailItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      fullName: json['full_name']?.toString() ?? 'Agent',
      username: json['username']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      role: json['role']?.toString() ?? 'agent_terrain',
      isActive: json['is_active'] == true || json['is_active'] == 1,
      totalClients: (json['total_clients'] as num?)?.toInt() ?? 0,
    );
  }
}

class FieldAgentCollectionItem {
  final int id;
  final String reference;
  final String type;
  final double amount;
  final String accountNumber;
  final String accountType;
  final int? clientId;
  final String clientName;
  final String clientPhone;
  final String time;
  final String? createdAt;
  final String paymentMethod;
  final String? description;

  const FieldAgentCollectionItem({
    required this.id,
    required this.reference,
    required this.type,
    required this.amount,
    required this.accountNumber,
    required this.accountType,
    this.clientId,
    required this.clientName,
    required this.clientPhone,
    required this.time,
    this.createdAt,
    this.paymentMethod = 'cash',
    this.description,
  });

  factory FieldAgentCollectionItem.fromJson(Map<String, dynamic> json) {
    return FieldAgentCollectionItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      reference: json['reference']?.toString() ?? 'TX-${json['id']}',
      type: json['type']?.toString() ?? 'deposit',
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      accountNumber: json['account_number']?.toString() ?? 'N/A',
      accountType: json['account_type']?.toString() ?? 'savings',
      clientId: json['client_id'] is int ? json['client_id'] : int.tryParse(json['client_id']?.toString() ?? ''),
      clientName: json['client_name']?.toString() ?? 'Client inconnu',
      clientPhone: json['client_phone']?.toString() ?? 'N/A',
      time: json['time']?.toString() ?? '--:--',
      createdAt: json['created_at']?.toString(),
      paymentMethod: json['payment_method']?.toString() ?? 'cash',
      description: json['description']?.toString(),
    );
  }
}

class FieldAgentClientItem {
  final int id;
  final String clientNumber;
  final String firstName;
  final String lastName;
  final String fullName;
  final String? phone;
  final String? email;
  final String? address;
  final String? kycStatus;
  final String? registrationStatus;
  final double totalBalance;
  final int accountsCount;
  final String? createdAt;

  const FieldAgentClientItem({
    required this.id,
    required this.clientNumber,
    required this.firstName,
    required this.lastName,
    required this.fullName,
    this.phone,
    this.email,
    this.address,
    this.kycStatus,
    this.registrationStatus,
    this.totalBalance = 0.0,
    this.accountsCount = 0,
    this.createdAt,
  });

  factory FieldAgentClientItem.fromJson(Map<String, dynamic> json) {
    return FieldAgentClientItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      clientNumber: json['client_number']?.toString() ?? 'CLI-${json['id']}',
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      fullName: json['full_name']?.toString() ??
          '${json['first_name'] ?? ''} ${json['last_name'] ?? ''}'.trim(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      address: json['address']?.toString(),
      kycStatus: json['kyc_status']?.toString() ?? 'pending',
      registrationStatus: json['registration_status']?.toString() ?? 'approved',
      totalBalance: (json['total_balance'] as num?)?.toDouble() ?? 0.0,
      accountsCount: (json['accounts_count'] as num?)?.toInt() ?? 0,
      createdAt: json['created_at']?.toString(),
    );
  }
}

class FieldAgentDetailResponse {
  final String date;
  final FieldAgentDetailItem agent;
  final AgentDailyCollectionsModel summary;
  final List<FieldAgentCollectionItem> collections;
  final List<FieldAgentClientItem> clients;

  const FieldAgentDetailResponse({
    required this.date,
    required this.agent,
    required this.summary,
    required this.collections,
    required this.clients,
  });

  factory FieldAgentDetailResponse.fromJson(Map<String, dynamic> json) {
    final rawCollections = json['collections'] is List ? json['collections'] as List : [];
    final rawClients = json['clients'] is List ? json['clients'] as List : [];

    return FieldAgentDetailResponse(
      date: json['date']?.toString() ?? '',
      agent: FieldAgentDetailItem.fromJson(json['agent'] as Map<String, dynamic>),
      summary: json['summary'] is Map<String, dynamic>
          ? AgentDailyCollectionsModel.fromJson(json['summary'] as Map<String, dynamic>)
          : const AgentDailyCollectionsModel(date: ''),
      collections: rawCollections
          .map((c) => FieldAgentCollectionItem.fromJson(c as Map<String, dynamic>))
          .toList(),
      clients: rawClients
          .map((cl) => FieldAgentClientItem.fromJson(cl as Map<String, dynamic>))
          .toList(),
    );
  }
}
