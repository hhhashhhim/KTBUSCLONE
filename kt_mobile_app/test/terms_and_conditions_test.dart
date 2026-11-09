import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/core/widgets/legal_links.dart';
import 'package:kt_mobile_app/core/widgets/terms_and_conditions_screen.dart';
import 'package:kt_mobile_app/features/auth/presentation/auth_screens.dart';
import 'package:kt_mobile_app/features/profile/profile_screen.dart';
import 'package:webview_flutter/webview_flutter.dart';

void main() {
  for (final viewport in [
    (size: const Size(320, 568), scale: 2.0),
    (size: const Size(568, 320), scale: 2.0),
    (size: const Size(1024, 768), scale: 1.0),
  ]) {
    testWidgets(
      'terms open offline and scroll at ${viewport.size}, scale ${viewport.scale}',
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
        await tester.tap(find.text('Terms & conditions'));
        await tester.pumpAndSettle();

        expect(find.byType(TermsAndConditionsScreen), findsOneWidget);
        expect(find.byType(WebViewWidget), findsNothing);
        expect(find.byType(SelectionArea), findsOneWidget);
        expect(tester.takeException(), isNull);

        for (final heading in [
          'Arrival Time',
          'Luggage Policy',
          'Complimentary Services',
          'Safety Regulations',
          'Personal Belongings',
          'Operational Changes',
          'Liability',
          'Amendments to Terms and Conditions',
          'Need help booking?',
        ]) {
          await tester.ensureVisible(find.text(heading));
          await tester.pumpAndSettle();
          expect(find.text(heading).hitTestable(), findsOneWidget);
          expect(tester.takeException(), isNull);
        }
        final phone = find.text('03-111-777-333');
        await tester.ensureVisible(phone);
        await tester.pumpAndSettle();
        expect(phone.hitTestable(), findsOneWidget);

        await tester.pageBack();
        await tester.pumpAndSettle();
        expect(find.byType(TermsAndConditionsScreen), findsNothing);
        expect(find.byType(LegalLinks), findsOneWidget);
      },
    );
  }

  for (final accepted in [false, true]) {
    testWidgets('reading terms preserves signup form and consent=$accepted', (
      tester,
    ) async {
      await tester.pumpWidget(
        ProviderScope(
          child: MaterialApp(
            theme: AppTheme.light,
            home: const RegisterScreen(),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byType(TextFormField).first,
        'Test Traveller',
      );
      if (accepted) {
        await tester.ensureVisible(find.byType(CheckboxListTile));
        await tester.tap(find.byType(CheckboxListTile));
        await tester.pumpAndSettle();
      }
      await tester.ensureVisible(find.text('Terms & conditions'));
      await tester.tap(find.text('Terms & conditions'));
      await tester.pumpAndSettle();
      expect(find.byType(TermsAndConditionsScreen), findsOneWidget);
      expect(find.byType(WebViewWidget), findsNothing);

      await tester.pageBack();
      await tester.pumpAndSettle();
      expect(find.text('Test Traveller'), findsOneWidget);
      // Reading terms must not change the passenger's consent choice.
      expect(
        tester.widget<CheckboxListTile>(find.byType(CheckboxListTile)).value,
        accepted,
      );
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('Account opens native terms and back returns to Account', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        child: MaterialApp(theme: AppTheme.light, home: const ProfileScreen()),
      ),
    );
    await tester.pumpAndSettle();
    final terms = find.text('Terms & conditions');
    await tester.scrollUntilVisible(terms, 300);
    await tester.pumpAndSettle();
    await tester.ensureVisible(terms);
    await tester.pumpAndSettle();
    await tester.tap(terms);
    await tester.pumpAndSettle();
    expect(find.byType(TermsAndConditionsScreen), findsOneWidget);
    expect(find.byType(WebViewWidget), findsNothing);

    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.byType(TermsAndConditionsScreen), findsNothing);
    expect(find.byType(ProfileScreen), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
