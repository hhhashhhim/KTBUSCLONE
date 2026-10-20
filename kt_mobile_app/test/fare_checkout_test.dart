import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart'
    as current;
import 'package:kt_mobile_app/features/travel/presentation/search_flow.dart'
    as legacy;

class _QuoteRepository implements TravelRepository {
  _QuoteRepository(this.response);
  final Map<String, dynamic> response;

  @override
  Future<FareQuote> quote({
    required SearchQuery query,
    required TravelSchedule schedule,
    required List<TravelSeat> seats,
    required List<PassengerDetails> passengers,
    int pointsToUse = 0,
  }) async => FareQuote.fromJson({
    'quote_token': 'server-quote',
    'payment_methods': ['counter'],
    ...response,
  });

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  test('fare formatting preserves paisa and signed deductions', () {
    expect(current.formatPkr(2449.75), 'Rs. 2,449.75');
    expect(current.formatPkr(-18.75), '-Rs. 18.75');
    expect(current.formatPkr(2500), 'Rs. 2,500');
    expect(legacy.formatPkr(2449.75), 'PKR 2,449.75');
  });

  final scenarios = <String, Map<String, dynamic>>{
    'ERP surcharge takes precedence over eligible discounts': {
      'base_fare': 2500,
      'discount': 0,
      'surcharge': 100,
      'rounding_adjustment': 0,
      'total': 2600,
    },
    'discounts without surcharge and rounding': {
      'base_fare': 2500,
      'discount': 375,
      'surcharge': 0,
      'rounding_adjustment': 25,
      'total': 2150,
    },
    'surcharge only': {'base_fare': 2500, 'surcharge': 100, 'total': 2600},
    'fractional discount, rounding and wallet': {
      'base_fare': 2500,
      'discount': 31.25,
      'surcharge': 0,
      'rounding_adjustment': -18.75,
      'wallet_deduction': 100.25,
      'total': 2349.75,
    },
    'older response keeps server total': {'base_fare': 2500, 'total': 2600},
  };

  for (final useLegacy in [false, true]) {
    for (final scenario in scenarios.entries) {
      testWidgets(
        '${useLegacy ? 'legacy' : 'current'} checkout: ${scenario.key}',
        (tester) async {
          tester.view.physicalSize = const Size(1080, 2800);
          tester.view.devicePixelRatio = 2;
          addTearDown(tester.view.resetPhysicalSize);
          addTearDown(tester.view.resetDevicePixelRatio);
          final container = ProviderContainer(
            overrides: [
              travelRepositoryProvider.overrideWithValue(
                _QuoteRepository(scenario.value),
              ),
              loyaltyWalletProvider.overrideWith(
                (ref) async =>
                    const LoyaltyWallet(active: false, pointsAvailable: 0),
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
              price: 999, // Checkout must replace this earlier seat estimate.
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
              GoRoute(
                path: '/',
                builder: (_, _) => useLegacy
                    ? const legacy.CheckoutScreen()
                    : const current.CheckoutScreen(),
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
          final format = useLegacy ? legacy.formatPkr : current.formatPkr;
          for (final entry in {
            'Discount': -(scenario.value['discount'] as num? ?? 0),
            'Surcharge': scenario.value['surcharge'] as num? ?? 0,
            'Rounding adjustment':
                scenario.value['rounding_adjustment'] as num? ?? 0,
            'Wallet': -(scenario.value['wallet_deduction'] as num? ?? 0),
          }.entries) {
            if (entry.value == 0) {
              expect(find.text(entry.key), findsNothing);
              continue;
            }
            final row = find
                .ancestor(of: find.text(entry.key), matching: find.byType(Row))
                .first;
            expect(
              find.descendant(
                of: row,
                matching: find.text(format(entry.value)),
              ),
              findsOneWidget,
            );
          }
          final total = format(scenario.value['total'] as num);
          expect(find.text(total), findsOneWidget);
          expect(
            find.widgetWithText(
              FilledButton,
              useLegacy ? 'Continue with $total' : 'Reserve · $total',
            ),
            findsOneWidget,
          );
          expect(tester.takeException(), isNull);
          await tester.pumpWidget(const SizedBox());
        },
      );
    }
  }
}
