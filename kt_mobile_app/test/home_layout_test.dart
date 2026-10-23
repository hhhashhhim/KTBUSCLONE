import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/features/home/fleet_photos.dart';
import 'package:kt_mobile_app/features/home/home_screen.dart';
import 'package:kt_mobile_app/features/shell/app_shell.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

final _now = DateTime.utc(2026, 10, 21, 14, 30);

BookingSummary _booking({
  int id = 1,
  String status = 'confirmed',
  Duration departureIn = const Duration(hours: 25),
}) => BookingSummary(
  id: id,
  reference: 'KT-$id',
  status: status,
  paymentStatus: 'paid',
  originName: 'Karachi',
  destinationName: 'Rawalpindi',
  departureAt: _now.add(departureIn),
  seats: const ['34'],
  total: 5000,
);

Future<ProviderContainer> _showHome(
  WidgetTester tester, {
  Size size = const Size(430, 1150),
  List<BookingSummary> bookings = const [],
  Stream<DateTime>? clock,
  double textScale = 1,
  bool banner = true,
  List<FleetPhoto>? bannerPhotos,
}) async {
  tester.view.devicePixelRatio = 1;
  tester.view.physicalSize = size;
  addTearDown(tester.view.resetDevicePixelRatio);
  addTearDown(tester.view.resetPhysicalSize);
  final container = ProviderContainer(
    overrides: [
      homeClockProvider.overrideWith((ref) => clock ?? Stream.value(_now)),
      bookingsProvider.overrideWith((ref) async => bookings),
      fleetGalleryProvider.overrideWith((ref) async => const []),
      appPhotosProvider('home_banner').overrideWith(
        (ref) => banner
            ? List<FleetPhoto>.of(bannerPhotos ?? [bundledFleetPhotos.first])
            : [],
      ),
      mobileAppConfigProvider.overrideWithValue(
        const MobileAppConfig(
          latestVersion: '1.0.4',
          minimumSupportedVersion: '1.0.0',
          forceUpdate: false,
          maintenanceMode: false,
          features: AppFeatureFlags(notifications: true),
        ),
      ),
    ],
  );
  addTearDown(container.dispose);
  final router = GoRouter(
    initialLocation: '/home',
    routes: [
      GoRoute(
        path: '/home',
        builder: (_, _) => const AppShell(child: HomeScreen()),
      ),
      GoRoute(
        path: '/tickets',
        builder: (_, _) => const Scaffold(body: Text('All trips')),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    UncontrolledProviderScope(
      container: container,
      child: MaterialApp.router(
        theme: AppTheme.light,
        routerConfig: router,
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(context).copyWith(
            padding: const EdgeInsets.only(top: 59, bottom: 34),
            viewPadding: const EdgeInsets.only(top: 59, bottom: 34),
            textScaler: TextScaler.linear(textScale),
          ),
          child: child!,
        ),
      ),
    ),
  );
  await tester.pumpAndSettle();
  addTearDown(() => tester.pumpWidget(const SizedBox()));
  return container;
}

Finder get _banner => find.byKey(const ValueKey('app-media-home_banner'));

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
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

  testWidgets(
    'trip yields space before banner and both return after resizing',
    (tester) async {
      await _showHome(
        tester,
        size: const Size(430, 1400),
        bookings: [_booking()],
      );
      expect(find.text('NEXT TRIP'), findsOneWidget);
      expect(_banner, findsOneWidget);

      tester.view.physicalSize = const Size(430, 1150);
      await tester.pumpAndSettle();
      expect(find.text('NEXT TRIP'), findsNothing);
      expect(_banner, findsOneWidget);

      tester.view.physicalSize = const Size(390, 844);
      await tester.pumpAndSettle();
      expect(find.text('NEXT TRIP'), findsNothing);
      expect(_banner, findsNothing);
      expect(find.text('Where to today?').hitTestable(), findsOneWidget);
      expect(find.text('Bus & seat guide').hitTestable(), findsOneWidget);
      expect(find.text('Executive bus').hitTestable(), findsOneWidget);
      expect(tester.takeException(), isNull);

      tester.view.physicalSize = const Size(430, 1400);
      await tester.pumpAndSettle();
      expect(find.text('NEXT TRIP'), findsOneWidget);
      expect(_banner, findsOneWidget);
    },
  );

  for (final departureIn in [
    const Duration(hours: 1),
    const Duration(hours: 24),
  ]) {
    testWidgets('confirmed trip in $departureIn stays on a small phone', (
      tester,
    ) async {
      await _showHome(
        tester,
        size: const Size(390, 844),
        bookings: [_booking(departureIn: departureIn)],
      );
      expect(find.text('NEXT TRIP'), findsOneWidget);
      expect(find.text('KT-1').hitTestable(), findsOneWidget);
      expect(_banner, findsNothing);
      // Essential content stays reachable when the protected trip needs scroll.
      await tester.ensureVisible(find.text('Executive bus'));
      expect(find.text('Executive bus').hitTestable(), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('trip more than 24 hours away yields to the banner', (
    tester,
  ) async {
    await _showHome(
      tester,
      bookings: [_booking(departureIn: const Duration(hours: 24, seconds: 1))],
    );
    expect(find.text('NEXT TRIP'), findsNothing);
    expect(_banner, findsOneWidget);
  });

  testWidgets('media arriving after load recalculates which sections fit', (
    tester,
  ) async {
    final photos = <FleetPhoto>[];
    final container = await _showHome(
      tester,
      bookings: [_booking()],
      bannerPhotos: photos,
    );
    expect(find.text('NEXT TRIP'), findsOneWidget);
    expect(_banner, findsNothing);

    photos.add(bundledFleetPhotos.first);
    container.invalidate(appPhotosProvider('home_banner'));
    await tester.pumpAndSettle();
    expect(find.text('NEXT TRIP'), findsNothing);
    expect(_banner, findsOneWidget);

    photos.clear();
    container.invalidate(appPhotosProvider('home_banner'));
    await tester.pumpAndSettle();
    expect(find.text('NEXT TRIP'), findsOneWidget);
    expect(_banner, findsNothing);
  });

  testWidgets('nearest confirmed future trip wins regardless of API order', (
    tester,
  ) async {
    await _showHome(
      tester,
      bookings: [
        _booking(
          id: 1,
          status: 'canceled',
          departureIn: const Duration(hours: 1),
        ),
        _booking(id: 2),
        _booking(id: 3, departureIn: const Duration(hours: 3)),
        _booking(
          id: 4,
          status: 'pending',
          departureIn: const Duration(hours: 2),
        ),
      ],
    );
    expect(find.text('KT-3'), findsOneWidget);
    for (final id in [1, 2, 4]) {
      expect(find.text('KT-$id', skipOffstage: false), findsNothing);
    }
  });

  testWidgets('canceled, pending and departed bookings have no trip card', (
    tester,
  ) async {
    await _showHome(
      tester,
      size: const Size(430, 1400),
      bookings: [
        _booking(id: 1, status: 'canceled'),
        _booking(id: 2, status: 'pending'),
        _booking(id: 3, departureIn: Duration.zero),
        _booking(id: 4, departureIn: const Duration(minutes: -1)),
      ],
    );
    expect(find.text('NEXT TRIP', skipOffstage: false), findsNothing);
  });

  testWidgets('empty bookings and missing banner leave no empty sections', (
    tester,
  ) async {
    await _showHome(tester, banner: false, size: const Size(390, 844));
    expect(find.text('NEXT TRIP', skipOffstage: false), findsNothing);
    expect(_banner, findsNothing);
    expect(find.text('Executive bus').hitTestable(), findsOneWidget);
  });

  testWidgets('24-hour protection updates while Home remains open', (
    tester,
  ) async {
    final clock = StreamController<DateTime>();
    addTearDown(clock.close);
    final shown = _showHome(
      tester,
      bookings: [_booking()],
      clock: clock.stream,
    );
    clock.add(_now);
    await shown;
    expect(find.text('NEXT TRIP'), findsNothing);
    expect(_banner, findsOneWidget);

    clock.add(_now.add(const Duration(hours: 1)));
    await tester.pumpAndSettle();
    expect(find.text('NEXT TRIP'), findsOneWidget);
    expect(_banner, findsNothing);

    clock.add(_now.add(const Duration(hours: 25)));
    await tester.pumpAndSettle();
    expect(find.text('NEXT TRIP', skipOffstage: false), findsNothing);
    expect(_banner, findsOneWidget);
  });

  testWidgets(
    'short landscape remains scrollable and My trips stays available',
    (tester) async {
      await _showHome(
        tester,
        size: const Size(844, 390),
        bookings: [_booking()],
        textScale: 1.2,
      );
      expect(find.text('NEXT TRIP'), findsNothing);
      expect(_banner, findsNothing);
      await tester.ensureVisible(find.text('Bus & seat guide'));
      expect(find.text('Bus & seat guide').hitTestable(), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.tap(find.text('My Trips'));
      await tester.pumpAndSettle();
      expect(find.text('All trips'), findsOneWidget);
    },
  );
}
