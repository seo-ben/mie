import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../../data/models/user_model.dart';
import '../../domain/repositories/i_auth_repository.dart';

class AuthRepository implements IAuthRepository {
  final ApiClient _api;

  AuthRepository({ApiClient? api}) : _api = api ?? ApiClient();

  @override
  Future<UserModel?> getCachedUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userDataString = prefs.getString('user_data');
    if (userDataString != null) {
      try {
        return UserModel.fromJson(jsonDecode(userDataString));
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  @override
  Future<UserModel?> tryAutoLogin() async {
    final token = await _api.loadToken();
    if (token == null || token.isEmpty) {
      return null;
    }

    final cachedUser = await getCachedUser();

    try {
      final response = await _api.dio.get(ApiConstants.me);
      if (response.data['success'] == true && response.data['data'] != null) {
        final freshUser = UserModel.fromJson(response.data['data']);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_data', jsonEncode(freshUser.toJson()));
        return freshUser;
      }
      return cachedUser;
    } catch (_) {
      // If network is down or temporary error, return cached user if present
      return cachedUser;
    }
  }

  @override
  Future<UserModel> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _api.dio.post(
        ApiConstants.login,
        data: {
          'email': email.trim(),
          'password': password,
        },
      );

      final data = response.data;
      if (data['success'] == true && data['data'] != null) {
        final token = data['data']['token'];
        final userData = data['data']['user'];

        final role = userData['role'];
        if (role != 'caissier' &&
            role != 'administrateur_systeme' &&
            role != 'agent_agence' &&
            role != 'agent_terrain') {
          throw Exception('Accès réservé au personnel de caisse et agents autorisés.');
        }

        final user = UserModel.fromJson(userData);
        await _api.saveToken(token);

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_data', jsonEncode(user.toJson()));

        return user;
      } else {
        throw Exception(data['message'] ?? 'Identifiants invalides.');
      }
    } on DioException catch (e) {
      final serverMsg = e.response?.data?['message'];
      if (serverMsg != null) {
        throw Exception(serverMsg);
      }
      throw Exception('Impossible de joindre le serveur. Vérifiez votre connexion.');
    }
  }

  @override
  Future<void> logout() async {
    try {
      await _api.dio.post(ApiConstants.logout);
    } catch (_) {}
    await _api.clearToken();
  }
}
