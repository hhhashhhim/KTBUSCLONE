import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/app/app.dart';
import 'package:kt_mobile_app/app/router.dart';
import 'package:kt_mobile_app/core/providers.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/notifications/notification_providers.dart';
import 'package:kt_mobile_app/features/notifications/notification_push.dart';
import 'package:kt_mobile_app/features/notifications/notification_repository.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';

import 'notifications_test.dart' show NoticeApi, TestPush, passenger;

const _config = MobileAppConfig(
  latestVersion: '1.0.5',
  minimumSupportedVersion: '1.0.0',
  forceUpdate: false,
  maintenanceMode: false,
  features: AppFeatureFlags(notifications: true, pushNotifications: true),
);

class _Startup extends StartupController {
  _Startup(this.destination);
  final StartupDestination destination;
  @override
  Future<StartupDecision> build() async =>
      StartupDecision(destination, _config);

  void release() => state = const AsyncData(
    StartupDecision(StartupDestination.home, _config),
  );
}

class _Push extends TestPush {
  _Push({this.initialId}) {
    configured = false;
  }
  final int? initialId;
  final taps = StreamController<int>.broadcast();
  final incoming = StreamController<void>.broadcast();
  @override
  Stream<int> get opened => taps.stream;
  @override
  Stream<void> get messages => incoming.stream;
  @override
  Future<int?> initialNotice() async => initialId;
  Future<void> close() async {
    await stream.close();
    await taps.close();
    await incoming.close();
  }
}

class _Api extends NoticeApi {
  @override
  Stream<void> get unauthorized => const Stream.empty();
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
    // Use the app's actual font metrics, as in the session-navigation tests.
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
  Future<ProviderContainer> mount(
    WidgetTester tester,
    _Push push, {
    String path = '/',
    bool signedIn = true,
    StartupDestination destination = StartupDestination.home,
    void Function()? loaded,
  }) async {
    tester.view.physicalSize = const Size(430, 1150);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final container = ProviderContainer(
      overrides: [
        apiClientProvider.overrideWithValue(_Api()),
        pushClientProvider.overrideWithValue(push),
        startupControllerProvider.overrideWith(() => _Startup(destination)),
        fleetGalleryProvider.overrideWith((ref) async => []),
        bookingsProvider.overrideWith((ref, session) async => []),
        notificationPageProvider(1).overrideWith((ref) async {
          loaded?.call();
          return const NotificationPage();
        }),
        notificationDetailProvider(8).overrideWith(
          (ref) async => AppNotification(
            id: 8,
            title: 'Test journey update',
            body: 'Notification opened successfully.',
            createdAt: DateTime(2026, 10, 26),
            readAt: DateTime(2026, 10, 26),
          ),
        ),
      ],
    );
    addTearDown(container.dispose);
    addTearDown(push.close);
    if (signedIn) {
      container
          .read(authControllerProvider.notifier)
          .replacePassenger(passenger(1));
    }
    appAccessDecision.value = null;
    appRouter.go(path);
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: const KainatTravelsApp(),
      ),
    );
    await tester.pumpAndSettle();
    return container;
  }

  Future<void> unmount(WidgetTester tester) async {
    await tester.pumpWidget(const SizedBox());
    appAccessDecision.value = null;
    appRouter.go('/');
  }

  testWidgets('cold-start notification survives the splash redirect', (
    tester,
  ) async {
    await mount(tester, _Push(initialId: 8));
    expect(
      appRouter.routeInformationProvider.value.uri.path,
      '/notifications/8',
    );
    expect(find.text('Test journey update'), findsOneWidget);
    await unmount(tester);
  });

  testWidgets('foreground push refreshes inbox and shows a visible alert', (
    tester,
  ) async {
    final push = _Push();
    var loads = 0;
    await mount(tester, push, path: '/notifications', loaded: () => loads++);
    final before = loads;
    push.incoming.add(null);
    await tester.pumpAndSettle();
    expect(loads, greaterThan(before));
    expect(find.text('You have a new update.'), findsOneWidget);
    expect(find.text('View alerts'), findsOneWidget);
    await unmount(tester);
  });

  testWidgets('notification waits for login and the update gate', (
    tester,
  ) async {
    final push = _Push(initialId: 8);
    final container = await mount(
      tester,
      push,
      signedIn: false,
      destination: StartupDestination.forceUpdate,
    );
    expect(
      appRouter.routeInformationProvider.value.uri.path,
      '/update-required',
    );
    container
        .read(authControllerProvider.notifier)
        .replacePassenger(passenger(1));
    await tester.pumpAndSettle();
    expect(
      appRouter.routeInformationProvider.value.uri.path,
      '/update-required',
    );
    (container.read(startupControllerProvider.notifier) as _Startup).release();
    await tester.pumpAndSettle();
    expect(
      appRouter.routeInformationProvider.value.uri.path,
      '/notifications/8',
    );
    await unmount(tester);
  });

  testWidgets('account switching drops another passenger notification tap', (
    tester,
  ) async {
    final push = _Push(initialId: 8);
    final container = await mount(
      tester,
      push,
      destination: StartupDestination.forceUpdate,
    );
    container
        .read(authControllerProvider.notifier)
        .replacePassenger(passenger(2));
    (container.read(startupControllerProvider.notifier) as _Startup).release();
    await tester.pumpAndSettle();
    expect(appRouter.routeInformationProvider.value.uri.path, '/home');
    expect(find.text('Test journey update'), findsNothing);
    await unmount(tester);
  });

  testWidgets('signed-out sessions do not show foreground alerts', (
    tester,
  ) async {
    final push = _Push();
    await mount(
      tester,
      push,
      signedIn: false,
      destination: StartupDestination.login,
    );
    push.incoming.add(null);
    await tester.pumpAndSettle();
    expect(find.text('You have a new update.'), findsNothing);
    await unmount(tester);
  });
}
