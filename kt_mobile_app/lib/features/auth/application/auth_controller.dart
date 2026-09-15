import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/auth_repository.dart';
import '../domain/passenger.dart';

class AuthState {
  const AuthState({
    this.passenger,
    this.isLoading = false,
    this.error,
    this.otpDeliveryMessage,
  });

  final PassengerAccount? passenger;
  final bool isLoading;
  final String? error;
  final String? otpDeliveryMessage;

  bool get isAuthenticated => passenger != null;

  AuthState copyWith({
    PassengerAccount? passenger,
    bool clearPassenger = false,
    bool? isLoading,
    String? error,
    bool clearError = false,
  }) => AuthState(
    passenger: clearPassenger ? null : passenger ?? this.passenger,
    isLoading: isLoading ?? this.isLoading,
    error: clearError ? null : error ?? this.error,
    otpDeliveryMessage: otpDeliveryMessage,
  );
}

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(tokenStorageProvider),
  ),
);

final authControllerProvider = NotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

class AuthController extends Notifier<AuthState> {
  @override
  AuthState build() => const AuthState();

  Future<bool> restoreSession() async {
    final passenger = await ref.read(authRepositoryProvider).restore();
    state = state.copyWith(
      passenger: passenger,
      clearPassenger: passenger == null,
      clearError: true,
    );
    return passenger != null;
  }

  Future<bool> login(String mobile, String password) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final passenger = await ref
          .read(authRepositoryProvider)
          .login(mobile: mobile, password: password);
      state = AuthState(passenger: passenger);
      return true;
    } catch (error) {
      state = AuthState(error: error.toString());
      return false;
    }
  }

  Future<bool> register({
    required String fullName,
    required String mobile,
    required String cnic,
    required String password,
    required String passwordConfirmation,
    String? email,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final result = await ref
          .read(authRepositoryProvider)
          .register(
            fullName: fullName,
            mobile: mobile,
            cnic: cnic,
            password: password,
            passwordConfirmation: passwordConfirmation,
            email: email,
          );
      state = AuthState(
        passenger: result.passenger,
        otpDeliveryMessage: result.deliveryMessage,
      );
      return true;
    } catch (error) {
      state = AuthState(error: error.toString());
      return false;
    }
  }

  Future<void> logout() async {
    state = state.copyWith(isLoading: true, clearError: true);
    await ref.read(authRepositoryProvider).logout();
    state = const AuthState();
  }

  void replacePassenger(PassengerAccount passenger) {
    state = AuthState(passenger: passenger);
  }

  Future<void> deleteAccount(String password) async {
    await ref.read(authRepositoryProvider).deleteAccount(password);
    // The server has confirmed deletion. Clear in-memory state even if secure
    // storage is temporarily unavailable; its old token is already revoked.
    state = const AuthState();
    try {
      await ref.read(authRepositoryProvider).clearLocalSession();
    } catch (_) {
      // Session restoration will reject and remove the revoked token.
    }
  }

  void expireSession() {
    state = const AuthState(
      error: 'Your session expired. Please sign in again.',
    );
  }
}
