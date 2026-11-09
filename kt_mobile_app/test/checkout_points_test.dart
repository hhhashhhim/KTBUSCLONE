import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';

class _PointsRepository implements TravelRepository {
  _PointsRepository({
    this.paymentMethod = 'counter',
    this.serverQuotes = const {},
  });

  final String paymentMethod;
  final Map<int, Map<String, num>> serverQuotes;
  final requestedPoints = <int>[];
  Completer<FareQuote>? pendingQuote;
  Object? quoteError;

  FareQuote result(int points) => FareQuote.fromJson({
    'quote_token': 'quote-$points',
    'base_fare': 2500,
    'total': points == 0 ? 2500 : 2300,
    'wallet_points_used': points,
    'wallet_deduction': points == 0 ? 0 : 200,
    'payment_methods': [paymentMethod],
    ...?serverQuotes[points],
  });

  @override
  Future<FareQuote> quote({
    required SearchQuery query,
    required TravelSchedule schedule,
    required List<TravelSeat> seats,
    required List<PassengerDetails> passengers,
    int pointsToUse = 0,
  }) async {
    requestedPoints.add(pointsToUse);
    if (quoteError != null) throw quoteError!;
    return pendingQuote?.future ?? result(pointsToUse);
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<ProviderContainer> _pumpCheckout(
  WidgetTester tester,
  _PointsRepository repository,
) async {
  tester.view.physicalSize = const Size(640, 1600);
  tester.view.devicePixelRatio = 2;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  final container = ProviderContainer(
    overrides: [
      travelRepositoryProvider.overrideWithValue(repository),
      loyaltyWalletProvider.overrideWith(
        (ref, session) async => const LoyaltyWallet(
          active: true,
          pointsAvailable: 250,
          flatDiscountPerPoint: 1,
        ),
      ),
    ],
  );
  addTearDown(container.dispose);
  const origin = CityOption(id: 1, name: 'Lahore');
  const destination = CityOption(id: 2, name: 'Islamabad');
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
      row: 0,
      column: 0,
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
        path: '/tickets',
        builder: (_, _) => const Scaffold(body: Text('My trips destination')),
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
  return container;
}

Finder _pointsControl(int selected) => find.ancestor(
  of: find.text('$selected'),
  matching: find.byWidgetPredicate((widget) => widget is OutlinedButton),
);

Future<void> _openEntry(WidgetTester tester, int selected) async {
  final control = _pointsControl(selected);
  await _reveal(tester, control);
  await tester.tap(control);
  await tester.pumpAndSettle();
  expect(find.byType(AlertDialog), findsOneWidget);
}

Future<void> _reveal(WidgetTester tester, Finder finder) async {
  await tester.scrollUntilVisible(finder, 200);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('pending reservation limit directs checkout to My Trips', (
    tester,
  ) async {
    final repository = _PointsRepository()
      ..quoteError = const ApiException(
        'Please complete your existing reservations before making another booking.',
        statusCode: 409,
      );
    await _pumpCheckout(tester, repository);

    expect(find.text('Complete your existing reservations'), findsOneWidget);
    expect(find.text('Could not confirm fare'), findsNothing);
    expect(find.text('Try again'), findsNothing);
    expect(find.text('Reserve · Rs. 2,500'), findsNothing);
    await tester.tap(find.text('View my trips'));
    await tester.pumpAndSettle();
    expect(find.text('My trips destination'), findsOneWidget);
    expect(repository.requestedPoints, [0]);
    expect(tester.takeException(), isNull);
  });

  testWidgets('other quote conflicts keep the fare retry action', (
    tester,
  ) async {
    final repository = _PointsRepository()
      ..quoteError = const ApiException(
        'One or more selected seats are no longer available.',
        statusCode: 409,
      );
    await _pumpCheckout(tester, repository);

    expect(find.text('Could not confirm fare'), findsOneWidget);
    expect(find.text('View my trips'), findsNothing);
    expect(find.text('Reserve · Rs. 2,500'), findsNothing);
    repository.quoteError = null;
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();
    expect(find.text('Reserve · Rs. 2,500'), findsOneWidget);
    expect(repository.requestedPoints, [0, 0]);
    expect(tester.takeException(), isNull);
  });

  for (final method in ['counter', 'bank_alfalah']) {
    testWidgets(
      '$method button and summary follow every applied points quote',
      (tester) async {
        final repository = _PointsRepository(
          paymentMethod: method,
          serverQuotes: const {
            0: {'total': 2500, 'wallet_deduction': 0},
            50: {'total': 2450, 'wallet_deduction': 50},
            125: {'total': 2375, 'wallet_deduction': 125},
            250: {'total': 2250, 'wallet_deduction': 250},
          },
        );
        await _pumpCheckout(tester, repository);
        final action = method == 'counter' ? 'Reserve' : 'Continue';
        var selected = 0;
        for (final example in [
          (points: 50, total: 'Rs. 2,450', deduction: '-Rs. 50'),
          (points: 125, total: 'Rs. 2,375', deduction: '-Rs. 125'),
          (points: 250, total: 'Rs. 2,250', deduction: '-Rs. 250'),
          (points: 0, total: 'Rs. 2,500', deduction: null),
        ]) {
          await _openEntry(tester, selected);
          await tester.enterText(
            find.byType(TextFormField),
            '${example.points}',
          );
          await tester.tap(find.text('Apply'));
          await tester.pumpAndSettle();
          expect(find.text('$action · ${example.total}'), findsOneWidget);
          final totalRow = find
              .ancestor(
                of: find.text('Total amount'),
                matching: find.byType(Row),
              )
              .first;
          expect(
            find.descendant(of: totalRow, matching: find.text(example.total)),
            findsOneWidget,
          );
          if (example.deduction == null) {
            expect(find.text('Wallet'), findsNothing);
          } else {
            expect(find.text('Wallet'), findsOneWidget);
            expect(find.text(example.deduction!), findsOneWidget);
          }
          selected = example.points;
        }
        expect(repository.requestedPoints, [0, 50, 125, 250, 0]);
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets('manual entry requests one updated fare only when applied', (
    tester,
  ) async {
    final repository = _PointsRepository();
    await _pumpCheckout(tester, repository);
    await _openEntry(tester, 0);
    await tester.enterText(find.byType(TextFormField), '1');
    await tester.enterText(find.byType(TextFormField), '12');
    await tester.enterText(find.byType(TextFormField), '125');
    expect(repository.requestedPoints, [0]);

    final pending = Completer<FareQuote>();
    repository.pendingQuote = pending;
    await tester.tap(find.text('Apply'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));
    expect(repository.requestedPoints, [0, 125]);
    expect(find.text('Confirming the latest fare…'), findsOneWidget);
    expect(find.textContaining('Reserve ·'), findsNothing);

    pending.complete(repository.result(125));
    await tester.pumpAndSettle();
    await _reveal(tester, _pointsControl(125));
    expect(_pointsControl(125), findsOneWidget);
    expect(find.text('Reserve · Rs. 2,300'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('invalid amounts cannot be applied; the exact limit is allowed', (
    tester,
  ) async {
    final repository = _PointsRepository();
    await _pumpCheckout(tester, repository);
    await _openEntry(tester, 0);

    for (final input in ['251', '999999999999999999999999999', '']) {
      await tester.enterText(find.byType(TextFormField), input);
      await tester.tap(find.text('Apply'));
      await tester.pumpAndSettle();
      expect(find.byType(AlertDialog), findsOneWidget);
      expect(repository.requestedPoints, [0]);
      expect(
        find.text(
          input == '251'
              ? 'You can use up to 250 points.'
              : 'Enter a whole number of points.',
        ),
        findsOneWidget,
      );
    }

    await tester.enterText(find.byType(TextFormField), '250');
    await tester.testTextInput.receiveAction(TextInputAction.done);
    await tester.pumpAndSettle();
    expect(find.byType(AlertDialog), findsNothing);
    expect(repository.requestedPoints, [0, 250]);
    await _reveal(tester, find.byTooltip('Use more points'));
    await tester.tap(find.byTooltip('Use more points'));
    await tester.pumpAndSettle();
    expect(repository.requestedPoints, [0, 250]);
    expect(tester.takeException(), isNull);
  });

  testWidgets('manual entry and step buttons stay in sync, including zero', (
    tester,
  ) async {
    final repository = _PointsRepository();
    await _pumpCheckout(tester, repository);
    await _openEntry(tester, 0);
    await tester.enterText(find.byType(TextFormField), '125');
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();

    for (final tooltip in ['Use more points', 'Use fewer points']) {
      await _reveal(tester, find.byTooltip(tooltip));
      await tester.tap(find.byTooltip(tooltip));
      await tester.pumpAndSettle();
    }
    expect(repository.requestedPoints, [0, 125, 126, 125]);
    await _openEntry(tester, 125);
    expect(
      tester.widget<TextFormField>(find.byType(TextFormField)).controller!.text,
      '125',
    );
    await tester.enterText(find.byType(TextFormField), '0');
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();
    expect(repository.requestedPoints, [0, 125, 126, 125, 0]);
    expect(find.text('Reserve · Rs. 2,500'), findsOneWidget);
    await _reveal(tester, find.byTooltip('Use fewer points'));
    await tester.tap(find.byTooltip('Use fewer points'));
    await tester.pumpAndSettle();
    expect(repository.requestedPoints, [0, 125, 126, 125, 0]);
    expect(tester.takeException(), isNull);
  });

  testWidgets('cancel and unchanged values preserve the existing fare', (
    tester,
  ) async {
    final repository = _PointsRepository();
    await _pumpCheckout(tester, repository);
    await _openEntry(tester, 0);
    await tester.enterText(find.byType(TextFormField), '100');
    await tester.tap(find.text('Cancel'));
    await tester.pumpAndSettle();
    expect(_pointsControl(0), findsOneWidget);
    await _openEntry(tester, 0);
    await tester.tap(find.text('Apply'));
    await tester.pumpAndSettle();
    expect(repository.requestedPoints, [0]);
    expect(tester.takeException(), isNull);
  });
}
