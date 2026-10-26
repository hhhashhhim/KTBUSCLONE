import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/core/widgets/legal_links.dart';
import 'package:kt_mobile_app/core/widgets/privacy_policy_screen.dart';
import 'package:kt_mobile_app/features/auth/presentation/auth_screens.dart';
import 'package:webview_flutter/webview_flutter.dart';

void main() {
  for (final viewport in [
    (size: const Size(320, 568), scale: 2.0),
    (size: const Size(568, 320), scale: 2.0),
    (size: const Size(1024, 768), scale: 1.0),
  ]) {
    testWidgets(
      'privacy is readable offline at ${viewport.size}, scale ${viewport.scale}',
      (tester) async {
        tester.view.devicePixelRatio = 1;
        tester.view.physicalSize = viewport.size;
        addTearDown(tester.view.resetDevicePixelRatio);
        addTearDown(tester.view.resetPhysicalSize);
        // No WebView platform or network client is installed for this test.
        await tester.pumpWidget(
          MaterialApp(
            theme: AppTheme.light,
            builder: (context, child) => MediaQuery(
              data: MediaQuery.of(
                context,
              ).copyWith(textScaler: TextScaler.linear(viewport.scale)),
              child: child!,
            ),
            home: const Scaffold(body: SafeArea(child: LegalLinks())),
          ),
        );
        await tester.tap(find.text('Privacy policy'));
        await tester.pumpAndSettle();

        expect(find.byType(PrivacyPolicyScreen), findsOneWidget);
        expect(find.byType(WebViewWidget), findsNothing);
        expect(find.byType(SelectionArea), findsOneWidget);
        expect(tester.takeException(), isNull);

        for (final heading in [
          'Deleting your account',
          'Records kept after deletion',
          'Contact Kainat Travels',
        ]) {
          await tester.ensureVisible(find.text(heading));
          await tester.pumpAndSettle();
          expect(find.text(heading).hitTestable(), findsOneWidget);
          expect(tester.takeException(), isNull);
        }
        final contact = find.textContaining('Email: info@kainattravels.com');
        await tester.ensureVisible(contact);
        await tester.pumpAndSettle();
        expect(contact.hitTestable(), findsOneWidget);

        await tester.pageBack();
        await tester.pumpAndSettle();
        expect(find.byType(PrivacyPolicyScreen), findsNothing);
        expect(find.byType(LegalLinks), findsOneWidget);
      },
    );
  }

  testWidgets('reading privacy before signup preserves form and consent', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        child: MaterialApp(theme: AppTheme.light, home: const RegisterScreen()),
      ),
    );
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextFormField).first, 'Test Traveller');
    await tester.ensureVisible(find.text('Privacy policy'));
    await tester.tap(find.text('Privacy policy'));
    await tester.pumpAndSettle();
    expect(find.byType(PrivacyPolicyScreen), findsOneWidget);
    expect(find.byType(WebViewWidget), findsNothing);

    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.text('Test Traveller'), findsOneWidget);
    // Merely reading the policy must not accept it on the passenger's behalf.
    expect(
      tester.widget<CheckboxListTile>(find.byType(CheckboxListTile)).value,
      isFalse,
    );
    expect(tester.takeException(), isNull);
  });
}
