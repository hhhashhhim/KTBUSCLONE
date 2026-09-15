import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../auth/application/auth_controller.dart';
import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_widgets.dart';
import '../application/startup_controller.dart';
import '../application/immediate_update.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final startup = ref.watch(startupControllerProvider);
    startup.whenData((decision) {
      final route = switch (decision.destination) {
        StartupDestination.home =>
          ref.read(authControllerProvider).passenger?.mobileVerified == true
              ? '/home'
              : '/verify-otp',
        StartupDestination.login => '/login',
        StartupDestination.maintenance => '/maintenance',
        StartupDestination.forceUpdate => '/update-required',
      };
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (context.mounted) context.go(route);
      });
    });
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(gradient: AppGradients.splash),
        child: SafeArea(
          child: startup.when(
            data: (_) => const Center(
              child: CircularProgressIndicator(color: Colors.white),
            ),
            loading: () => const Padding(
              padding: EdgeInsets.fromLTRB(24, 80, 24, 28),
              child: Column(
                children: [
                  Spacer(flex: 3),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.all(Radius.circular(28)),
                      boxShadow: [
                        BoxShadow(
                          color: Color(0x33002A12),
                          blurRadius: 28,
                          offset: Offset(0, 10),
                        ),
                      ],
                    ),
                    child: SizedBox(
                      width: 116,
                      height: 116,
                      child: Center(child: AppLogo(height: 64)),
                    ),
                  ),
                  SizedBox(height: 28),
                  Text(
                    'Kainat Travels',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -.4,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Comfortable intercity travel across Pakistan',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  SizedBox(height: 28),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          color: Colors.white,
                          strokeWidth: 2,
                        ),
                      ),
                      SizedBox(width: 12),
                      Text(
                        'Checking for updates…',
                        style: TextStyle(color: Colors.white70, fontSize: 13),
                      ),
                    ],
                  ),
                  Spacer(flex: 4),
                ],
              ),
            ),
            error: (error, _) => Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Center(
                child: AppCard(
                  child: AppStateView(
                    icon: Icons.wifi_off_rounded,
                    title: 'Unable to start',
                    message: error.toString(),
                    actionLabel: 'Try again',
                    onAction: () => ref.invalidate(startupControllerProvider),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class MaintenanceScreen extends ConsumerWidget {
  const MaintenanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final config = ref.watch(mobileAppConfigProvider);
    return Scaffold(
      body: AppStateView(
        icon: Icons.engineering_rounded,
        title: 'We’ll be back shortly',
        message:
            config?.maintenanceMessage ??
            'Kainat Travels is undergoing scheduled maintenance.',
        actionLabel: 'Check again',
        onAction: () {
          ref.invalidate(startupControllerProvider);
          context.go('/');
        },
      ),
    );
  }
}

class ForceUpdateScreen extends ConsumerStatefulWidget {
  const ForceUpdateScreen({super.key});

  @override
  ConsumerState<ForceUpdateScreen> createState() => _ForceUpdateScreenState();
}

class _ForceUpdateScreenState extends ConsumerState<ForceUpdateScreen> {
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _update(openStore: false);
    });
  }

  Future<void> _update({bool openStore = true}) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final handled = await ref.read(immediateUpdateProvider)();
      if (!mounted || handled || !openStore) return;
      final config = ref.read(mobileAppConfigProvider);
      final url = Platform.isIOS
          ? config?.iosStoreUrl
          : config?.androidStoreUrl;
      if (url == null || url.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Store link is not configured yet.')),
        );
        return;
      }
      final uri = Uri.tryParse(url);
      if (uri == null ||
          !['https', 'market', 'itms-apps'].contains(uri.scheme) ||
          !await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        throw const FormatException('Unable to open update link.');
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Unable to open the update. Please try again.'),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        body: AppStateView(
          icon: Icons.system_update_alt_rounded,
          title: 'Update required',
          message:
              'A newer version of Kainat Travels is required to continue safely.',
          actionLabel: _busy ? 'Checking for update…' : 'Update now',
          onAction: _busy ? null : () => _update(),
        ),
      ),
    );
  }
}
