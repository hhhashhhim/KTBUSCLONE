import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/tickets/tickets_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';

class _BookingApi extends Fake implements ApiClient {
  late Future<dynamic> Function(int page, CancelToken? cancelToken) respond;
  final pages = <int>[];

  @override
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
  }) {
    expect(path, '/bookings');
    expect(queryParameters?['per_page'], 50);
    final page = queryParameters!['page'] as int;
    pages.add(page);
    return respond(page, cancelToken);
  }
}

Map<String, dynamic> _booking(int id, {bool upcoming = false}) => {
  'id': id,
  'reference': 'KT-TRIP-$id',
  'status': 'confirmed',
  'payment_status': 'paid',
  'origin_name': 'Lahore',
  'destination_name': 'Islamabad',
  'departure_at': upcoming
      ? '2099-09-12T12:00:00+05:00'
      : '2020-09-12T12:00:00+05:00',
  'seats': ['1'],
  'total': 2500,
};

Map<String, dynamic> _page(
  int page,
  int total, {
  List<Map<String, dynamic>>? items,
}) => {
  'items':
      items ??
      [
        for (
          var offset = (page - 1) * 50;
          offset < total && offset < page * 50;
          offset++
        )
          _booking(total - offset, upcoming: offset >= 50),
      ],
  'pagination': {
    'current_page': page,
    'per_page': 50,
    'total': total,
    'last_page': total == 0 ? 1 : (total / 50).ceil(),
  },
};

PassengerAccount _passenger(int id) => PassengerAccount(
  id: id,
  fullName: 'Test passenger $id',
  mobile: '0300000000$id',
  mobileVerified: true,
);

void main() {
  test('loads every page of more than 20 bookings in server order', () async {
    final api = _BookingApi()..respond = (page, _) async => _page(page, 105);
    final bookings = await TravelRepository(api).bookings();

    expect(api.pages, [1, 2, 3]);
    expect(
      bookings.map((booking) => booking.id),
      List.generate(105, (index) => 105 - index),
    );
    expect(
      bookings.where((booking) => booking.departureAt.year == 2099),
      hasLength(55),
    );
  });

  test('empty history completes without fetching a second page', () async {
    final api = _BookingApi()..respond = (page, _) async => _page(page, 0);
    expect(await TravelRepository(api).bookings(), isEmpty);
    expect(api.pages, [1]);
  });

  test(
    'accepts the complete unpaginated response from older servers',
    () async {
      final api = _BookingApi()..respond = (_, _) async => [_booking(1)];
      expect((await TravelRepository(api).bookings()).single.id, 1);
      expect(api.pages, [1]);
    },
  );

  test(
    'a failed later page fails the whole load and retry starts at page one',
    () async {
      var fail = true;
      final api = _BookingApi()
        ..respond = (page, _) async {
          if (page == 2 && fail) {
            throw const ApiException('Unable to connect. Please try again.');
          }
          return _page(page, 51);
        };
      final repository = TravelRepository(api);

      await expectLater(repository.bookings(), throwsA(isA<ApiException>()));
      fail = false;
      expect(await repository.bookings(), hasLength(51));
      expect(api.pages, [1, 2, 1, 2]);
    },
  );

  for (final scenario in {
    'changed total': _page(2, 52),
    'duplicate booking': _page(2, 51, items: [_booking(51)]),
    'repeated first page': _page(1, 51),
    'missing booking': _page(2, 51, items: []),
  }.entries) {
    test('${scenario.key} never returns an incomplete trip history', () async {
      final api = _BookingApi()
        ..respond = (page, _) async =>
            page == 1 ? _page(1, 51) : scenario.value;
      await expectLater(
        TravelRepository(api).bookings(),
        throwsA(isA<ApiException>()),
      );
      expect(api.pages, [1, 2]);
    });
  }

  testWidgets('My Trips finds an upcoming trip beyond the first page', (
    tester,
  ) async {
    final lastPage = Completer<dynamic>();
    final api = _BookingApi()
      ..respond = (page, _) async {
        if (page == 1) return _page(1, 51);
        return await lastPage.future;
      };
    addTearDown(() => tester.pumpWidget(const SizedBox()));
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          passengerSessionProvider.overrideWithValue((
            passengerId: 1,
            generation: 0,
          )),
          travelRepositoryProvider.overrideWithValue(TravelRepository(api)),
        ],
        child: const MaterialApp(home: TicketsScreen()),
      ),
    );
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 50));
    expect(
      find.text('Loading your trips…'),
      findsOneWidget,
      reason: tester
          .widgetList<Text>(find.byType(Text))
          .map((text) => text.data)
          .join(' | '),
    );
    expect(find.text('No upcoming trips'), findsNothing);
    expect(find.text('KT-TRIP-51'), findsNothing);

    lastPage.complete(_page(2, 51));
    await tester.pumpAndSettle();
    expect(find.text('KT-TRIP-1'), findsOneWidget);
    expect(find.text('No upcoming trips'), findsNothing);
    expect(find.text('Loading your trips…'), findsNothing);

    await tester.tap(find.text('Past'));
    await tester.pumpAndSettle();
    expect(find.text('KT-TRIP-51'), findsOneWidget);
    expect(find.text('KT-TRIP-1'), findsNothing);
  });

  test(
    'account change cancels paging and cannot mix passenger histories',
    () async {
      final oldPage = Completer<dynamic>();
      final oldPageStarted = Completer<void>();
      CancelToken? oldToken;
      var account = 1;
      final requests = <(int, int)>[];
      final api = _BookingApi()
        ..respond = (page, cancelToken) async {
          requests.add((account, page));
          if (account == 2) return _page(1, 1, items: [_booking(200)]);
          if (page == 2) {
            oldToken = cancelToken;
            if (!oldPageStarted.isCompleted) oldPageStarted.complete();
            // Deliberately ignore cancellation here to emulate a late response.
            return oldPage.future;
          }
          return _page(page, 105);
        };
      final container = ProviderContainer(
        overrides: [
          travelRepositoryProvider.overrideWithValue(TravelRepository(api)),
        ],
      );
      addTearDown(container.dispose);
      final auth = container.read(authControllerProvider.notifier);
      auth.replacePassenger(_passenger(1));
      final sessionA = container.read(passengerSessionProvider);
      final oldSubscription = container.listen(
        bookingsProvider(sessionA),
        (_, _) {},
      );
      addTearDown(oldSubscription.close);
      await oldPageStarted.future;

      account = 2;
      auth.replacePassenger(_passenger(2));
      await container.pump();
      expect(oldToken?.isCancelled, isTrue);
      final sessionB = container.read(passengerSessionProvider);
      final currentSubscription = container.listen(
        bookingsProvider(sessionB),
        (_, _) {},
      );
      addTearDown(currentSubscription.close);
      expect(
        (await container.read(
          bookingsProvider(sessionB).future,
        )).map((booking) => booking.id),
        [200],
      );

      oldPage.complete(_page(2, 105));
      await container.pump();
      expect(requests, [(1, 1), (1, 2), (2, 1)]);
      expect(container.read(bookingsProvider(sessionA)).value, isEmpty);
      expect(container.read(bookingsProvider(sessionB)).value!.single.id, 200);
    },
  );
}
