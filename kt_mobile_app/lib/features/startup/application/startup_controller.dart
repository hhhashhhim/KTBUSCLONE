import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../core/providers.dart';
import '../../auth/application/auth_controller.dart';
import '../data/app_config_repository.dart';
import '../domain/app_config.dart';
import '../domain/update_policy.dart';

enum StartupDestination { login, home, maintenance, forceUpdate }

class StartupDecision {
  const StartupDecision(this.destination, this.config, {this.message});

  final StartupDestination destination;
  final MobileAppConfig config;
  final String? message;
}

final appConfigRepositoryProvider = Provider<AppConfigRepository>(
  (ref) => AppConfigRepository(ref.watch(apiClientProvider)),
);

final installedAppVersionProvider = FutureProvider<String>(
  (ref) async => (await PackageInfo.fromPlatform()).version,
);

final startupControllerProvider =
    AsyncNotifierProvider<StartupController, StartupDecision>(
      StartupController.new,
    );

final mobileAppConfigProvider = Provider<MobileAppConfig?>((ref) {
  return ref.watch(startupControllerProvider).value?.config;
});

class StartupController extends AsyncNotifier<StartupDecision> {
  bool _checkingUpdates = false;

  @override
  Future<StartupDecision> build() async {
    final connectivity = await Connectivity().checkConnectivity();
    if (connectivity.every((result) => result == ConnectivityResult.none)) {
      throw const SocketException('No internet connection.');
    }

    final config = await ref.read(appConfigRepositoryProvider).fetch();
    if (config.maintenanceMode) {
      return StartupDecision(
        StartupDestination.maintenance,
        config,
        message: config.maintenanceMessage,
      );
    }

    final version = await ref.read(installedAppVersionProvider.future);
    if (requiresAppUpdate(version, config)) {
      return StartupDecision(StartupDestination.forceUpdate, config);
    }

    final authenticated = await ref
        .read(authControllerProvider.notifier)
        .restoreSession();
    return StartupDecision(
      authenticated ? StartupDestination.home : StartupDestination.login,
      config,
    );
  }

  /// Refresh release policy without resetting the passenger's session or flow.
  Future<void> checkForUpdates() async {
    if (_checkingUpdates || !state.hasValue) return;
    _checkingUpdates = true;
    try {
      final config = await ref.read(appConfigRepositoryProvider).fetch();
      final version = await ref.read(installedAppVersionProvider.future);
      if (!ref.mounted) return;
      final wasBlocked = [
        StartupDestination.forceUpdate,
        StartupDestination.maintenance,
      ].contains(state.value?.destination);
      if (wasBlocked &&
          !config.maintenanceMode &&
          !requiresAppUpdate(version, config) &&
          !ref.read(authControllerProvider).isAuthenticated) {
        await ref.read(authControllerProvider.notifier).restoreSession();
        if (!ref.mounted) return;
      }
      final destination = config.maintenanceMode
          ? StartupDestination.maintenance
          : requiresAppUpdate(version, config)
          ? StartupDestination.forceUpdate
          : ref.read(authControllerProvider).isAuthenticated
          ? StartupDestination.home
          : StartupDestination.login;
      state = AsyncData(StartupDecision(destination, config));
    } catch (_) {
      // A failed refresh must not clear a previously required update.
    } finally {
      _checkingUpdates = false;
    }
  }
}
