class ClientModel {
  final int id;
  final String firstName;
  final String lastName;
  final String? phone;
  final String? email;
  final String? address;
  final String? city;
  final String? profession;
  final String? idType;
  final String? idNumber;
  final String? kycStatus;
  final DateTime? createdAt;
  final List<dynamic>? accounts;
  final List<dynamic>? loans;

  ClientModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.phone,
    this.email,
    this.address,
    this.city,
    this.profession,
    this.idType,
    this.idNumber,
    this.kycStatus,
    this.createdAt,
    this.accounts,
    this.loans,
  });

  String get fullName => '$firstName $lastName'.trim();
  String get clientNumber => idNumber ?? 'CLI-$id';

  double get totalAccountBalance {
    return (accounts ?? []).fold<double>(0, (total, account) {
      if (account is! Map) return total;
      return total + (double.tryParse(account['balance']?.toString() ?? '0') ?? 0);
    });
  }

  bool get hasLoans => (loans != null && loans!.isNotEmpty);

  bool get hasActiveLoan {
    return (loans ?? []).any((l) {
      if (l is! Map) return false;
      final st = l['status']?.toString();
      return st == 'active' || st == 'disbursed';
    });
  }

  double get totalLoanBorrowed {
    return (loans ?? []).fold<double>(0, (total, l) {
      if (l is! Map) return total;
      final st = l['status']?.toString();
      if (st == 'active' || st == 'disbursed' || st == 'completed') {
        final app = double.tryParse(l['approved_amount']?.toString() ?? '0') ?? 0;
        final req = double.tryParse(l['requested_amount']?.toString() ?? '0') ?? 0;
        return total + (app > 0 ? app : req);
      }
      return total;
    });
  }

  double get totalLoanPaid {
    return (loans ?? []).fold<double>(0, (total, l) {
      if (l is! Map) return total;
      return total + (double.tryParse(l['total_paid']?.toString() ?? '0') ?? 0);
    });
  }

  double get totalLoanRemaining {
    return (loans ?? []).fold<double>(0, (total, l) {
      if (l is! Map) return total;
      final st = l['status']?.toString();
      if (st == 'active' || st == 'disbursed') {
        final due = double.tryParse(l['total_amount_due']?.toString() ?? l['approved_amount']?.toString() ?? '0') ?? 0;
        final paid = double.tryParse(l['total_paid']?.toString() ?? '0') ?? 0;
        return total + (due - paid).clamp(0, double.infinity);
      }
      return total;
    });
  }

  factory ClientModel.fromJson(Map<String, dynamic> json) {
    return ClientModel(
      id: json['id'] is int ? json['id'] : (int.tryParse(json['id']?.toString() ?? '0') ?? 0),
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      address: json['address']?.toString(),
      city: json['city']?.toString(),
      profession: json['profession']?.toString(),
      idType: json['id_type']?.toString(),
      idNumber: json['id_number']?.toString(),
      kycStatus: json['kyc_status']?.toString() ?? 'pending',
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      accounts: json['accounts'] is List ? json['accounts'] : null,
      loans: json['loans'] is List ? json['loans'] : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'phone': phone,
      'email': email,
      'address': address,
      'city': city,
      'profession': profession,
      'id_type': idType,
      'id_number': idNumber,
      'kyc_status': kycStatus,
      'created_at': createdAt?.toIso8601String(),
      'accounts': accounts,
      'loans': loans,
    };
  }
}
