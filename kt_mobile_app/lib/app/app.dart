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
  int? _pendingNotice;
  bool _resuming = false;
  DateTime? _backgroundedAt;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final push = ref.read(pushClientProvider);
    _pushOpened = push.opened.listen((id) {
      _pendingNotice = id;
      _openPendingNotice();
    });
    _pushMessages = push.messages.listen((_) {
      ref.invalidate(notificationPageProvider);
    });
    unawaited(
      push
          .initialNotice()
          .then((id) {
            if (mounted && id != null) {
              _pendingNotice = id;
              _openPendingNotice();
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
    unawaited(
      ref
          .read(notificationPushProvider)
          .sync(
            ref.read(authControllerProvider).passenger?.id,
            ref.read(mobileAppConfigProvider)?.features.pushNotifications ==
                true,
          ),
    );
  }

  void _openPendingNotice() {
    if (!mounted ||
        _resuming ||
        _backgroundedAt != null ||
        _pendingNotice == null ||
        !ref.read(authControllerProvider).isAuthenticated) {
      return;
    }
    final decision = ref.read(startupControllerProvider).value;
    if (decision == null ||
        decision.destination == StartupDestination.maintenance ||
        decision.destination == StartupDestination.forceUpdate) {
      return;
    }
    final id = _pendingNotice!;
    _pendingNotice = null;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && ref.read(authControllerProvider).isAuthenticated) {
        appRouter.go('/notifications/$id');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(passengerSessionProvider, (previous, next) {
      if (previous?.passengerId != null && previous != next) {
        // Discard private routes and their local forms/WebViews on logout,
        // expiry, biometric lock, or a direct switch to another login session.
        _pendingNotice = null;
        appRouter.go(next.passengerId == null ? '/login' : '/home');
      }
    });
    ref.listen(authControllerProvider.select((state) => state.passenger?.id), (
      _,
      _,
    ) {
      _syncPush();
      _openPendingNotice();
    });
    ref.listen(startupControllerProvider, (_, next) {
      final decision = next.value;
      if (decision != null) appAccessDecision.value = decision;
      _syncPush();
      _openPendingNotice();
    });
    return MaterialApp.router(
      title: 'Kainat Travels',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: appRouter,
    );
  }
}
