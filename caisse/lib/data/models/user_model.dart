class UserModel {
  final int id;
  final String username;
  final String email;
  final String? phone;
  final String? firstName;
  final String? lastName;
  final String role;
  final int? agencyId;
  final String? agencyName;

  UserModel({
    required this.id,
    required this.username,
    required this.email,
    this.phone,
    this.firstName,
    this.lastName,
    required this.role,
    this.agencyId,
    this.agencyName,
  });

  String get fullName => '$firstName $lastName'.trim().isNotEmpty 
      ? '$firstName $lastName'.trim() 
      : username;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] is int ? json['id'] : (int.tryParse(json['id']?.toString() ?? '0') ?? 0),
      username: json['username']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString(),
      firstName: json['first_name']?.toString(),
      lastName: json['last_name']?.toString(),
      role: json['role']?.toString() ?? 'caissier',
      agencyId: json['agency_id'] != null ? int.tryParse(json['agency_id'].toString()) : null,
      agencyName: (json['agency'] != null && json['agency'] is Map) ? json['agency']['name']?.toString() : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'email': email,
      'phone': phone,
      'first_name': firstName,
      'last_name': lastName,
      'role': role,
      'agency_id': agencyId,
    };
  }
}
