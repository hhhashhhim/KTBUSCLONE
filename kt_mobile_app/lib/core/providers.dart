import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'network/api_client.dart';
import 'storage/token_storage.dart';
import 'storage/biometric_vault.dart';

final biometricVaultProvider = Provider<BiometricVault>(
  (ref) => BiometricVault(),
);
final biometricStatusProvider = FutureProvider<BiometricStatus>(
  (ref) => ref.watch(biometricVaultProvider).status(),
);
final tokenStorageProvider = Provider<TokenStorage>(
  (ref) => BiometricTokenStorage(ref.watch(biometricVaultProvider)),
);

final apiClientProvider = Provider<ApiClient>((ref) {
  final client = ApiClient(ref.watch(tokenStorageProvider));
  ref.onDispose(client.dispose);
  return client;
});
