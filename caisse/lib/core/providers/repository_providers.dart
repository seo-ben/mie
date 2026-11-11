import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../network/api_client.dart';
import '../../domain/repositories/i_auth_repository.dart';
import '../../domain/repositories/i_cashier_repository.dart';
import '../../data/repositories/auth_repository.dart';
import '../../data/repositories/cashier_repository.dart';

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient();
});

final authRepositoryProvider = Provider<IAuthRepository>((ref) {
  final api = ref.watch(apiClientProvider);
  return AuthRepository(api: api);
});

final cashierRepositoryProvider = Provider<ICashierRepository>((ref) {
  final api = ref.watch(apiClientProvider);
  return CashierRepository(api: api);
});
