import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:webview_flutter_platform_interface/webview_flutter_platform_interface.dart';

import 'payment_checkout_test.dart' show checkoutUri, FakeCheckoutPlatform;
import 'payment_test.dart' show booking, FakeTravelRepository, pumpPayment;
import 'ticket_payment_actions_test.dart' show DelayedPaymentRepository;

void main() {
  for (final confirmed in [false, true]) {
    testWidgets(
      'automatic checkout returns to server status: paid=$confirmed',
      (tester) async {
        final platform = FakeCheckoutPlatform();
        WebViewPlatform.instance = platform;
        final repository = FakeTravelRepository(
          booking(checkoutUrl: checkoutUri().toString()),
        );
        await pumpPayment(tester, repository, openCheckoutOnEntry: true);
        expect(find.text('Secure payment'), findsOneWidget);
        expect(platform.controller.request!.uri, checkoutUri());
        expect(repository.refreshes, 1);
        expect(platform.controllerCount, 1);

        repository.result = booking(state: confirmed ? 'paid' : 'pending');
        if (confirmed) {
          platform.delegate.finished!(
            checkoutUri()
                .replace(
                  path: checkoutUri().path.replaceFirst('/checkout', '/return'),
                )
                .toString(),
          );
        } else {
          await tester.tap(find.byTooltip('Close payment'));
        }
        await tester.pumpAndSettle();
        expect(repository.refreshes, 2);
        expect(find.text('Secure payment'), findsNothing);
        expect(
          find.text(
            confirmed ? 'Payment received' : 'Payment awaiting verification',
          ),
          findsOneWidget,
        );
        expect(
          find.text('View ticket'),
          confirmed ? findsOneWidget : findsNothing,
        );

        tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await tester.pumpAndSettle();
        await tester.pump(const Duration(seconds: 16));
        await tester.pumpAndSettle();
        expect(platform.controllerCount, 1);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }

  testWidgets(
    'automatic checkout waits for verification and launches only once',
    (tester) async {
      final repository = DelayedPaymentRepository(
        booking(checkoutUrl: checkoutUri().toString()),
      );
      final verification = Completer<BookingSummary>();
      repository.verification = verification;
      var launches = 0;
      await pumpPayment(
        tester,
        repository,
        openCheckoutOnEntry: true,
        settle: false,
        launcher: (_) async {
          launches++;
          repository.verification = null;
          repository.result = booking();
          return true;
        },
      );
      await tester.pump();
      expect(find.text('Opening secure payment…'), findsOneWidget);
      expect(find.text('Complete payment'), findsNothing);
      await tester.pump(const Duration(seconds: 16));
      expect(launches, 0);
      expect(repository.refreshes, 1);
      verification.complete(repository.result);
      await tester.pumpAndSettle();
      expect(launches, 1);
      expect(find.text('Payment awaiting verification'), findsOneWidget);
      await tester.pumpWidget(const SizedBox());
    },
  );

  for (final state in ['expired', 'paid', 'review_required', 'pending']) {
    testWidgets(
      'automatic checkout rejects a stale URL when server says $state',
      (tester) async {
        final repository = DelayedPaymentRepository(
          booking(checkoutUrl: checkoutUri().toString()),
        );
        final verification = Completer<BookingSummary>();
        repository.verification = verification;
        var launches = 0;
        await pumpPayment(
          tester,
          repository,
          openCheckoutOnEntry: true,
          settle: false,
          launcher: (_) async {
            launches++;
            return true;
          },
        );
        verification.complete(booking(state: state));
        await tester.pumpAndSettle();
        expect(launches, 0);
        expect(find.text('Opening secure payment…'), findsNothing);
        expect(find.text('Complete payment'), findsNothing);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }

  testWidgets('failed verification requires an explicit payment retry', (
    tester,
  ) async {
    final repository = FakeTravelRepository(
      booking(checkoutUrl: checkoutUri().toString()),
    )..failRefresh = true;
    var launches = 0;
    await pumpPayment(
      tester,
      repository,
      openCheckoutOnEntry: true,
      launcher: (_) async {
        launches++;
        return true;
      },
    );
    expect(launches, 0);
    expect(find.text('Complete payment'), findsNothing);
    expect(find.textContaining('Unable to connect'), findsOneWidget);
    repository.failRefresh = false;
    await tester.tap(find.text('Check payment status'));
    await tester.pumpAndSettle();
    expect(launches, 0);
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    expect(launches, 1);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('failed checkout launch returns to a usable payment screen', (
    tester,
  ) async {
    final repository = FakeTravelRepository(
      booking(checkoutUrl: checkoutUri().toString()),
    );
    await pumpPayment(
      tester,
      repository,
      openCheckoutOnEntry: true,
      launcher: (_) async => false,
    );
    expect(find.text('Opening secure payment…'), findsNothing);
    expect(find.textContaining('Could not open checkout'), findsOneWidget);
    expect(find.text('Check payment status'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });
}
