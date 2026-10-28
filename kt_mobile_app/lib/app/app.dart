import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/providers.dart';
import '../core/theme/app_theme.dart';
import '../features/auth/application/auth_controller.dart';
import '../features/startup/application/startup_controller.dart';
import '../features/travel/application/travel_providers.dart';
import 'router.dart';
import '../features/notifications/notification_push.dart';
import '../features/notifications/notification_providers.dart';

class KainatTravelsApp extends ConsumerStatefulWidget {
  const KainatTravelsApp({super.key});

  @override
  ConsumerState<KainatTravelsApp> createState() => _KainatTravelsAppState();
}

class _KainatTravelsAppState extends ConsumerState<KainatTravelsApp>
    with WidgetsBindingObserver {
  late final StreamSubscription<void> _unauthorizedSubscription;
  Timer? _updateTimer;
  StreamSubscription<int>? _pushOpened;
  StreamSubscription<void>? _pushMessages;
  final _messengerKey = GlobalKey<ScaffoldMessengerState>();
  int? _pendingNotice;
  int? _pendingNoticeAccount;
  bool _openingNotice = false;
  bool _resuming = false;
  DateTime? _backgroundedAt;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    appRouter.routerDelegate.addListener(_openPendingNotice);
    final push = ref.read(pushClientProvider);
    _pushOpened = push.opened.listen(_queueNotice);
    _pushMessages = push.messages.listen((_) {
      ref.invalidate(notificationPageProvider);
      if (!_canOpenNotice) return;
      _messengerKey.currentState
        ?..hideCurrentSnackBar()
        ..showSnackBar(
          SnackBar(
            content: const Text('You have a new update.'),
            action: SnackBarAction(
              label: 'View alerts',
              onPressed: () {
                if (_canOpenNotice) appRouter.go('/notifications');
              },
            ),
          ),
        );
    });
    unawaited(
      push
          .initialNotice()
          .then((id) {
            if (mounted && id != null) {
              _queueNotice(id);
            }
          })
          .catchError((Object _) {}),
    );
    _updateTimer = Timer.periodic(const Duration(minutes: 1), (_) {
      if (WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed) {
        ref.read(startupControllerProvider.notifier).checkForUpdates();
      }
    });
    _unauthorizedSubscription = ref.read(apiClientProvider).unauthorized.listen(
      (_) {
        ref.read(authControllerProvider.notifier).expireSession();
        appRouter.go('/login');
      },
    );
  }

  @override
  void dispose() {
    _pushOpened?.cancel();
    _pushMessages?.cancel();
    _updateTimer?.cancel();
    appRouter.routerDelegate.removeListener(_openPendingNotice);
    WidgetsBinding.instance.removeObserver(this);
    _unauthorizedSubscription.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused) _backgroundedAt = DateTime.now();
    if (state == AppLifecycleState.resumed) unawaited(_resume());
  }

  Future<void> _resume() async {
    _resuming = true;
    final away = _backgroundedAt;
    _backgroundedAt = null;
    try {
      if (away != null &&
          DateTime.now().difference(away) >= const Duration(minutes: 1)) {
        await _lockQuickLogin();
      }
      if (!mounted) return;
      await ref.read(startupControllerProvider.notifier).checkForUpdates();
      if (!mounted) return;
      ref.invalidate(fleetGalleryProvider);
      ref.invalidate(notificationPageProvider);
      _syncPush();
    } finally {
      _resuming = false;
    }
    _openPendingNotice();
  }

  Future<void> _lockQuickLogin() async {
    if (!(await ref.read(biometricVaultProvider).status()).enabled ||
        !mounted) {
      return;
    }
    await ref.read(tokenStorageProvider).clear();
    if (!mounted) return;
    ref.read(authControllerProvider.notifier).lockForQuickLogin();
    appRouter.go('/login');
  }

  void _syncPush() {
    final auth = ref.read(authControllerProvider);
    unawaited(
      ref
          .read(notificationPushProvider)
          .sync(
            auth.passenger?.mobileVerified == true ? auth.passenger?.id : null,
            ref.read(mobileAppConfigProvider)?.features.pushNotifications ==
                true,
            sessionGeneration: auth.sessionGeneration,
          ),
    );
  }

  void _queueNotice(int id) {
    _pendingNotice = id;
    _pendingNoticeAccount = ref.read(authControllerProvider).passenger?.id;
    _openPendingNotice();
  }

  bool get _canOpenNotice {
    if (!mounted ||
        _resuming ||
        _backgroundedAt != null ||
        ref.read(authControllerProvider).passenger?.mobileVerified != true) {
      return false;
    }
    final decision = ref.read(startupControllerProvider).value;
    if (decision == null ||
        decision.destination == StartupDestination.maintenance ||
        decision.destination == StartupDestination.forceUpdate) {
      return false;
    }
    return true;
  }

  void _openPendingNotice() {
    if (_pendingNotice == null || _openingNotice || !_canOpenNotice) return;
    // Let splash/login finish their own navigation first. Otherwise their
    // post-frame redirect can replace the notification with the home screen.
    final path = appRouter.routeInformationProvider.value.uri.path;
    if (const [
      '/',
      '/login',
      '/verify-otp',
      '/maintenance',
      '/update-required',
    ].contains(path)) {
      return;
    }
    _openingNotice = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _openingNotice = false;
      if (_pendingNotice != null && _canOpenNotice) {
        final id = _pendingNotice!;
        _pendingNotice = null;
        _pendingNoticeAccount = null;
        appRouter.go('/notifications/$id');
      }
    });
    WidgetsBinding.instance.ensureVisualUpdate();
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(passengerSessionProvider, (previous, next) {
      if (_pendingNoticeAccount != null &&
          next.passengerId != null &&
          _pendingNoticeAccount != next.passengerId) {
        _pendingNotice = null;
        _pendingNoticeAccount = null;
      }
      if (previous?.passengerId != null && previous != next) {
        // Discard private routes and their local forms/WebViews on logout,
        // expiry, biometric lock, or a direct switch to another login session.
        // Preserve a tap through the resume-time biometric lock for this same
        // passenger; explicit logout/expiry still discards the pending tap.
        if (!(_resuming && next.passengerId == null)) {
          _pendingNotice = null;
          _pendingNoticeAccount = null;
        }
        _messengerKey.currentState?.removeCurrentSnackBar();
        appRouter.go(next.passengerId == null ? '/login' : '/home');
      }
      _syncPush();
      _openPendingNotice();
    });
    ref.listen(startupControllerProvider, (_, next) {
      final decision = next.value;
      if (decision != null) appAccessDecision.value = decision;
      if (!_canOpenNotice) _messengerKey.currentState?.removeCurrentSnackBar();
      _syncPush();
      _openPendingNotice();
    });
    return MaterialApp.router(
      title: 'Kainat Travels',
      scaffoldMessengerKey: _messengerKey,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: appRouter,
    );
  }
}
