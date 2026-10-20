import 'package:flutter/services.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/storage/biometric_vault.dart';
import '../../../core/storage/token_storage.dart';
import '../domain/passenger.dart';

class BiometricLoginRepository {
  BiometricLoginRepository(this._api, this._tokens, this._vault);
  final ApiClient _api;
  final TokenStorage _tokens;
  final BiometricVault _vault;
  bool _busy = false;

  Future<void> enable(String password) async {
    if (_busy) throw const QuickLoginException('Quick login is busy.');
    _busy = true;
    var enrolled = false;
    String? access;
    try {
      if (!(await _vault.status()).available) {
        throw const QuickLoginException(
          'Set up Face ID or fingerprint in your phone settings first.',
        );
      }
      access = await _tokens.read();
      if (access == null) {
        throw const QuickLoginException('Sign in with your password first.');
      }
      final data =
          await _api.post('/auth/biometric', data: {'password': password})
              as Map;
      enrolled = true;
      await _vault.write(data['refresh_token'] as String);
      await _tokens.write(access);
      // Enrollment is only complete after a real cryptographic biometric read.
      if (await _vault.unlock() != data['refresh_token']) {
        throw const QuickLoginException('Quick login could not be enabled.');
      }
    } catch (error) {
      if (enrolled) {
        // Make the current access token available while undoing enrollment.
        // The vault is removed after revocation, never saved without biometrics.
        try {
          await _api.delete('/auth/biometric');
        } catch (_) {}
        await _vault.clear();
        if (access != null) await _tokens.write(access);
      }
      throw _friendly(error);
    } finally {
      _busy = false;
      await _vault.release();
    }
  }

  Future<PassengerAccount> login() async {
    if (_busy) throw const QuickLoginException('Quick login is busy.');
    _busy = true;
    var renewed = false;
    try {
      final secret = await _vault.unlock();
      if (secret == null) {
        throw const QuickLoginException(
          'Sign in with your password to enable quick login.',
        );
      }
      // No automatic HTTP retry: renewal credentials are single-use.
      final data =
          await _api.post(
                '/auth/biometric/renew',
                data: {'refresh_token': secret},
              )
              as Map;
      renewed = true;
      await _vault.write(data['refresh_token'] as String);
      await _tokens.write(data['token'] as String);
      return PassengerAccount.fromJson(
        (data['passenger'] as Map).cast<String, dynamic>(),
      );
    } catch (error) {
      final invalid =
          error is ApiException &&
          (error.statusCode == 401 || error.statusCode == 403);
      final invalidated =
          error is PlatformException && error.code == 'invalidated';
      if (invalid || invalidated || renewed) {
        await _tokens.clear();
        await _vault.clear();
      }
      throw _friendly(error);
    } finally {
      _busy = false;
      await _vault.release();
    }
  }

  Future<void> disable() async {
    if (_busy) throw const QuickLoginException('Quick login is busy.');
    _busy = true;
    try {
      final access = await _tokens.read();
      await _api.delete('/auth/biometric');
      await _vault.clear();
      // Return to the existing password-session behavior.
      if (access != null) await _tokens.write(access);
    } finally {
      _busy = false;
    }
  }

  Object _friendly(Object error) {
    if (error is PlatformException) {
      return QuickLoginException(
        error.code == 'cancelled'
            ? 'Quick login cancelled. You can use your password.'
            : error.message ?? 'Biometrics are unavailable. Use your password.',
      );
    }
    return error;
  }
}

class QuickLoginException implements Exception {
  const QuickLoginException(this.message);
  final String message;
  @override
  String toString() => message;
}
