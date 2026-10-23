import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/providers.dart';
import 'package:kt_mobile_app/core/storage/biometric_vault.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/profile/biometric_login_tile.dart';

class SetupVault extends BiometricVault {
  bool available = false;
  bool failStatus = false;
  bool canOpenSettings = true;
  int settingsOpened = 0;
  String message = 'Set up a fingerprint in Android settings.';

  @override
  Future<BiometricStatus> status() async {
    if (failStatus) throw PlatformException(code: 'unavailable');
    return BiometricStatus(
      available: available,
      unavailableMessage: available ? null : message,
      canOpenSettings: canOpenSettings,
    );
  }

  @override
  Future<void> openSettings() async => settingsOpened++;
}

class SetupAuth extends AuthController {
  SetupAuth({this.verified = true});
  final bool verified;

  @override
  AuthState build() => AuthState(
    passenger: PassengerAccount(
      id: 1,
      fullName: 'Passenger',
      mobile: '03001234567',
      mobileVerified: verified,
    ),
  );
}

Future<void> showTile(
  WidgetTester tester,
  SetupVault vault, {
  bool verified = true,
}) async {
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        biometricVaultProvider.overrideWithValue(vault),
        authControllerProvider.overrideWith(
          () => SetupAuth(verified: verified),
        ),
      ],
      child: const MaterialApp(home: Scaffold(body: BiometricLoginTile())),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('returning from biometric setup enables enrollment', (
    tester,
  ) async {
    final vault = SetupVault();
    await showTile(tester, vault);
    expect(
      tester.widget<SwitchListTile>(find.byType(SwitchListTile)).onChanged,
      isNull,
    );
    await tester.tap(find.text('Open biometric settings'));
    await tester.pumpAndSettle();
    expect(vault.settingsOpened, 1);

    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
    vault.available = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(
      tester.widget<SwitchListTile>(find.byType(SwitchListTile)).onChanged,
      isNotNull,
    );
    expect(find.text('Open biometric settings'), findsNothing);
  });

  testWidgets('unsupported face unlock explains why sign-in stays disabled', (
    tester,
  ) async {
    final vault = SetupVault()
      ..canOpenSettings = false
      ..message =
          'This device’s face unlock cannot be used for secure sign-in.';
    await showTile(tester, vault);
    expect(find.text(vault.message), findsOneWidget);
    expect(find.text('Open biometric settings'), findsNothing);
    expect(
      tester.widget<SwitchListTile>(find.byType(SwitchListTile)).onChanged,
      isNull,
    );
  });

  testWidgets('failed availability check can be retried', (tester) async {
    final vault = SetupVault()..failStatus = true;
    await showTile(tester, vault);
    expect(find.text('Unable to check biometrics. Try again.'), findsOneWidget);
    vault
      ..failStatus = false
      ..available = true;
    await tester.tap(find.text('Check again'));
    await tester.pumpAndSettle();
    expect(
      tester.widget<SwitchListTile>(find.byType(SwitchListTile)).onChanged,
      isNotNull,
    );
  });

  testWidgets('supported hardware does not bypass mobile verification', (
    tester,
  ) async {
    await showTile(tester, SetupVault()..available = true, verified: false);
    expect(
      find.text('Verify your mobile number to enable quick login.'),
      findsOneWidget,
    );
    expect(
      tester.widget<SwitchListTile>(find.byType(SwitchListTile)).onChanged,
      isNull,
    );
  });

  test('native availability details and setup action reach Flutter', () async {
    const channel = MethodChannel('kt/biometric_vault');
    final calls = <String>[];
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async {
          calls.add(call.method);
          if (call.method == 'status') {
            return {
              'available': false,
              'enabled': false,
              'label': 'Face / fingerprint',
              'unavailableMessage': 'Set up a fingerprint in Android settings.',
              'canOpenSettings': true,
            };
          }
          return null;
        });
    addTearDown(
      () => TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(channel, null),
    );
    final vault = BiometricVault();
    final status = await vault.status();
    expect(
      status.unavailableMessage,
      'Set up a fingerprint in Android settings.',
    );
    expect(status.canOpenSettings, isTrue);
    await vault.openSettings();
    expect(calls, ['status', 'openSettings']);
  });
}
