import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/repository_providers.dart';
import '../../data/models/user_model.dart';
import '../repositories/i_auth_repository.dart';

class AuthState {
  final UserModel? user;
  final bool isLoading;
  final String? errorMessage;

  const AuthState({
    this.user,
    this.isLoading = false,
    this.errorMessage,
  });

  bool get isAuthenticated => user != null;

  AuthState copyWith({
    UserModel? user,
    bool? isLoading,
    String? errorMessage,
    bool clearError = false,
    bool clearUser = false,
  }) {
    return AuthState(
      user: clearUser ? null : (user ?? this.user),
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final IAuthRepository _authRepository;

  AuthNotifier(this._authRepository) : super(const AuthState());

  Future<bool> tryAutoLogin() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final user = await _authRepository.tryAutoLogin();
      if (user != null) {
        state = state.copyWith(user: user, isLoading: false);
        return true;
      }
      state = state.copyWith(isLoading: false, clearUser: true);
      return false;
    } catch (e) {
      state = state.copyWith(isLoading: false);
      return state.isAuthenticated;
    }
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final user = await _authRepository.login(
        email: email,
        password: password,
      );
      state = state.copyWith(user: user, isLoading: false, clearError: true);
      return true;
    } catch (e) {
      final message = e.toString().replaceFirst('Exception: ', '');
      state = state.copyWith(
        isLoading: false,
        errorMessage: message,
      );
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _authRepository.logout();
    } finally {
      state = const AuthState();
    }
  }
}

final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final authRepository = ref.watch(authRepositoryProvider);
  return AuthNotifier(authRepository);
});
