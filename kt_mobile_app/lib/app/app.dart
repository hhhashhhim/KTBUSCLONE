import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/providers.dart';
import '../core/theme/app_theme.dart';
import '../features/auth/application/auth_controller.dart';
import '../features/startup/application/startup_controller.dart';
import 'router.dart';

class KainatTravelsApp extends ConsumerStatefulWidget {
  const KainatTravelsApp({super.key});

  @override
  ConsumerState<KainatTravelsApp> createState() => _KainatTravelsAppState();
}

class _KainatTravelsAppState extends ConsumerState<KainatTravelsApp>
    with WidgetsBindingObserver {
  late final StreamSubscription<void> _unauthorizedSubscription;
  Timer? _updateTimer;
  DateTime? _backgroundedAt;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
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
    _updateTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    _unauthorizedSubscription.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused) _backgroundedAt = DateTime.now();
    if (state == AppLifecycleState.resumed) {
      final away = _backgroundedAt;
      _backgroundedAt = null;
      if (away != null &&
          DateTime.now().difference(away) >= const Duration(minutes: 1)) {
        unawaited(_lockQuickLogin());
      }
      ref.read(startupControllerProvider.notifier).checkForUpdates();
    }
  }

  Future<void> _lockQuickLogin() async {
    if (!(await ref.read(biometricVaultProvider).status()).enabled || !mounted) {
      return;
    }
    await ref.read(tokenStorageProvider).clear();
    if (!mounted) return;
    ref.read(authControllerProvider.notifier).lockForQuickLogin();
    appRouter.go('/login');
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(startupControllerProvider, (_, next) {
      final decision = next.value;
      if (decision != null) appAccessDecision.value = decision;
    });
    return MaterialApp.router(
      title: 'Kainat Travels',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: appRouter,
    );
  }
}
