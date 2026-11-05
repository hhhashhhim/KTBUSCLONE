import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';
import 'package:kt_mobile_app/features/travel/presentation/passenger_details_widgets.dart';

class _Repository extends Fake implements TravelRepository {
  int savedWrites = 0;

  @override
  Future<SavedPassenger> createSavedPassenger({
    required String fullName,
    required String cnic,
    required String mobile,
    required String gender,
  }) async {
    savedWrites++;
    return SavedPassenger(
      id: 1,
      fullName: fullName,
      cnic: cnic,
      mobile: mobile,
      gender: gender,
    );
  }

  @override
  Future<List<SavedPassenger>> savedPassengers() async => const [
    SavedPassenger(
      id: 2,
      fullName: 'Saved traveller',
      cnic: '0000000000000',
      mobile: '03000000000',
      gender: 'other',
    ),
  ];
}

void main() {
  late ProviderContainer container;
  late _Repository repository;
  late GoRouter router;

  Future<void> mount(WidgetTester tester, String gender) async {
    tester.view.physicalSize = const Size(430, 1200);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    repository = _Repository();
    container = ProviderContainer(
      overrides: [travelRepositoryProvider.overrideWithValue(repository)],
    );
    addTearDown(container.dispose);
    container
        .read(authControllerProvider.notifier)
        .replacePassenger(
          PassengerAccount(
            id: 1,
            fullName: 'Test traveller',
            mobile: '03000000000',
            cnic: '0000000000000',
            gender: gender,
            mobileVerified: true,
          ),
        );
    const origin = CityOption(id: 1, name: 'Lahore');
    const destination = CityOption(id: 2, name: 'Islamabad');
    final date = DateTime(2099);
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
    router = GoRouter(
      routes: [
        GoRoute(path: '/', builder: (_, _) => const PassengerDetailsScreen()),
        GoRoute(
          path: '/search/checkout',
          builder: (_, _) => const Scaffold(body: Text('Checkout reached')),
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
  }

  for (final gender in ['other', 'unrecognized']) {
    testWidgets(
      'profile gender $gender is preserved and blocked before checkout',
      (tester) async {
        await mount(tester, gender);
        expect(find.byType(UnsupportedBookingGenderNotice), findsOneWidget);
        expect(find.text('Call for booking assistance'), findsOneWidget);
        await tester.tap(find.text('Continue to payment'));
        await tester.pumpAndSettle();
        expect(router.routeInformationProvider.value.uri.path, '/');
        expect(container.read(bookingFlowProvider).passengers, isEmpty);
        expect(
          container.read(authControllerProvider).passenger!.gender,
          gender,
        );
        expect(repository.savedWrites, 0);
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets('choosing Other blocks passenger saving and checkout', (
    tester,
  ) async {
    await mount(tester, 'male');
    await tester.ensureVisible(find.text('Other'));
    await tester.tap(find.text('Other'));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byType(CheckboxListTile));
    await tester.tap(find.byType(CheckboxListTile));
    await tester.tap(find.text('Continue to payment'));
    await tester.pumpAndSettle();
    expect(repository.savedWrites, 0);
    expect(router.routeInformationProvider.value.uri.path, '/');
    expect(container.read(bookingFlowProvider).passengers, isEmpty);
  });

  testWidgets(
    'saved Other passenger gets support instead of a substituted gender',
    (tester) async {
      await mount(tester, 'male');
      await tester.tap(find.text('Use saved'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Saved traveller'));
      await tester.pumpAndSettle();
      expect(find.byType(UnsupportedBookingGenderNotice), findsOneWidget);
      await tester.tap(find.text('Continue to payment'));
      await tester.pumpAndSettle();
      expect(router.routeInformationProvider.value.uri.path, '/');
      expect(repository.savedWrites, 0);
    },
  );

  for (final gender in ['male', 'female']) {
    testWidgets(
      '$gender can continue after correcting an unsupported selection',
      (tester) async {
        await mount(tester, 'other');
        final label = gender == 'male' ? 'Male' : 'Female';
        await tester.ensureVisible(find.text(label));
        await tester.tap(find.text(label));
        await tester.pumpAndSettle();
        expect(find.byType(UnsupportedBookingGenderNotice), findsNothing);
        await tester.tap(find.text('Continue to payment'));
        await tester.pumpAndSettle();
        expect(find.text('Checkout reached'), findsOneWidget);
        expect(
          container.read(bookingFlowProvider).passengers.single.gender,
          gender,
        );
        expect(repository.savedWrites, 0);
        // Selecting a booking value never overwrites the account profile.
        expect(
          container.read(authControllerProvider).passenger!.gender,
          'other',
        );
      },
    );
  }
}
