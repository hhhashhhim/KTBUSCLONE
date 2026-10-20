import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/features/payments/presentation/payment_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';

import 'payment_test.dart' show booking, FakeTravelRepository;

void main() {
  for (final systemBack in [false, true]) {
    for (final hasHistory in [false, true]) {
      testWidgets(
        '${systemBack ? 'Android' : 'header'} back ${hasHistory ? 'returns to prior screen' : 'falls back to My Bookings'}',
        (tester) async {
          final repository = FakeTravelRepository(booking());
          final router = GoRouter(
            initialLocation: hasHistory ? '/details' : '/payments/42',
            routes: [
              GoRoute(
                path: '/details',
                builder: (context, _) => Scaffold(
                  body: TextButton(
                    onPressed: () => context.push('/payments/42'),
                    child: const Text('Booking details'),
                  ),
                ),
              ),
              GoRoute(
                path: '/payments/:id',
                builder: (_, _) => PaymentScreen(
                  bookingId: 42,
                  initialBooking: repository.result,
                ),
              ),
              GoRoute(
                path: '/tickets',
                builder: (_, _) =>
                    const Scaffold(body: Text('My Bookings destination')),
              ),
            ],
          );
          addTearDown(router.dispose);
          await tester.pumpWidget(
            ProviderScope(
              overrides: [
                travelRepositoryProvider.overrideWithValue(repository),
              ],
              child: MaterialApp.router(routerConfig: router),
            ),
          );
          await tester.pumpAndSettle();
          if (hasHistory) {
            await tester.tap(find.text('Booking details'));
            await tester.pumpAndSettle();
          }
          expect(find.text('Payment awaiting verification'), findsOneWidget);
          if (systemBack) {
            await tester.binding.handlePopRoute();
          } else {
            await tester.tap(find.byIcon(Icons.chevron_left_rounded));
          }
          await tester.pumpAndSettle();
          expect(
            router.routeInformationProvider.value.uri.path,
            hasHistory ? '/details' : '/tickets',
          );
          expect(
            find.text(
              hasHistory ? 'Booking details' : 'My Bookings destination',
            ),
            findsOneWidget,
          );
          // Leaving the screen is navigation only, not cancellation or payment.
          expect(repository.result.payment!.status, 'pending');
          await tester.pumpWidget(const SizedBox());
        },
      );
    }
  }
}
