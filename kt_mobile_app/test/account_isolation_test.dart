// Account isolation regressions from the 26 October 2026 QA review.
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/data/auth_repository.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/profile/saved_passengers_screen.dart';
import 'package:kt_mobile_app/features/profile/profile_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';

import 'session_recovery_test.dart' show SessionApi, SessionTokens;

class _AccountTravelRepository implements TravelRepository {
  int account = 1;
  int savedReads = 0;
  int walletReads = 0;
  int bookingReads = 0;
  Completer<List<SavedPassenger>>? savedRequest;
  Completer<LoyaltyWallet>? walletRequest;
  Completer<List<BookingSummary>>? bookingRequest;
  Completer<FareQuote>? quoteRequest;

  @override
  Future<List<SavedPassenger>> savedPassengers() async {
    savedReads++;
    if (savedRequest != null) return savedRequest!.future;
    return [
      SavedPassenger(
        id: account,
        fullName: 'Account $account private passenger',
        cnic: '000000000000$account',
        mobile: '0300000000$account',
        gender: 'male',
      ),
    ];
  }

  @override
  Future<LoyaltyWallet> wallet() async {
    walletReads++;
    return walletRequest == null
        ? LoyaltyWallet(active: true, pointsAvailable: account * 100)
        : walletRequest!.future;
  }

  @override
  Future<List<BookingSummary>> bookings() async {
    bookingReads++;
    if (bookingRequest != null) return bookingRequest!.future;
    return [
      BookingSummary(
        id: account,
        reference: 'ACCOUNT-$account-PRIVATE-TRIP',
        status: 'confirmed',
        paymentStatus: 'paid',
        originName: 'Origin',
        destinationName: 'Destination',
        departureAt: DateTime(2099),
        seats: const ['1'],
        total: 1000,
      ),
    ];
  }

  @override
  Future<FareQuote> quote({
    required SearchQuery query,
    required TravelSchedule schedule,
    required List<TravelSeat> seats,
    required List<PassengerDetails> passengers,
    int pointsToUse = 0,
  }) => quoteRequest!.future;

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class _DeferredAuthRepository extends Fake implements AuthRepository {
  final restored = Completer<PassengerAccount?>();
  final profile = Completer<PassengerAccount>();

  @override
  Future<PassengerAccount?> restore() => restored.future;

  @override
  Future<void> logout() async {}

  @override
  Future<PassengerAccount> updateProfile(Map<String, dynamic> input) =>
      profile.future;
}

PassengerAccount _passenger(int id) => PassengerAccount(
  id: id,
  fullName: 'QA account $id',
  mobile: '0300000000$id',
  mobileVerified: true,
  cnic: '000000000000$id',
);

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late ProviderContainer container;
  late _AccountTravelRepository repository;
  late AuthController auth;

  setUp(() {
    repository = _AccountTravelRepository();
    container = ProviderContainer(
      overrides: [
        travelRepositoryProvider.overrideWithValue(repository),
        authRepositoryProvider.overrideWithValue(
          AuthRepository(SessionApi(), SessionTokens()),
        ),
      ],
    );
    auth = container.read(authControllerProvider.notifier);
    auth.replacePassenger(_passenger(1));
  });
  tearDown(() => container.dispose());

  Future<void> switchAccount() async {
    await auth.logout();
    repository.account = 2;
    auth.replacePassenger(_passenger(2));
  }

  testWidgets('account B must never see account A saved passenger details', (
    tester,
  ) async {
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: SavedPassengersScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Account 1 private passenger'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
    await switchAccount();
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const MaterialApp(home: SavedPassengersScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      find.text('Account 1 private passenger'),
      findsNothing,
      reason:
          'Logout and another login must remove cached personal information.',
    );
    expect(find.text('Account 2 private passenger'), findsOneWidget);
  });

  test(
    'account B receives its own wallet balance after account switching',
    () async {
      expect(
        (await container.read(
          loyaltyWalletProvider(
            container.read(passengerSessionProvider),
          ).future,
        )).pointsAvailable,
        100,
      );
      await switchAccount();
      expect(
        (await container.read(
          loyaltyWalletProvider(
            container.read(passengerSessionProvider),
          ).future,
        )).pointsAvailable,
        200,
      );
    },
  );

  test(
    'account B receives its own upcoming bookings after account switching',
    () async {
      expect(
        (await container.read(
          bookingsProvider(container.read(passengerSessionProvider)).future,
        )).single.id,
        1,
      );
      await switchAccount();
      expect(
        (await container.read(
          bookingsProvider(container.read(passengerSessionProvider)).future,
        )).single.id,
        2,
      );
    },
  );

  test(
    'logout clears passenger details retained in the booking draft',
    () async {
      container.read(bookingFlowProvider.notifier).setPassengers(const [
        PassengerDetails(
          seatNumber: '1',
          fullName: 'Account 1 private passenger',
          cnic: '0000000000001',
          mobile: '03000000001',
          gender: 'male',
        ),
      ]);
      await switchAccount();
      expect(container.read(bookingFlowProvider).passengers, isEmpty);
    },
  );

  test(
    'signed-out private providers return empty data without API calls',
    () async {
      await auth.logout();
      final session = container.read(passengerSessionProvider);
      expect(
        await container.read(savedPassengersProvider(session).future),
        isEmpty,
      );
      expect(await container.read(bookingsProvider(session).future), isEmpty);
      expect(
        (await container.read(loyaltyWalletProvider(session).future)).active,
        isFalse,
      );
      expect(
        repository.savedReads +
            repository.walletReads +
            repository.bookingReads,
        0,
      );
    },
  );

  for (final endSession in ['logout', 'expiry', 'biometric lock']) {
    test('$endSession clears all private data and the draft', () async {
      final old = container.read(passengerSessionProvider);
      final subscriptions = [
        container.listen(savedPassengersProvider(old), (_, _) {}),
        container.listen(bookingsProvider(old), (_, _) {}),
        container.listen(loyaltyWalletProvider(old), (_, _) {}),
      ];
      addTearDown(() {
        for (final subscription in subscriptions) {
          subscription.close();
        }
      });
      await container.read(savedPassengersProvider(old).future);
      await container.read(bookingsProvider(old).future);
      await container.read(loyaltyWalletProvider(old).future);
      container.read(bookingFlowProvider.notifier).setPassengers(const [
        PassengerDetails(
          seatNumber: '1',
          fullName: 'Private',
          cnic: '0000000000001',
          mobile: '03000000001',
          gender: 'male',
        ),
      ]);
      switch (endSession) {
        case 'logout':
          await auth.logout();
        case 'expiry':
          auth.expireSession();
        case 'biometric lock':
          auth.lockForQuickLogin();
      }
      final current = container.read(passengerSessionProvider);
      expect(current.passengerId, isNull);
      expect(
        await container.read(savedPassengersProvider(current).future),
        isEmpty,
      );
      expect(await container.read(bookingsProvider(current).future), isEmpty);
      expect(
        (await container.read(
          loyaltyWalletProvider(current).future,
        )).pointsAvailable,
        0,
      );
      expect(container.read(bookingFlowProvider).passengers, isEmpty);
      // Even a retained listener to the old provider becomes inactive.
      expect(
        await container.read(savedPassengersProvider(old).future),
        isEmpty,
      );
    });
  }

  testWidgets('old passenger details stay hidden while B loads and fails', (
    tester,
  ) async {
    Widget screen() => UncontrolledProviderScope(
      container: container,
      child: const MaterialApp(home: SavedPassengersScreen()),
    );
    await tester.pumpWidget(screen());
    await tester.pumpAndSettle();
    expect(find.text('Account 1 private passenger'), findsOneWidget);
    repository.savedRequest = Completer<List<SavedPassenger>>();
    repository.account = 2;
    auth.replacePassenger(_passenger(2));
    await tester.pump();
    expect(find.text('Account 1 private passenger'), findsNothing);
    final current = savedPassengersProvider(
      container.read(passengerSessionProvider),
    );
    expect(container.read(current).hasValue, isFalse);
    repository.savedRequest!.completeError(StateError('Connection lost'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 50));
    expect(find.text('Account 1 private passenger'), findsNothing);
    expect(container.read(current).hasValue, isFalse);
    await tester.pumpWidget(const SizedBox());
  });

  for (final cache in ['passengers', 'wallet', 'bookings']) {
    for (final failOld in [false, true]) {
      test(
        'late $cache ${failOld ? 'error' : 'response'} cannot replace B data',
        () async {
          final old = container.read(passengerSessionProvider);
          FutureProvider<dynamic> provider(PassengerSession session) =>
              switch (cache) {
                'passengers' => savedPassengersProvider(session),
                'wallet' => loyaltyWalletProvider(session),
                _ => bookingsProvider(session),
              };
          // Complete each transport only after switching accounts and loading B.
          switch (cache) {
            case 'passengers':
              repository.savedRequest = Completer<List<SavedPassenger>>();
            case 'wallet':
              repository.walletRequest = Completer<LoyaltyWallet>();
            case 'bookings':
              repository.bookingRequest = Completer<List<BookingSummary>>();
          }
          final oldSaved = repository.savedRequest;
          final oldWallet = repository.walletRequest;
          final oldBookings = repository.bookingRequest;
          final oldSub = container.listen(provider(old), (_, _) {});
          addTearDown(oldSub.close);
          await Future<void>.delayed(Duration.zero);
          repository.savedRequest = null;
          repository.walletRequest = null;
          repository.bookingRequest = null;
          await switchAccount();
          final current = provider(container.read(passengerSessionProvider));
          final currentSub = container.listen(current, (_, _) {});
          addTearDown(currentSub.close);
          final value = await container.read(current.future);
          if (failOld) {
            (oldSaved ?? oldWallet ?? oldBookings)!.completeError(
              StateError('Old request failed'),
            );
          } else {
            oldSaved?.complete(const []);
            oldWallet?.complete(
              const LoyaltyWallet(active: true, pointsAvailable: 999),
            );
            oldBookings?.complete(const []);
          }
          await container.pump();
          expect(container.read(current).hasError, isFalse);
          expect(container.read(current).value, same(value));
        },
      );
    }
  }

  test(
    'signing back into the same account gets a fresh session and balance',
    () async {
      final old = container.read(passengerSessionProvider);
      final sub = container.listen(loyaltyWalletProvider(old), (_, _) {});
      addTearDown(sub.close);
      expect(
        (await container.read(
          loyaltyWalletProvider(old).future,
        )).pointsAvailable,
        100,
      );
      auth.expireSession();
      auth.replacePassenger(_passenger(1));
      final current = container.read(passengerSessionProvider);
      expect(current, isNot(old));
      await container.read(loyaltyWalletProvider(current).future);
      expect(repository.walletReads, 2);
    },
  );

  test(
    'editing the current profile preserves the session and booking draft',
    () async {
      final before = container.read(passengerSessionProvider);
      final sub = container.listen(savedPassengersProvider(before), (_, _) {});
      addTearDown(sub.close);
      await container.read(savedPassengersProvider(before).future);
      container.read(bookingFlowProvider.notifier).setPassengers(const [
        PassengerDetails(
          seatNumber: '1',
          fullName: 'Draft',
          cnic: '0000000000001',
          mobile: '03000000001',
          gender: 'male',
        ),
      ]);
      auth.replacePassenger(_passenger(1));
      expect(container.read(passengerSessionProvider), before);
      expect(
        container.read(bookingFlowProvider).passengers.single.fullName,
        'Draft',
      );
      await container.read(savedPassengersProvider(before).future);
      expect(repository.savedReads, 1);
    },
  );

  for (final endSession in [
    'logout',
    'expiry',
    'biometric lock',
    'account switch',
  ]) {
    test('late session restoration cannot undo $endSession', () async {
      final repository = _DeferredAuthRepository();
      final isolated = ProviderContainer(
        overrides: [authRepositoryProvider.overrideWithValue(repository)],
      );
      addTearDown(isolated.dispose);
      final auth = isolated.read(authControllerProvider.notifier);
      final restore = auth.restoreSession();
      switch (endSession) {
        case 'logout':
          await auth.logout();
        case 'expiry':
          auth.expireSession();
        case 'biometric lock':
          auth.lockForQuickLogin();
        case 'account switch':
          auth.replacePassenger(_passenger(2));
      }
      repository.restored.complete(_passenger(1));
      expect(await restore, isFalse);
      expect(
        isolated.read(authControllerProvider).passenger?.id,
        endSession == 'account switch' ? 2 : null,
      );
    });
  }

  testWidgets('a delayed profile save cannot restore the old passenger', (
    tester,
  ) async {
    final repository = _DeferredAuthRepository();
    final isolated = ProviderContainer(
      overrides: [authRepositoryProvider.overrideWithValue(repository)],
    );
    addTearDown(isolated.dispose);
    final auth = isolated.read(authControllerProvider.notifier);
    auth.replacePassenger(_passenger(1));
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: isolated,
        child: const MaterialApp(home: EditProfileScreen()),
      ),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Save changes'));
    await tester.tap(find.text('Save changes'));
    await tester.pump();
    auth.replacePassenger(_passenger(2));
    repository.profile.complete(_passenger(1));
    await tester.pumpAndSettle();
    expect(isolated.read(authControllerProvider).passenger!.id, 2);
    expect(find.text('Profile updated.'), findsNothing);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets(
    'a delayed fare quote cannot repopulate a different session draft',
    (tester) async {
      repository.quoteRequest = Completer<FareQuote>();
      const origin = CityOption(id: 1, name: 'Origin');
      const destination = CityOption(id: 2, name: 'Destination');
      final date = DateTime(2099, 1, 1, 12);
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
          price: 1000,
          classId: 1,
        ),
        1,
      );
      flow.setPassengers(const [
        PassengerDetails(
          seatNumber: '1',
          fullName: 'Private A',
          cnic: '0000000000001',
          mobile: '03000000001',
          gender: 'male',
        ),
      ]);
      await tester.pumpWidget(
        UncontrolledProviderScope(
          container: container,
          child: const MaterialApp(home: CheckoutScreen()),
        ),
      );
      await tester.pump();
      await switchAccount();
      repository.quoteRequest!.complete(
        FareQuote.fromJson({
          'quote_token': 'account-a-private-quote',
          'base_fare': 1000,
          'total': 1000,
          'payment_methods': ['counter'],
        }),
      );
      await tester.pumpAndSettle();
      expect(container.read(bookingFlowProvider).quote, isNull);
      expect(container.read(bookingFlowProvider).passengers, isEmpty);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
    },
  );
}
