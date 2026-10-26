import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/config/app_environment.dart';
import 'package:kt_mobile_app/features/payments/domain/booking_payment_action.dart';
import 'package:kt_mobile_app/features/payments/presentation/payment_screen.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/tickets/tickets_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:qr_flutter/qr_flutter.dart';

import 'payment_test.dart' show booking, FakeTravelRepository, pumpPayment;

String get checkoutUrl {
  final api = Uri.parse(AppEnvironmentConfig.apiBaseUrl);
  return api
      .replace(
        scheme: 'https',
        port: api.port,
        path: '${api.path}/payments/test/checkout',
        query: 'signature=test',
      )
      .toString();
}

class DelayedPaymentRepository extends FakeTravelRepository {
  DelayedPaymentRepository(super.result);
  Completer<BookingSummary>? verification;

  @override
  Future<BookingSummary> refreshPayment(int id) {
    if (verification == null) return super.refreshPayment(id);
    refreshes++;
    return verification!.future;
  }
}

void main() {
  final scenarios =
      <({String name, BookingSummary value, BookingPaymentAction action})>[
        (
          name: 'unstarted reservation',
          value: booking(checkoutUrl: checkoutUrl),
          action: BookingPaymentAction.completePayment,
        ),
        (
          name: 'started checkout',
          value: booking(),
          action: BookingPaymentAction.checkPaymentStatus,
        ),
        (
          name: 'expired reservation',
          value: booking(state: 'expired'),
          action: BookingPaymentAction.bookAgain,
        ),
        (
          name: 'expired booking with unresolved payment',
          value: booking(bookingStatus: 'expired', checkoutUrl: checkoutUrl),
          action: BookingPaymentAction.checkPaymentStatus,
        ),
        (
          name: 'local deadline without server expiry',
          value: booking(
            checkoutUrl: checkoutUrl,
            expiresAt: '2000-01-01T00:00:00Z',
          ),
          action: BookingPaymentAction.checkPaymentStatus,
        ),
        (
          name: 'payment received but seats unconfirmed',
          value: booking(state: 'review_required'),
          action: BookingPaymentAction.checkPaymentStatus,
        ),
        (
          name: 'unrecognized failure has no retry contract',
          value: booking(state: 'failed', checkoutUrl: checkoutUrl),
          action: BookingPaymentAction.checkPaymentStatus,
        ),
        (
          name: 'confirmed ticket',
          value: booking(state: 'paid', qrValue: 'confirmed-boarding-code'),
          action: BookingPaymentAction.viewTicket,
        ),
      ];

  for (final scenario in scenarios) {
    testWidgets('ticket actions: ${scenario.name}', (tester) async {
      final repository = FakeTravelRepository(scenario.value);
      var launches = 0;
      await pumpTickets(tester, repository, onCheckout: () => launches++);
      expect(bookingPaymentAction(scenario.value), scenario.action);
      for (final action in [
        BookingPaymentAction.completePayment,
        BookingPaymentAction.checkPaymentStatus,
        BookingPaymentAction.bookAgain,
      ]) {
        expect(
          find.widgetWithText(FilledButton, action.label),
          action == scenario.action ? findsOneWidget : findsNothing,
        );
      }
      expect(
        find.byType(QrImageView),
        scenario.action == BookingPaymentAction.viewTicket
            ? findsOneWidget
            : findsNothing,
      );
      if (scenario.action == BookingPaymentAction.bookAgain) {
        expect(
          find.widgetWithText(TextButton, 'View payment status'),
          findsOneWidget,
        );
        await tester.tap(find.text('Book again'));
        await tester.pumpAndSettle();
        expect(find.text('New trip search'), findsOneWidget);
      }
      if (scenario.action == BookingPaymentAction.completePayment) {
        expect(find.textContaining('Time left to pay:'), findsOneWidget);
        await tester.tap(find.text('Complete payment'));
        await tester.pumpAndSettle();
        expect(launches, 1);
        expect(find.text('Payment awaiting verification'), findsOneWidget);
      }
      await tester.pumpWidget(const SizedBox());
    });
  }

  testWidgets('repeated Complete payment taps open only one payment route', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking(checkoutUrl: checkoutUrl));
    var launches = 0;
    await pumpTickets(tester, repository, onCheckout: () => launches++);
    final pay = tester
        .widget<FilledButton>(
          find.widgetWithText(FilledButton, 'Complete payment'),
        )
        .onPressed!;
    pay();
    pay();
    await tester.pumpAndSettle();
    expect(launches, 1);
    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    expect(find.text('Booking: pending'), findsOneWidget);
    expect(find.text('Payment awaiting verification'), findsNothing);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('expired payment page offers a new search', (tester) async {
    await pumpTickets(
      tester,
      FakeTravelRepository(booking(state: 'expired')),
      location: '/payments/42',
    );
    await tester.tap(find.text('Book again'));
    await tester.pumpAndSettle();
    expect(find.text('New trip search'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('ticket verification failure keeps details and blocks payment', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking(checkoutUrl: checkoutUrl))
      ..failRefresh = true;
    await pumpTickets(tester, repository);
    expect(find.text('KT-00000042'), findsOneWidget);
    expect(find.textContaining('Could not verify payment.'), findsOneWidget);
    expect(find.text('Complete payment'), findsNothing);
    expect(find.text('Check payment status'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('ticket load failure can be retried', (tester) async {
    final repository = FakeTravelRepository(booking(state: 'expired'))
      ..failBooking = true;
    await pumpTickets(tester, repository);
    expect(find.text('Ticket unavailable'), findsOneWidget);
    repository.failBooking = false;
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();
    expect(find.text('Book again'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('returning from details refreshes a stale trip list', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking());
    await pumpTickets(tester, repository, location: '/tickets');
    expect(find.text('Pending'), findsOneWidget);
    repository.result = booking(state: 'expired');
    await tester.tap(find.text('KT-00000042'));
    await tester.pumpAndSettle();
    expect(find.text('Booking: expired'), findsOneWidget);
    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    expect(find.text('Expired'), findsOneWidget);
    expect(find.text('Pending'), findsNothing);
    expect(repository.listReads, greaterThan(1));
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('deep linked payment verifies its transaction before checkout', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking(checkoutUrl: checkoutUrl));
    await pumpTickets(tester, repository, location: '/payments/42');
    expect(repository.bookingReads, 1);
    expect(repository.refreshes, 1);
    expect(find.text('Complete payment'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  for (final state in ['expired', 'paid', 'review_required', 'pending']) {
    testWidgets('checkout preflight sees $state and does not reuse old URL', (
      tester,
    ) async {
      final repository = FakeTravelRepository(
        booking(checkoutUrl: checkoutUrl),
      );
      var launches = 0;
      await pumpPayment(
        tester,
        repository,
        launcher: (_) async {
          launches++;
          return true;
        },
      );
      repository.result = booking(state: state);
      await tester.tap(find.text('Complete payment'));
      await tester.pumpAndSettle();
      expect(launches, 0);
      expect(find.text('Complete payment'), findsNothing);
      expect(
        find.text('View ticket'),
        state == 'paid' ? findsOneWidget : findsNothing,
      );
      await tester.pumpWidget(const SizedBox());
    });
  }

  testWidgets('failed preflight does not launch a cached checkout URL', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking(checkoutUrl: checkoutUrl));
    var launches = 0;
    await pumpPayment(
      tester,
      repository,
      launcher: (_) async {
        launches++;
        return true;
      },
    );
    repository.failRefresh = true;
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    expect(launches, 0);
    expect(find.text('Complete payment'), findsNothing);
    expect(find.text('Check payment status'), findsOneWidget);
    repository.failRefresh = false;
    await tester.tap(find.text('Check payment status'));
    await tester.pumpAndSettle();
    expect(find.text('Complete payment'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('repeated taps while verifying launch checkout only once', (
    tester,
  ) async {
    final repository = DelayedPaymentRepository(
      booking(checkoutUrl: checkoutUrl),
    );
    var launches = 0;
    await pumpPayment(
      tester,
      repository,
      launcher: (_) async {
        launches++;
        repository.verification = null;
        repository.result = booking();
        return true;
      },
    );
    final verification = Completer<BookingSummary>();
    repository.verification = verification;
    await tester.tap(find.text('Complete payment'));
    await tester.tap(find.text('Complete payment'));
    await tester.pump();
    expect(launches, 0);
    expect(repository.refreshes, 2);
    verification.complete(repository.result);
    await tester.pumpAndSettle();
    expect(launches, 1);
    expect(find.text('Complete payment'), findsNothing);
    await tester.pumpWidget(const SizedBox());
  });

  test(
    'countdown reaches zero without treating local time as server expiry',
    () {
      final expires = DateTime.utc(2026, 9, 17, 12);
      expect(
        paymentTimeRemaining(
          expires,
          now: expires.subtract(const Duration(seconds: 125)),
        ),
        'Time left to pay: 2:05',
      );
      expect(
        paymentTimeRemaining(expires, now: expires),
        contains('Check payment status'),
      );
      expect(
        bookingPaymentAction(
          booking(checkoutUrl: checkoutUrl),
          now: DateTime(2100),
        ),
        BookingPaymentAction.checkPaymentStatus,
      );
    },
  );
}

Future<void> pumpTickets(
  WidgetTester tester,
  FakeTravelRepository repository, {
  String location = '/tickets/42',
  VoidCallback? onCheckout,
}) async {
  tester.view.physicalSize = const Size(500, 1100);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  final router = GoRouter(
    initialLocation: location,
    routes: [
      GoRoute(path: '/tickets', builder: (_, _) => const TicketsScreen()),
      GoRoute(
        path: '/tickets/:id',
        builder: (_, _) => const TicketDetailScreen(bookingId: 42),
      ),
      GoRoute(
        path: '/payments/:id',
        builder: (_, state) => PaymentScreen(
          bookingId: 42,
          openCheckoutOnEntry: state.uri.queryParameters['checkout'] == '1',
        ),
      ),
      GoRoute(
        path: '/search',
        builder: (_, _) => const Scaffold(body: Text('New trip search')),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        passengerSessionProvider.overrideWithValue((
          passengerId: 1,
          generation: 0,
        )),
        travelRepositoryProvider.overrideWithValue(repository),
        paymentLauncherProvider.overrideWithValue((_, uri) async {
          onCheckout?.call();
          repository.result = booking();
          return true;
        }),
        mobileAppConfigProvider.overrideWithValue(null),
      ],
      child: MaterialApp.router(routerConfig: router),
    ),
  );
  await tester.pumpAndSettle();
}
