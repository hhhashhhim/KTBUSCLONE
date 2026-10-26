import 'dart:async';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/notifications/notification_repository.dart';
import 'package:kt_mobile_app/features/notifications/notification_providers.dart';
import 'package:kt_mobile_app/features/notifications/notification_push.dart';
import 'package:kt_mobile_app/features/notifications/notifications_screen.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';

class NoticeApi implements ApiClient {
  final calls = <String>[];
  Object? putError;
  Completer<void>? waiting;
  Object? lastData;
  @override
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
  }) async {
    calls.add('GET $path');
    expect(queryParameters?['paginated'], 1);
    return {
      'items': [
        {
          'id': 8,
          'title': 'Confirmed',
          'body': 'Check My Trips',
          'created_at': '2026-10-20T08:00:00+05:00',
          'read_at': null,
          'booking_id': 42,
        },
      ],
      'unread_count': 1,
      'pagination': {'current_page': 1, 'last_page': 2},
    };
  }

  @override
  Future<dynamic> put(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    calls.add('PUT $path');
    lastData = data;
    await waiting?.future;
    if (putError != null) throw putError!;
    return {};
  }

  @override
  Future<dynamic> post(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    calls.add('POST $path');
    lastData = data;
    return {};
  }

  @override
  Future<dynamic> delete(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    calls.add('DELETE $path');
    return {};
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class TestPush implements PushClient {
  final stream = StreamController<String>.broadcast(sync: true);
  bool allowed = false;
  Completer<bool>? permission;
  bool configured = true;
  int registerCalls = 0;
  int unregisterCalls = 0;
  @override
  bool get available => configured;
  @override
  Stream<String> get registrations => stream.stream;
  @override
  Stream<int> get opened => const Stream.empty();
  @override
  Stream<void> get messages => const Stream.empty();
  @override
  Future<bool> permitted() async => allowed;
  @override
  Future<bool> requestPermission() async =>
      permission == null ? allowed : permission!.future;
  @override
  Future<void> register() async {
    registerCalls++;
    stream.add('cTestFirebaseInstallationId');
  }

  @override
  Future<void> unregister() async {
    unregisterCalls++;
  }

  @override
  Future<int?> initialNotice() async => null;
}

const config = MobileAppConfig(
  latestVersion: '1.0.3',
  minimumSupportedVersion: '1.0.0',
  forceUpdate: false,
  maintenanceMode: false,
  features: AppFeatureFlags(notifications: true),
);
PassengerAccount passenger(int id) => PassengerAccount.fromJson({
  'id': id,
  'full_name': 'Passenger $id',
  'mobile': '03001234567',
  'mobile_verified': true,
});

class SignedIn extends AuthController {
  @override
  AuthState build() => AuthState(passenger: passenger(1));
}

void main() {
  test(
    'repository preserves IDs, timestamps, pagination and read watermark',
    () async {
      final api = NoticeApi();
      final repo = NotificationRepository(api);
      final page = await repo.load(1);
      expect(page.unread, 1);
      expect(page.lastPage, 2);
      expect(page.items.single.bookingId, 42);
      expect(
        notificationTime(page.items.single.createdAt),
        '20 Oct, 8:00 AM PKT',
      );
      await repo.readThrough(8);
      expect(api.lastData, {'through_id': 8});
      await repo.read(8);
      expect(api.calls.last, 'POST /notifications/8/read');
    },
  );
  test(
    'push requires authenticated account, server configuration and permission',
    () async {
      final api = NoticeApi();
      final client = TestPush();
      final push = NotificationPush(api, client);
      await push.sync(null, true);
      await push.sync(1, false);
      await push.sync(1, true);
      expect(client.registerCalls, 0);
      await expectLater(push.enable(), throwsStateError);
      expect(api.calls, isEmpty);
      push.dispose();
      await client.stream.close();
    },
  );
  test(
    'opt-in registers current installation and logout unregisters it',
    () async {
      final api = NoticeApi();
      final client = TestPush();
      final push = NotificationPush(api, client);
      await push.sync(1, true);
      client.allowed = true;
      await push.enable();
      expect(api.calls, ['PUT /notifications/device']);
      expect((api.lastData as Map)['token'], 'cTestFirebaseInstallationId');
      await push.detach();
      expect(api.calls.last, 'DELETE /notifications/device');
      expect(client.unregisterCalls, 1);
      client.stream.add('late-registration');
      await Future<void>.delayed(Duration.zero);
      expect(api.calls.length, 2);
      push.dispose();
      await client.stream.close();
    },
  );
  test('failed device registration is not reported as enabled', () async {
    final api = NoticeApi()..putError = StateError('offline');
    final client = TestPush();
    final push = NotificationPush(api, client);
    await push.sync(1, true);
    client.allowed = true;
    await expectLater(push.enable(), throwsStateError);
    push.dispose();
    await client.stream.close();
  });
  test(
    'logout while permission is open does not register or report success',
    () async {
      final api = NoticeApi();
      final client = TestPush()..permission = Completer<bool>();
      final push = NotificationPush(api, client);
      await push.sync(1, true);
      final enabling = push.enable();
      final expected = expectLater(enabling, throwsStateError);
      await push.detach();
      client.permission!.complete(true);
      await expected;
      expect(client.registerCalls, 0);
      expect(api.calls, isEmpty);
      push.dispose();
      await client.stream.close();
    },
  );
  test(
    'logout waits for an in-flight device registration then removes it',
    () async {
      final api = NoticeApi()..waiting = Completer<void>();
      final client = TestPush()..allowed = true;
      final push = NotificationPush(api, client);
      await push.sync(1, true);
      await Future<void>.delayed(Duration.zero);
      final detached = push.detach();
      expect(client.unregisterCalls, 0);
      api.waiting!.complete();
      await detached;
      expect(api.calls, [
        'PUT /notifications/device',
        'DELETE /notifications/device',
      ]);
      push.dispose();
      await client.stream.close();
    },
  );
  test(
    'inbox provider refreshes when the signed-in passenger changes',
    () async {
      final api = NoticeApi();
      final container = ProviderContainer(
        overrides: [
          notificationRepositoryProvider.overrideWithValue(
            NotificationRepository(api),
          ),
          mobileAppConfigProvider.overrideWithValue(config),
          authControllerProvider.overrideWith(SignedIn.new),
        ],
      );
      final sub = container.listen(notificationPageProvider(1), (_, _) {});
      await container.read(notificationPageProvider(1).future);
      expect(api.calls.length, 1);
      container
          .read(authControllerProvider.notifier)
          .replacePassenger(passenger(2));
      await container.read(notificationPageProvider(1).future);
      expect(api.calls.length, 2);
      container.read(authControllerProvider.notifier).expireSession();
      expect(
        (await container.read(notificationPageProvider(1).future)).items,
        isEmpty,
      );
      sub.close();
      container.dispose();
    },
  );
  testWidgets('empty inbox remains refreshable without phone push', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authControllerProvider.overrideWith(SignedIn.new),
          mobileAppConfigProvider.overrideWithValue(config),
          notificationPageProvider(
            1,
          ).overrideWith((ref) async => const NotificationPage()),
        ],
        child: const MaterialApp(home: NotificationsScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('You’re all caught up'), findsOneWidget);
    expect(find.byType(RefreshIndicator), findsOneWidget);
    expect(find.text('Enable'), findsNothing);
  });

  testWidgets('small screen with large text can refresh and open trips', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    var loads = 0;
    final router = GoRouter(
      routes: [
        GoRoute(path: '/', builder: (_, _) => const NotificationsScreen()),
        GoRoute(
          path: '/tickets',
          builder: (_, _) => const Scaffold(body: Text('Trips destination')),
        ),
      ],
    );
    addTearDown(router.dispose);
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authControllerProvider.overrideWith(SignedIn.new),
          mobileAppConfigProvider.overrideWithValue(config),
          notificationPageProvider(1).overrideWith((ref) async {
            loads++;
            return const NotificationPage();
          }),
        ],
        child: MaterialApp.router(
          theme: AppTheme.light,
          routerConfig: router,
          builder: (context, child) => MediaQuery(
            data: MediaQuery.of(
              context,
            ).copyWith(textScaler: TextScaler.linear(1.8)),
            child: child!,
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    await tester.drag(find.byType(ListView), const Offset(0, 350));
    await tester.pumpAndSettle();
    expect(loads, 2);
    await tester.scrollUntilVisible(find.text('View my trips'), 240);
    await tester.pumpAndSettle();
    await tester.tap(find.text('View my trips'));
    await tester.pumpAndSettle();
    expect(find.text('Trips destination'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('populated inbox keeps read watermark and pagination controls', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final api = NoticeApi();
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authControllerProvider.overrideWith(SignedIn.new),
          mobileAppConfigProvider.overrideWithValue(config),
          notificationRepositoryProvider.overrideWithValue(
            NotificationRepository(api),
          ),
        ],
        child: MaterialApp(
          theme: AppTheme.light,
          builder: (context, child) => MediaQuery(
            data: MediaQuery.of(
              context,
            ).copyWith(textScaler: TextScaler.linear(1.8)),
            child: child!,
          ),
          home: const NotificationsScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Confirmed'), findsOneWidget);
    await tester.tap(find.text('Mark these and older as read'));
    await tester.pumpAndSettle();
    expect(api.lastData, {'through_id': 8});
    await tester.scrollUntilVisible(find.text('Next'), 240);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Page 2 of 2'), 240);
    await tester.pumpAndSettle();
    expect(find.text('Page 2 of 2'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
