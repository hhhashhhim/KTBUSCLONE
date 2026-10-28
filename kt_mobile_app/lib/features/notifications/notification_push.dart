import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/providers.dart';

abstract class PushClient {
  bool get available;
  Stream<String> get registrations;
  Stream<int> get opened;
  Stream<void> get messages;
  Future<bool> permitted();
  Future<bool> requestPermission();
  Future<void> register();
  Future<void> unregister();
  Future<int?> initialNotice();
}

class FirebasePushClient implements PushClient {
  FirebasePushClient({FirebaseMessaging? messaging, bool? isIOS})
    : _messaging = messaging,
      _isIOS = isIOS ?? Platform.isIOS;

  final FirebaseMessaging? _messaging;
  final bool _isIOS;
  int _generation = 0;
  static bool configured = false;
  static Future<void> initialize() async {
    const project = String.fromEnvironment('FIREBASE_PROJECT_ID');
    const sender = String.fromEnvironment('FIREBASE_MESSAGING_SENDER_ID');
    const androidKey = String.fromEnvironment('FIREBASE_ANDROID_API_KEY');
    const iosKey = String.fromEnvironment('FIREBASE_IOS_API_KEY');
    const androidApp = String.fromEnvironment('FIREBASE_ANDROID_APP_ID');
    const iosApp = String.fromEnvironment('FIREBASE_IOS_APP_ID');
    if ((!Platform.isAndroid && !Platform.isIOS) ||
        project.isEmpty ||
        sender.isEmpty) {
      return;
    }
    final key = Platform.isIOS ? iosKey : androidKey;
    final app = Platform.isIOS ? iosApp : androidApp;
    if (key.isEmpty || app.isEmpty) return;
    try {
      await Firebase.initializeApp(
        options: FirebaseOptions(
          apiKey: key,
          appId: app,
          messagingSenderId: sender,
          projectId: project,
          iosBundleId: Platform.isIOS ? 'com.kainattravels.ktMobileApp' : null,
        ),
      );
      await FirebaseMessaging.instance.setAutoInitEnabled(false);
      configured = true;
    } catch (_) {
      configured = false;
    }
  }

  FirebaseMessaging get _sdk => _messaging ?? FirebaseMessaging.instance;
  @override
  bool get available => configured;
  @override
  Stream<String> get registrations =>
      available ? _sdk.onRegistered : const Stream.empty();
  @override
  Stream<int> get opened => available
      ? FirebaseMessaging.onMessageOpenedApp
            .map(_id)
            .where((id) => id != null)
            .cast<int>()
      : const Stream.empty();
  @override
  Stream<void> get messages => available
      ? FirebaseMessaging.onMessage.map((_) {})
      : const Stream.empty();
  static int? _id(RemoteMessage message) {
    final id = int.tryParse(message.data['notification_id']?.toString() ?? '');
    return id != null && id > 0 ? id : null;
  }

  bool _allowed(NotificationSettings settings) =>
      settings.authorizationStatus == AuthorizationStatus.authorized ||
      settings.authorizationStatus == AuthorizationStatus.provisional;
  @override
  Future<bool> permitted() async =>
      available && _allowed(await _sdk.getNotificationSettings());
  @override
  Future<bool> requestPermission() async =>
      available &&
      _allowed(
        await _sdk.requestPermission(alert: true, badge: true, sound: true),
      );
  @override
  Future<void> register() async {
    if (!available) return;
    final generation = _generation;
    if (_isIOS) {
      // The Apple plugin only requests/forwards the APNs token with auto-init
      // enabled. This runs after permission and authenticated account checks.
      await _sdk.setAutoInitEnabled(true);
      for (var attempt = 0; attempt < 20; attempt++) {
        if (generation != _generation) return;
        if (await _sdk.getAPNSToken() != null) break;
        if (attempt == 19) {
          throw StateError(
            'Apple notification registration is not ready. Please try again.',
          );
        }
        await Future<void>.delayed(const Duration(milliseconds: 500));
      }
    }
    if (generation == _generation) await _sdk.register();
  }

  @override
  Future<void> unregister() async {
    _generation++;
    if (!available) return;
    await _sdk.setAutoInitEnabled(false);
    await _sdk.unregister();
  }

  @override
  Future<int?> initialNotice() async {
    if (!available) return null;
    final message = await _sdk.getInitialMessage();
    return message == null ? null : _id(message);
  }
}

final pushClientProvider = Provider<PushClient>((ref) => FirebasePushClient());
final notificationPushProvider = Provider<NotificationPush>((ref) {
  final service = NotificationPush(
    ref.watch(apiClientProvider),
    ref.watch(pushClientProvider),
  );
  ref.onDispose(service.dispose);
  return service;
});

/// Binds a device to the current authenticated passenger, never to a phone number.
class NotificationPush {
  NotificationPush(
    this.api,
    this.client, {
    Duration registrationTimeout = const Duration(seconds: 30),
  }) : _registrationTimeout = registrationTimeout {
    _registration = client.registrations.listen(
      (id) => unawaited(_bind(id)),
      // An active registration attempt has its own error listener. Background
      // SDK failures must leave the inbox usable, rather than escape the zone.
      onError: (Object error) => _bindingError = error,
    );
  }
  final ApiClient api;
  final PushClient client;
  final Duration _registrationTimeout;
  late final StreamSubscription<String> _registration;
  int? _account;
  int? _sessionGeneration;
  String? _registrationId;
  bool _enabled = false;
  int _generation = 0;
  Future<void> _pending = Future.value();
  Object? _bindingError;
  Future<void>? _registering;

  Future<void> sync(
    int? account,
    bool enabled, {
    int sessionGeneration = 0,
  }) async {
    if (_account != account ||
        _enabled != enabled ||
        _sessionGeneration != sessionGeneration) {
      _generation++;
    }
    _account = account;
    _sessionGeneration = sessionGeneration;
    _enabled = enabled;
    if (account == null || !enabled || !client.available) return;
    final generation = _generation;
    try {
      if (await client.permitted() && generation == _generation) {
        await _register();
      }
    } catch (_) {
      /* Inbox remains available when push is unavailable. */
    }
  }

  Future<void> enable() async {
    if (_account == null || !_enabled || !client.available) {
      throw StateError(
        'Phone alerts are not available yet. Your inbox still works.',
      );
    }
    final generation = _generation;
    if (!await client.requestPermission()) {
      throw StateError(
        'Phone notifications are disabled. You can allow them in your phone settings.',
      );
    }
    if (generation != _generation || _account == null || !_enabled) {
      throw StateError('Your session changed. Sign in and try again.');
    }
    await _register();
    await _pending;
    if (generation != _generation || _account == null || !_enabled) {
      throw StateError('Your session changed. Sign in and try again.');
    }
    if (_bindingError != null) {
      throw StateError(
        'Phone registration could not be saved. Try again when connected.',
      );
    }
  }

  Future<void> _register() => _registering ??= _registerOnce().whenComplete(() {
    _registering = null;
  });

  Future<void> _registerOnce() async {
    _bindingError = null;
    final received = Completer<void>();
    final subscription = client.registrations.listen(
      (_) {
        if (!received.isCompleted) received.complete();
      },
      onError: (Object error, StackTrace stack) {
        if (!received.isCompleted) received.completeError(error, stack);
      },
    );
    try {
      // Subscribe before registration, fail promptly on an SDK error, and bound
      // the entire attempt even if the native register call never completes.
      await Future.wait<void>([
        received.future,
        Future.sync(client.register),
      ], eagerError: true).timeout(_registrationTimeout);
    } on TimeoutException {
      throw StateError('Phone registration timed out. Please try again.');
    } finally {
      await subscription.cancel();
    }
  }

  Future<void> _bind(String id) async {
    if (_account == null || !_enabled) return;
    final generation = _generation;
    _registrationId = id;
    _bindingError = null;
    // Serialize registrations so a stale request cannot overwrite a newer account binding.
    final work = _pending.then((_) async {
      if (generation != _generation || _account == null) return;
      try {
        await api.put(
          '/notifications/device',
          data: {'token': id, 'platform': Platform.isIOS ? 'ios' : 'android'},
        );
        if (generation == _generation) _bindingError = null;
      } catch (error) {
        if (generation == _generation) _bindingError = error;
        /* Re-register on the next foreground/authentication transition. */
      }
    });
    _pending = work;
    await work;
  }

  Future<void> detach() async {
    _generation++;
    _account = null;
    _enabled = false;
    await _pending;
    final id = _registrationId;
    _registrationId = null;
    try {
      if (id != null) {
        await api.delete('/notifications/device', data: {'token': id});
      }
    } catch (_) {}
    try {
      await client.unregister();
    } catch (_) {}
  }

  void dispose() {
    _generation++;
    _registration.cancel();
  }
}
