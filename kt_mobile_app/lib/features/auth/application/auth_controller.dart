import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../../notifications/notification_push.dart';
import '../data/auth_repository.dart';
import '../data/biometric_login_repository.dart';
import '../domain/passenger.dart';

class AuthState {
  const AuthState({
    this.passenger,
    this.isLoading = false,
    this.error,
    this.otpDeliveryMessage,
    this.sessionGeneration = 0,
  });

  final PassengerAccount? passenger;
  final bool isLoading;
  final String? error;
  final String? otpDeliveryMessage;
  final int sessionGeneration;

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
    sessionGeneration: sessionGeneration,
  );
}

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(tokenStorageProvider),
    biometricVault: ref.watch(biometricVaultProvider),
    beforeLogout: () => ref.read(notificationPushProvider).detach(),
  ),
);

final biometricLoginRepositoryProvider = Provider<BiometricLoginRepository>(
  (ref) => BiometricLoginRepository(
    ref.watch(apiClientProvider),
    ref.watch(tokenStorageProvider),
    ref.watch(biometricVaultProvider),
  ),
);

final authControllerProvider = NotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

// A fresh login has a fresh cache scope, even when the same passenger returns.
typedef PassengerSession = ({int? passengerId, int generation});

final passengerSessionProvider = Provider<PassengerSession>((ref) {
  return ref.watch(
    authControllerProvider.select(
      (state) => (
        passengerId: state.passenger?.id,
        generation: state.sessionGeneration,
      ),
    ),
  );
});

class AuthController extends Notifier<AuthState> {
  int _sessionGeneration = 0;
  int _authOperation = 0;

  @override
  AuthState build() => const AuthState();

  Future<bool> loginWithBiometrics() async {
    if (state.isLoading) return false;
    final operation = ++_authOperation;
    state = const AuthState(isLoading: true);
    try {
      final passenger = await ref
          .read(biometricLoginRepositoryProvider)
          .login();
      if (!ref.mounted || operation != _authOperation) return false;
      state = AuthState(
        passenger: passenger,
        sessionGeneration: ++_sessionGeneration,
      );
      return true;
    } catch (error) {
      if (!ref.mounted || operation != _authOperation) return false;
      state = AuthState(error: error.toString());
      return false;
    } finally {
      ref.invalidate(biometricStatusProvider);
    }
  }

  Future<bool> restoreSession() async {
    final operation = ++_authOperation;
    final passenger = await ref.read(authRepositoryProvider).restore();
    // A delayed restore must not undo a logout, lock or newer sign-in.
    if (!ref.mounted || operation != _authOperation) return false;
    state = AuthState(
      passenger: passenger,
      sessionGeneration: ++_sessionGeneration,
    );
    return passenger != null;
  }

  Future<bool> login(String mobile, String password) async {
    final operation = ++_authOperation;
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final passenger = await ref
          .read(authRepositoryProvider)
          .login(mobile: mobile, password: password);
      if (!ref.mounted || operation != _authOperation) return false;
      state = AuthState(
        passenger: passenger,
        sessionGeneration: ++_sessionGeneration,
      );
      ref.invalidate(biometricStatusProvider);
      return true;
    } catch (error) {
      if (!ref.mounted || operation != _authOperation) return false;
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
    final operation = ++_authOperation;
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
      if (!ref.mounted || operation != _authOperation) return false;
      state = AuthState(
        passenger: result.passenger,
        otpDeliveryMessage: result.deliveryMessage,
        sessionGeneration: ++_sessionGeneration,
      );
      ref.invalidate(biometricStatusProvider);
      return true;
    } catch (error) {
      if (!ref.mounted || operation != _authOperation) return false;
      state = AuthState(error: error.toString());
      return false;
    }
  }

  Future<void> logout() async {
    ++_authOperation;
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      await ref.read(authRepositoryProvider).logout();
      state = const AuthState();
      ref.invalidate(biometricStatusProvider);
    } catch (error) {
      state = state.copyWith(isLoading: false, error: error.toString());
      rethrow;
    }
  }

  void replacePassenger(PassengerAccount passenger) {
    ++_authOperation;
    state = AuthState(
      passenger: passenger,
      sessionGeneration: state.passenger?.id == passenger.id
          ? state.sessionGeneration
          : ++_sessionGeneration,
    );
  }

  Future<void> deleteAccount(String password) async {
    ++_authOperation;
    await ref.read(authRepositoryProvider).deleteAccount(password);
    await ref.read(notificationPushProvider).detach();
    // The server has confirmed deletion. Clear in-memory state even if secure
    // storage is temporarily unavailable; its old token is already revoked.
    state = const AuthState();
    try {
      await ref.read(authRepositoryProvider).clearLocalSession();
    } catch (_) {
      // Session restoration will reject and remove the revoked token.
    } finally {
      ref.invalidate(biometricStatusProvider);
    }
  }

  void expireSession() {
    ++_authOperation;
    state = const AuthState(
      error: 'Your session expired. Please sign in again.',
    );
  }

  void lockForQuickLogin() {
    ++_authOperation;
    state = const AuthState();
  }
}
