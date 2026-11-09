import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/app/app.dart';
import 'package:kt_mobile_app/app/router.dart';
import 'package:kt_mobile_app/core/providers.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/notifications/notification_push.dart';
import 'package:kt_mobile_app/features/profile/profile_screen.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/schedule_result_card.dart';

import 'notifications_test.dart' show TestPush;
import 'session_recovery_test.dart' show SessionApi;

const _config = MobileAppConfig(
  latestVersion: '1.0.5',
  minimumSupportedVersion: '1.0.0',
  forceUpdate: false,
  maintenanceMode: false,
  features: AppFeatureFlags(),
);

class _ReadyStartup extends StartupController {
  @override
  Future<StartupDecision> build() async =>
      StartupDecision(StartupDestination.home, _config);
}

class _Api extends SessionApi {
  @override
  Stream<void> get unauthorized => const Stream.empty();
}

class _Travel extends Fake implements TravelRepository {
  List<TravelSchedule> results = [];
  int reads = 0;

  @override
  Future<List<TravelSchedule>> schedules(SearchQuery query) async {
    reads++;
    return [...results];
  }
}

PassengerAccount _passenger(int id) => PassengerAccount(
  id: id,
  fullName: 'Private account $id',
  mobile: '0300000000$id',
  cnic: '000000000000$id',
  mobileVerified: true,
);

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
    // Match the real-font setup used by the existing Home layout tests.
    final fonts = Directory(
      Platform.environment['FLUTTER_ROOT']!,
    ).uri.resolve('bin/cache/artifacts/material_fonts/');
    final loader = FontLoader('Roboto');
    for (final weight in ['Regular', 'Bold', 'Black']) {
      loader.addFont(
        File.fromUri(
          fonts.resolve('Roboto-$weight.ttf'),
        ).readAsBytes().then(ByteData.sublistView),
      );
    }
    await loader.load();
  });
  testWidgets('resume and foreground refresh remove expired server results', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(430, 1150);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    const origin = CityOption(id: 1, name: 'Rawalpindi');
    const destination = CityOption(id: 3, name: 'Faisalabad');
    final query = SearchQuery(
      origin: origin,
      destination: destination,
      date: DateTime(2099),
    );
    final schedule = TravelSchedule(
      id: 1,
      scheduleDetailId: 1,
      origin: origin,
      destination: destination,
      departureAt: DateTime(2099, 1, 1, 12),
      busClassName: 'Executive',
      availableSeats: 3,
      totalSeats: 40,
      fares: const [
        FareOption(
          classId: 1,
          className: 'Executive',
          amount: 2000,
          originalAmount: 2000,
        ),
      ],
    );
    final travel = _Travel()..results = [schedule];
    final push = TestPush()..configured = false;
    final container = ProviderContainer(
      overrides: [
        apiClientProvider.overrideWithValue(_Api()),
        pushClientProvider.overrideWithValue(push),
        startupControllerProvider.overrideWith(_ReadyStartup.new),
        travelRepositoryProvider.overrideWithValue(travel),
        fleetGalleryProvider.overrideWith((ref) async => []),
      ],
    );
    addTearDown(container.dispose);
    addTearDown(push.stream.close);
    container
        .read(authControllerProvider.notifier)
        .replacePassenger(_passenger(1));
    appAccessDecision.value = null;
    appRouter.go('/search/results', extra: query);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const KainatTravelsApp(),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(ScheduleResultCard), findsOneWidget);
    expect(travel.reads, 1);

    travel.results = [];
    await tester.pump(const Duration(minutes: 1));
    await tester.pumpAndSettle();
    expect(find.byType(ScheduleResultCard), findsNothing);
    expect(find.text('No buses found'), findsOneWidget);
    expect(travel.reads, 2);

    // The server publishes another future bus while the app is backgrounded.
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
    travel.results = [schedule];
    await tester.pump(const Duration(seconds: 1));
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.byType(ScheduleResultCard), findsOneWidget);
    expect(travel.reads, 3);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    appRouter.go('/');
  });

  testWidgets('expiry and account switching remove private routes and forms', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(430, 1150);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final push = TestPush()..configured = false;
    final container = ProviderContainer(
      overrides: [
        apiClientProvider.overrideWithValue(_Api()),
        pushClientProvider.overrideWithValue(push),
        startupControllerProvider.overrideWith(_ReadyStartup.new),
        fleetGalleryProvider.overrideWith((ref) async => []),
        bookingsProvider.overrideWith((ref, session) async => []),
      ],
    );
    addTearDown(container.dispose);
    addTearDown(push.stream.close);
    final auth = container.read(authControllerProvider.notifier);
    auth.replacePassenger(_passenger(1));
    appAccessDecision.value = null;
    appRouter.go('/profile/edit');
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const KainatTravelsApp(),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.byType(EditProfileScreen), findsOneWidget);
    expect(find.text('Private account 1'), findsOneWidget);

    auth.expireSession();
    await tester.pumpAndSettle();
    expect(appRouter.routeInformationProvider.value.uri.path, '/login');
    expect(find.byType(EditProfileScreen), findsNothing);
    expect(find.text('Private account 1'), findsNothing);
    expect(appRouter.canPop(), isFalse);

    auth.replacePassenger(_passenger(2));
    appRouter.go('/profile/edit');
    await tester.pumpAndSettle();
    expect(find.text('Private account 2'), findsOneWidget);
    auth.replacePassenger(_passenger(3));
    await tester.pumpAndSettle();
    expect(appRouter.routeInformationProvider.value.uri.path, '/home');
    expect(find.byType(EditProfileScreen), findsNothing);
    expect(find.text('Private account 2'), findsNothing);
    expect(appRouter.canPop(), isFalse);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    appRouter.go('/');
  });
}
