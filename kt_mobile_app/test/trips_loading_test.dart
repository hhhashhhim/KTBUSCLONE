import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/tickets/tickets_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

import 'payment_test.dart' show booking, FakeTravelRepository;

class TripsRepository extends FakeTravelRepository {
  TripsRepository() : super(booking());

  Object? failure;
  Completer<List<BookingSummary>>? pending;

  @override
  Future<List<BookingSummary>> bookings() async {
    listReads++;
    if (failure != null) throw failure!;
    return pending == null ? [result] : pending!.future;
  }
}

Future<void> pumpTrips(WidgetTester tester, TripsRepository repository) async {
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        passengerSessionProvider.overrideWithValue((
          passengerId: 1,
          generation: 0,
        )),
        travelRepositoryProvider.overrideWithValue(repository),
      ],
      child: const MaterialApp(home: TicketsScreen()),
    ),
  );
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 50));
}

void main() {
  for (final error in [
    const ApiException('The server took too long to respond.'),
    const ApiException('The booking was not found.', statusCode: 404),
  ]) {
    testWidgets('trip failure shows retry immediately: $error', (tester) async {
      final repository = TripsRepository()..failure = error;
      addTearDown(() => tester.pumpWidget(const SizedBox()));
      await pumpTrips(tester, repository);

      expect(find.text('Could not load trips'), findsOneWidget);
      expect(find.text(error.message), findsOneWidget);
      expect(find.text('Loading your trips…'), findsNothing);

      final failedReads = repository.listReads;
      await tester.pump(const Duration(seconds: 30));
      expect(repository.listReads, failedReads);

      repository.failure = null;
      await tester.tap(find.text('Try again'));
      await tester.pumpAndSettle();
      expect(repository.listReads, failedReads + 1);
      expect(find.text('KT-00000042'), findsOneWidget);
      expect(find.text('Could not load trips'), findsNothing);
    });
  }

  testWidgets('pending trips show loading then display the returned booking', (
    tester,
  ) async {
    final pending = Completer<List<BookingSummary>>();
    final repository = TripsRepository()..pending = pending;
    addTearDown(() => tester.pumpWidget(const SizedBox()));
    await pumpTrips(tester, repository);
    expect(find.text('Loading your trips…'), findsOneWidget);

    pending.complete([booking()]);
    await tester.pumpAndSettle();
    expect(find.text('Loading your trips…'), findsNothing);
    expect(find.text('KT-00000042'), findsOneWidget);
  });
}
