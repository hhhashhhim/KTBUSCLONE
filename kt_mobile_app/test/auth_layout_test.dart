import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/providers.dart';
import 'package:kt_mobile_app/core/storage/biometric_vault.dart';
import 'package:kt_mobile_app/features/auth/presentation/auth_screens.dart';

void main() {
  for (final screen in <String, Widget>{
    'Welcome back': const LoginScreen(),
    'Forgot password?': const ForgotPasswordScreen(),
  }.entries) {
    testWidgets('${screen.key} handles zero startup height and recovery', (
      tester,
    ) async {
      tester.view.devicePixelRatio = 1;
      tester.view.physicalSize = const Size(800, 1280);
      addTearDown(tester.view.resetDevicePixelRatio);
      addTearDown(tester.view.resetPhysicalSize);

      final size = ValueNotifier<Size>(const Size(800, 0));
      addTearDown(size.dispose);
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            biometricStatusProvider.overrideWith(
              (ref) async => const BiometricStatus(),
            ),
          ],
          child: MaterialApp(
            home: screen.value,
            builder: (context, child) => ValueListenableBuilder<Size>(
              valueListenable: size,
              builder: (context, size, _) => MediaQuery(
                data: MediaQuery.of(context).copyWith(size: size),
                child: child!,
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);

      size.value = const Size(800, 1280);
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      expect(find.text(screen.key), findsOneWidget);
      expect(
        find.byType(TextFormField).evaluate().isNotEmpty ||
            find.byType(TextField).evaluate().isNotEmpty,
        isTrue,
      );
    });
  }
}
