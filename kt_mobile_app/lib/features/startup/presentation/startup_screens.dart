import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../auth/application/auth_controller.dart';
import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../../core/widgets/support_links.dart';
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
            loading: () => CustomScrollView(
              slivers: [
                SliverFillRemaining(
                  hasScrollBody: false,
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(24, 80, 24, 28),
                    child: Column(
                      children: [
                        Spacer(flex: 3),
                        _StartupIntro(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                'Kainat Travels',
                                textAlign: TextAlign.center,
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
                                style: TextStyle(
                                  color: Colors.white70,
                                  fontSize: 13,
                                ),
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
                                  Flexible(
                                    child: Text(
                                      'Checking for updates…',
                                      style: TextStyle(
                                        color: Colors.white70,
                                        fontSize: 13,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        Spacer(flex: 4),
                      ],
                    ),
                  ),
                ),
              ],
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

class _StartupIntro extends StatelessWidget {
  const _StartupIntro({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final reduceMotion = MediaQuery.disableAnimationsOf(context);
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: reduceMotion ? 1 : 0, end: 1),
      duration: reduceMotion
          ? Duration.zero
          : const Duration(milliseconds: 480),
      curve: Curves.easeOutCubic,
      builder: (context, value, child) => Opacity(
        opacity: value,
        child: Transform.translate(
          offset: Offset(0, 12 * (1 - value)),
          child: Transform.scale(scale: .96 + .04 * value, child: child),
        ),
      ),
      child: child,
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
          const SnackBar(
            content: Text(
              'The update link is unavailable. Please contact support.',
            ),
          ),
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
        bottomNavigationBar: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Wrap(
              alignment: WrapAlignment.center,
              spacing: AppSpacing.md,
              children: [
                TextButton.icon(
                  onPressed: _busy
                      ? null
                      : () async {
                          setState(() => _busy = true);
                          try {
                            await ref
                                .read(startupControllerProvider.notifier)
                                .checkForUpdates();
                          } finally {
                            if (mounted) setState(() => _busy = false);
                          }
                        },
                  icon: const Icon(Icons.refresh_rounded),
                  label: const Text('Check again'),
                ),
                TextButton.icon(
                  onPressed: () => SupportLinks.open(context),
                  icon: const Icon(Icons.support_agent_rounded),
                  label: const Text('Contact support'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
