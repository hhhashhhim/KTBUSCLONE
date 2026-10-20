import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/core/providers.dart';
import 'package:kt_mobile_app/core/storage/biometric_vault.dart';
import 'package:kt_mobile_app/core/storage/token_storage.dart';
import 'package:kt_mobile_app/features/auth/data/auth_repository.dart';
import 'package:kt_mobile_app/features/auth/data/biometric_login_repository.dart';
import 'package:kt_mobile_app/features/auth/presentation/auth_screens.dart';

class FakeVault extends BiometricVault {
  bool available = true;
  String? secret;
  Object? readError;
  Object? writeError;
  int unlocks = 0;
  @override
  Future<BiometricStatus> status() async => BiometricStatus(
    available: available,
    enabled: secret != null,
    label: 'Face ID',
  );
  @override
  Future<String?> unlock() async {
    unlocks++;
    if (readError != null) throw readError!;
    return secret;
  }

  @override
  Future<void> write(String value) async {
    if (writeError != null) throw writeError!;
    secret = value;
  }

  @override
  Future<void> clear() async => secret = null;
}

class FakeBiometricApi implements ApiClient {
  int renewals = 0;
  int disables = 0;
  Object? renewalError;
  final passenger = <String, dynamic>{
    'id': 1,
    'full_name': 'Passenger',
    'mobile': '03001234567',
    'mobile_verified': true,
  };
  @override
  Future<dynamic> post(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    switch (path) {
      case '/auth/biometric':
        return {'refresh_token': 'initial'};
      case '/auth/biometric/renew':
        renewals++;
        if (renewalError != null) throw renewalError!;
        return {
          'token': 'renewed-access',
          'refresh_token': 'rotated',
          'passenger': passenger,
        };
      case '/auth/login':
        return {'token': 'password-access', 'passenger': passenger};
      case '/auth/logout':
        return null;
      default:
        throw StateError(path);
    }
  }

  @override
  Future<dynamic> delete(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    expect(path, '/auth/biometric');
    disables++;
    return null;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late FakeVault vault;
  late BiometricTokenStorage tokens;
  late FakeBiometricApi api;
  late BiometricLoginRepository repository;
  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
    vault = FakeVault();
    tokens = BiometricTokenStorage(vault);
    api = FakeBiometricApi();
    repository = BiometricLoginRepository(api, tokens, vault);
  });

  test(
    'enrollment verifies biometrics and removes persistent access token',
    () async {
      await tokens.write('password-access');
      await repository.enable('password');
      expect(vault.unlocks, 1);
      expect(vault.secret, 'initial');
      expect(await tokens.read(), 'password-access');
      expect(await TokenStorage().read(), isNull);
      expect(await BiometricTokenStorage(vault).read(), isNull);
      expect(
        await AuthRepository(api, tokens, biometricVault: vault).restore(),
        isNull,
      );
    },
  );

  test(
    'cancelled enrollment revokes renewal and preserves password session',
    () async {
      await tokens.write('password-access');
      vault.readError = PlatformException(code: 'cancelled');
      await expectLater(
        repository.enable('password'),
        throwsA(isA<QuickLoginException>()),
      );
      expect(api.disables, 1);
      expect(vault.secret, isNull);
      expect(await tokens.read(), 'password-access');
    },
  );

  test('unavailable device cannot enroll', () async {
    vault.available = false;
    await expectLater(
      repository.enable('password'),
      throwsA(isA<QuickLoginException>()),
    );
    expect(vault.secret, isNull);
  });

  test(
    'successful biometric login rotates credential and keeps access in memory',
    () async {
      vault.secret = 'initial';
      final user = await repository.login();
      expect(user.id, 1);
      expect(vault.unlocks, 1);
      expect(vault.secret, 'rotated');
      expect(await tokens.read(), 'renewed-access');
      expect(await TokenStorage().read(), isNull);
    },
  );

  test('cancelled unlock never calls server or authenticates', () async {
    vault.secret = 'initial';
    vault.readError = PlatformException(code: 'cancelled');
    await expectLater(repository.login(), throwsA(isA<QuickLoginException>()));
    expect(api.renewals, 0);
    expect(vault.secret, 'initial');
    expect(await tokens.read(), isNull);
  });

  for (final status in [401, 403]) {
    test('server restriction $status clears quick login', () async {
      vault.secret = 'initial';
      api.renewalError = ApiException('Revoked', statusCode: status);
      await expectLater(repository.login(), throwsA(isA<ApiException>()));
      expect(vault.secret, isNull);
      expect(await tokens.read(), isNull);
    });
  }

  test(
    'offline failure keeps protected credential without silently retrying',
    () async {
      vault.secret = 'initial';
      api.renewalError = const ApiException('Offline');
      await expectLater(repository.login(), throwsA(isA<ApiException>()));
      expect(api.renewals, 1);
      expect(vault.secret, 'initial');
    },
  );

  test(
    'failure saving rotated credential does not leave authenticated session',
    () async {
      vault.secret = 'initial';
      vault.writeError = PlatformException(code: 'invalidated');
      await expectLater(
        repository.login(),
        throwsA(isA<QuickLoginException>()),
      );
      expect(vault.secret, isNull);
      expect(await tokens.read(), isNull);
    },
  );

  test('changed biometric enrollment clears old credential', () async {
    vault.secret = 'initial';
    vault.readError = PlatformException(code: 'invalidated');
    await expectLater(repository.login(), throwsA(isA<QuickLoginException>()));
    expect(vault.secret, isNull);
    expect(api.renewals, 0);
  });

  test(
    'disable restores normal password-session storage after server revocation',
    () async {
      vault.secret = 'initial';
      await tokens.write('access');
      await repository.disable();
      expect(api.disables, 1);
      expect(vault.secret, isNull);
      expect(await TokenStorage().read(), 'access');
    },
  );

  test('password account switch removes old biometric identity', () async {
    vault.secret = 'initial';
    await AuthRepository(
      api,
      tokens,
      biometricVault: vault,
    ).login(mobile: '03001234567', password: 'password');
    expect(vault.secret, isNull);
    expect(await tokens.read(), 'password-access');
  });

  test('explicit logout removes both credentials', () async {
    vault.secret = 'initial';
    await tokens.write('access');
    await AuthRepository(api, tokens, biometricVault: vault).logout();
    expect(vault.secret, isNull);
    expect(await tokens.read(), isNull);
  });

  testWidgets('sign-in screen offers quick login and password fallback', (
    tester,
  ) async {
    vault.secret = 'initial';
    await tester.pumpWidget(
      ProviderScope(
        overrides: [biometricVaultProvider.overrideWithValue(vault)],
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Sign in with Face ID'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Forgot password?'), findsOneWidget);
  });
}
