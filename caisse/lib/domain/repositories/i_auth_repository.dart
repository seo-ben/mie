import '../../data/models/user_model.dart';

abstract class IAuthRepository {
  Future<UserModel?> tryAutoLogin();
  Future<UserModel> login({required String email, required String password});
  Future<void> logout();
  Future<UserModel?> getCachedUser();
}
