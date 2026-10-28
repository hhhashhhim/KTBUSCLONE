import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/notifications/notification_push.dart';

class _Messaging implements FirebaseMessaging {
  final autoInitChanges = <bool>[];
  final requestedApns = Completer<void>();
  final apnsToken = Completer<String?>();
  int registrations = 0;
  int unregistrations = 0;

  @override
  Future<void> setAutoInitEnabled(bool enabled) async {
    autoInitChanges.add(enabled);
  }

  @override
  Future<String?> getAPNSToken() {
    if (!requestedApns.isCompleted) requestedApns.complete();
    return apnsToken.future;
  }

  @override
  Future<void> register({
    String? vapidKey,
    String? serviceWorkerScriptPath,
  }) async {
    registrations++;
  }

  @override
  Future<void> unregister() async {
    unregistrations++;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  setUp(() => FirebasePushClient.configured = true);
  tearDown(() => FirebasePushClient.configured = false);

  test('Apple registration starts APNs and waits for its token', () async {
    final messaging = _Messaging();
    final client = FirebasePushClient(messaging: messaging, isIOS: true);
    final registration = client.register();
    await messaging.requestedApns.future;
    expect(messaging.autoInitChanges, [true]);
    expect(messaging.registrations, 0);
    messaging.apnsToken.complete('test-apns-token');
    await registration;
    expect(messaging.registrations, 1);
    await client.unregister();
    expect(messaging.autoInitChanges, [true, false]);
    expect(messaging.unregistrations, 1);
  });

  test('logout cancels registration waiting for APNs', () async {
    final messaging = _Messaging();
    final client = FirebasePushClient(messaging: messaging, isIOS: true);
    final registration = client.register();
    await messaging.requestedApns.future;
    await client.unregister();
    messaging.apnsToken.complete('late-test-apns-token');
    await registration;
    expect(messaging.registrations, 0);
    expect(messaging.autoInitChanges.last, false);
  });

  test('Android registers explicitly without waiting for APNs', () async {
    final messaging = _Messaging();
    final client = FirebasePushClient(messaging: messaging, isIOS: false);
    await client.register();
    expect(messaging.registrations, 1);
    expect(messaging.autoInitChanges, isEmpty);
    expect(messaging.requestedApns.isCompleted, false);
  });
}
