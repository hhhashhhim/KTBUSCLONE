import 'package:flutter/services.dart';

import 'token_storage.dart';

class BiometricStatus {
  const BiometricStatus({
    this.available = false,
    this.enabled = false,
    this.label = 'Face ID / fingerprint',
    this.unavailableMessage,
    this.canOpenSettings = false,
  });

  final bool available;
  final bool enabled;
  final String label;
  final String? unavailableMessage;
  final bool canOpenSettings;
}

class BiometricVault {
  static const _channel = MethodChannel('kt/biometric_vault');

  Future<BiometricStatus> status() async {
    try {
      final data = await _channel.invokeMapMethod<String, dynamic>('status');
      return BiometricStatus(
        available: data?['available'] == true,
        enabled: data?['enabled'] == true,
        label: data?['label'] as String? ?? 'Face ID / fingerprint',
        unavailableMessage: data?['unavailableMessage'] as String?,
        canOpenSettings: data?['canOpenSettings'] == true,
      );
    } on MissingPluginException {
      return const BiometricStatus();
    }
  }

  Future<String?> unlock() => _channel.invokeMethod<String>('read');
  Future<void> openSettings() => _channel.invokeMethod<void>('openSettings');
  Future<void> write(String secret) =>
      _channel.invokeMethod<void>('write', {'secret': secret});
  Future<void> release() async {
    try {
      await _channel.invokeMethod<void>('release');
    } on MissingPluginException {
      // Unsupported platforms have no native context to release.
    }
  }

  Future<void> clear() async {
    try {
      await _channel.invokeMethod<void>('clear');
    } on MissingPluginException {
      // Quick login is unavailable on unsupported platforms.
    }
  }
}

/// A biometric passenger's access token is only retained in memory. The only
/// persistent login credential is protected by the native biometric vault.
class BiometricTokenStorage extends TokenStorage {
  BiometricTokenStorage(this.vault);
  final BiometricVault vault;
  String? _sessionToken;

  @override
  Future<String?> read() async {
    if ((await vault.status()).enabled) return _sessionToken;
    return super.read();
  }

  @override
  Future<void> write(String token) async {
    if ((await vault.status()).enabled) {
      await super.clear();
      _sessionToken = token;
    } else {
      _sessionToken = null;
      await super.write(token);
    }
  }

  @override
  Future<void> clear() async {
    _sessionToken = null;
    await super.clear();
  }
}
