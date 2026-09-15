import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/config/app_environment.dart';
import 'package:kt_mobile_app/features/payments/domain/booking_payment.dart';
import 'package:kt_mobile_app/features/payments/presentation/payment_screen.dart';
import 'package:kt_mobile_app/features/payments/presentation/payment_method_selector.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';

BookingSummary booking({String state = 'pending', String? checkoutUrl}) =>
    BookingSummary.fromJson({
      'id': 42,
      'reference': 'KT-00000042',
      'status': state == 'paid' ? 'confirmed' : state,
      'payment_status': ['paid', 'review_required'].contains(state)
          ? 'paid'
          : state,
      'origin_name': 'Lahore',
      'destination_name': 'Islamabad',
      'departure_at': '2099-09-12T12:00:00+05:00',
      'seats': ['1'],
      'total': 2500,
      'payment': {
        'id': 'payment-test',
        'method': 'jazzcash',
        'status': state,
        'expires_at': '2099-09-12T12:00:00+05:00',
        'checkout_url': checkoutUrl,
      },
    });

class FakeTravelRepository implements TravelRepository {
  FakeTravelRepository(this.result);
  BookingSummary result;
  int refreshes = 0;
  bool failRefresh = false;
  @override
  Future<BookingSummary> refreshPayment(int id) async {
    refreshes++;
    if (failRefresh) throw Exception('Unable to connect. Try again.');
    return result;
  }

  @override
  Future<BookingSummary> booking(int id) async => result;
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class PreviewTravelRepository extends FakeTravelRepository {
  PreviewTravelRepository({this.previewOnly = true}) : super(booking());
  final bool previewOnly;
  int submissions = 0;
  String? submittedMethod;
  String? submittedQuote;

  @override
  Future<FareQuote> quote({
    required SearchQuery query,
    required TravelSchedule schedule,
    required List<TravelSeat> seats,
    required List<PassengerDetails> passengers,
    int pointsToUse = 0,
  }) async => FareQuote.fromJson({
    'quote_token': 'preview-test',
    'base_fare': 2500,
    'total': 2500,
    'payment_methods': ['jazzcash', 'bank_alfalah'],
    'payment_environment': 'live',
    'payment_preview': previewOnly,
  });

  @override
  Future<BookingSummary> createBooking({
    required String quoteToken,
    required String paymentMethod,
  }) async {
    submissions++;
    submittedMethod = paymentMethod;
    submittedQuote = quoteToken;
    if (previewOnly) throw StateError('Preview must never submit a booking');
    return result;
  }
}

void main() {
  for (final scenario in [
    (preview: true, method: 'bank_alfalah'),
    (preview: false, method: 'jazzcash'),
    (preview: false, method: 'bank_alfalah'),
  ]) {
    testWidgets(
      'live checkout ${scenario.method}, preview: ${scenario.preview}',
      (tester) async {
        tester.view.physicalSize = const Size(1080, 2400);
        tester.view.devicePixelRatio = 2;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repository = PreviewTravelRepository(
          previewOnly: scenario.preview,
        );
        final container = ProviderContainer(
          overrides: [
            travelRepositoryProvider.overrideWithValue(repository),
            loyaltyWalletProvider.overrideWith(
              (ref) async => LoyaltyWallet.fromJson({'active': false}),
            ),
          ],
        );
        addTearDown(container.dispose);
        const origin = CityOption(id: 1, name: 'Karachi');
        const destination = CityOption(id: 2, name: 'Rawalpindi');
        final date = DateTime(2099, 9, 12, 11);
        final flow = container.read(bookingFlowProvider.notifier);
        flow.begin(
          SearchQuery(origin: origin, destination: destination, date: date),
          TravelSchedule(
            id: 1,
            scheduleDetailId: 1,
            origin: origin,
            destination: destination,
            departureAt: date,
            busClassName: 'Test bus',
            availableSeats: 1,
            totalSeats: 1,
            fares: const [],
          ),
        );
        flow.toggleSeat(
          const TravelSeat(
            number: '1',
            row: 1,
            column: 1,
            status: SeatAvailability.available,
            price: 2500,
            classId: 1,
          ),
          1,
        );
        flow.setPassengers(const [
          PassengerDetails(
            seatNumber: '1',
            fullName: 'Test Passenger',
            cnic: '0000000000000',
            mobile: '03000000000',
            gender: 'male',
          ),
        ]);
        final router = GoRouter(
          routes: [
            GoRoute(path: '/', builder: (_, _) => const CheckoutScreen()),
            GoRoute(
              path: '/payments/:bookingId',
              builder: (_, state) => Scaffold(
                body: Text(
                  'Payment for booking ${state.pathParameters['bookingId']}',
                ),
              ),
            ),
          ],
        );
        addTearDown(router.dispose);
        await tester.pumpWidget(
          UncontrolledProviderScope(
            container: container,
            child: MaterialApp.router(routerConfig: router),
          ),
        );
        await tester.pumpAndSettle();
        expect(
          find.text('Live preview · No payments'),
          scenario.preview ? findsOneWidget : findsNothing,
        );
        expect(find.text(paymentMethodLabel('jazzcash')), findsOneWidget);
        expect(find.text('JazzCash Mobile Account'), findsOneWidget);
        expect(find.text('Debit / Credit Card'), findsOneWidget);
        await tester.tap(find.text(paymentMethodLabel(scenario.method)));
        await tester.pumpAndSettle();
        final submit = find.widgetWithText(
          FilledButton,
          scenario.preview
              ? 'Preview only · Payments disabled'
              : 'Continue · Rs. 2,500',
        );
        expect(
          tester.widget<FilledButton>(submit).onPressed,
          scenario.preview ? isNull : isNotNull,
        );
        await tester.tap(submit);
        await tester.pumpAndSettle();
        expect(repository.submissions, scenario.preview ? 0 : 1);
        if (!scenario.preview) {
          expect(repository.submittedMethod, scenario.method);
          expect(repository.submittedQuote, 'preview-test');
          expect(find.text('Payment for booking 42'), findsOneWidget);
        }
        await tester.pumpWidget(const SizedBox());
      },
    );
  }

  testWidgets(
    'live gateways stay visible but unavailable without API approval',
    (tester) async {
      String? selected;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: PaymentMethodSelector(
              availableMethods: const ['counter'],
              environment: 'live',
              selectedMethod: 'counter',
              onSelected: (method) => selected = method,
              onRetry: () {},
            ),
          ),
        ),
      );
      expect(find.text('Currently unavailable'), findsNWidgets(2));
      expect(find.text('Currently unavailable in sandbox'), findsNothing);
      await tester.tap(find.text('JazzCash Mobile Account'));
      await tester.tap(find.text('Debit / Credit Card'));
      expect(selected, isNull);
      await tester.tap(find.text('Pay at Branch'));
      expect(selected, 'counter');
    },
  );

  testWidgets('unavailable sandbox gateways cannot be selected and can retry', (
    tester,
  ) async {
    String? selected;
    var retries = 0;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: PaymentMethodSelector(
            availableMethods: const [],
            environment: 'sandbox',
            selectedMethod: 'jazzcash',
            onSelected: (method) => selected = method,
            onRetry: () => retries++,
          ),
        ),
      ),
    );
    expect(find.text('Sandbox · Test payments'), findsOneWidget);
    expect(find.text('Currently unavailable in sandbox'), findsNWidgets(2));
    await tester.tap(find.text(paymentMethodLabel('jazzcash')));
    await tester.tap(find.text(paymentMethodLabel('bank_alfalah')));
    expect(selected, isNull);
    await tester.tap(find.text('Check again'));
    expect(retries, 1);
  });

  testWidgets('only gateways offered by the API can be selected', (
    tester,
  ) async {
    String? selected;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: PaymentMethodSelector(
            availableMethods: const ['jazzcash'],
            environment: 'sandbox',
            selectedMethod: null,
            onSelected: (method) => selected = method,
            onRetry: () {},
          ),
        ),
      ),
    );
    await tester.tap(find.text(paymentMethodLabel('bank_alfalah')));
    expect(selected, isNull);
    await tester.tap(find.text(paymentMethodLabel('jazzcash')));
    expect(selected, 'jazzcash');
    expect(find.text('Check again'), findsNothing);
  });

  test(
    'HTTP checkout is accepted only with explicit local sandbox permission',
    () {
      const base = 'http://10.0.2.2:8000/api/mobile/v1';
      const url = '$base/payments/abc/checkout?signature=test';
      expect(trustedCheckoutUri(url, base), isNull);
      expect(
        trustedCheckoutUri(url, base, allowLocalSandbox: true),
        Uri.parse(url),
      );
      expect(
        trustedCheckoutUri(
          url.replaceFirst('10.0.2.2', 'example.com'),
          base.replaceFirst('10.0.2.2', 'example.com'),
          allowLocalSandbox: true,
        ),
        isNull,
      );
    },
  );
  test(
    'checkout URLs must be signed HTTPS URLs on the configured API origin',
    () {
      const base = 'https://mobile.example.test/api/mobile/v1';
      const valid = '$base/payments/abc/checkout?signature=test&expires=999';
      expect(trustedCheckoutUri(valid, base), Uri.parse(valid));
      for (final url in [
        valid.replaceFirst('https:', 'http:'),
        valid.replaceFirst(
          'mobile.example.test',
          'mobile.example.test.attacker.test',
        ),
        valid.replaceFirst('mobile.example.test', 'user@mobile.example.test'),
        '$valid#fragment',
        '$base/payments/abc/checkout',
        'https://mobile.example.test/other/checkout?signature=test',
        'javascript:alert(1)',
      ]) {
        expect(trustedCheckoutUri(url, base), isNull, reason: url);
      }
    },
  );

  test('a received payment requiring review is not a confirmed ticket', () {
    final result = booking(state: 'review_required');
    expect(result.paymentStatus, 'paid');
    expect(result.status, 'review_required');
    expect(result.payment!.checkoutUrl, isNull);
  });

  testWidgets('pending payment remains pending until the backend confirms it', (
    tester,
  ) async {
    final repository = FakeTravelRepository(booking());
    await pumpPayment(tester, repository);
    expect(find.text('Complete your payment'), findsOneWidget);
    expect(find.text('View ticket'), findsNothing);
    repository.result = booking(state: 'paid');
    await tester.tap(find.text('Check payment status'));
    await tester.pumpAndSettle();
    expect(find.text('Payment received'), findsOneWidget);
    expect(find.text('View ticket'), findsOneWidget);
    expect(repository.refreshes, 2);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets(
    'network failure preserves pending state and allows checking again',
    (tester) async {
      final repository = FakeTravelRepository(booking())..failRefresh = true;
      await pumpPayment(tester, repository);
      expect(find.textContaining('Unable to connect.'), findsOneWidget);
      expect(find.text('View ticket'), findsNothing);
      repository.failRefresh = false;
      await tester.tap(find.text('Check payment status'));
      await tester.pumpAndSettle();
      expect(find.textContaining('Unable to connect.'), findsNothing);
      await tester.pumpWidget(const SizedBox());
    },
  );

  for (final state in ['expired', 'review_required']) {
    testWidgets('$state never offers checkout or a confirmed ticket', (
      tester,
    ) async {
      await pumpPayment(tester, FakeTravelRepository(booking(state: state)));
      expect(find.text('View ticket'), findsNothing);
      expect(find.text('Open secure checkout'), findsNothing);
      expect(find.text('Booking details & support'), findsOneWidget);
      await tester.pumpWidget(const SizedBox());
    });
  }

  testWidgets('opening a payment URL does not mark a booking paid', (
    tester,
  ) async {
    final api = Uri.parse(AppEnvironmentConfig.apiBaseUrl);
    // Keep the configured port; this test never opens a browser or makes a request.
    final url = api
        .replace(
          scheme: 'https',
          port: api.port,
          path: '${api.path}/payments/abc/checkout',
          query: 'signature=test',
        )
        .toString();
    var launches = 0;
    await pumpPayment(
      tester,
      FakeTravelRepository(booking(checkoutUrl: url)),
      launcher: (uri) async {
        launches++;
        return true;
      },
    );
    await tester.tap(find.text('Open secure checkout'));
    await tester.pumpAndSettle();
    expect(launches, 1);
    expect(find.text('View ticket'), findsNothing);
    expect(find.text('Complete your payment'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });
}

Future<void> pumpPayment(
  WidgetTester tester,
  FakeTravelRepository repository, {
  Future<bool> Function(Uri)? launcher,
}) async {
  final router = GoRouter(
    routes: [
      GoRoute(
        path: '/',
        builder: (_, _) =>
            PaymentScreen(bookingId: 42, initialBooking: repository.result),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        travelRepositoryProvider.overrideWithValue(repository),
        if (launcher != null)
          paymentLauncherProvider.overrideWithValue(launcher),
      ],
      child: MaterialApp.router(routerConfig: router),
    ),
  );
  await tester.pumpAndSettle();
}
