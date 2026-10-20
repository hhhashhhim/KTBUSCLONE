import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/app/router.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/application/immediate_update.dart';
import 'package:kt_mobile_app/features/startup/presentation/startup_screens.dart';
import 'package:kt_mobile_app/features/startup/data/app_config_repository.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';
import 'package:kt_mobile_app/features/startup/domain/update_policy.dart';

MobileAppConfig release({
  String latest = '1.0.1',
  String minimum = '1.0.0',
  bool forced = true,
}) => MobileAppConfig.fromJson({
  'latest_version': latest,
  'minimum_supported_version': minimum,
  'force_update': forced,
});

class FakeConfigRepository implements AppConfigRepository {
  MobileAppConfig config = release();
  bool offline = false;
  @override
  Future<MobileAppConfig> fetch() async {
    if (offline) throw Exception('Offline');
    return config;
  }
}

class ReadyStartupController extends StartupController {
  @override
  Future<StartupDecision> build() async =>
      StartupDecision(StartupDestination.home, release(latest: '1.0.0'));
}

void main() {
  test('blank optional configuration values are treated as unavailable', () {
    final config = MobileAppConfig.fromJson({
      'support_phone': '',
      'support_whatsapp': '   ',
      'support_email': ' info@kainattravels.com ',
      'android_store_url': '',
      'ios_store_url': ' ',
    });
    expect(config.supportPhone, isNull);
    expect(config.supportWhatsapp, isNull);
    expect(config.supportEmail, 'info@kainattravels.com');
    expect(config.androidStoreUrl, isNull);
    expect(config.iosStoreUrl, isNull);
  });

  testWidgets('missing store link retains the update gate and offers support', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          mobileAppConfigProvider.overrideWithValue(release()),
          immediateUpdateProvider.overrideWithValue(() async => false),
        ],
        child: const MaterialApp(home: ForceUpdateScreen()),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Update now'));
    await tester.pumpAndSettle();
    expect(
      find.text('The update link is unavailable. Please contact support.'),
      findsOneWidget,
    );
    expect(find.text('Contact support'), findsOneWidget);
    expect(find.text('Check again'), findsOneWidget);
    expect(tester.widget<PopScope>(find.byType(PopScope)).canPop, isFalse);
    expect(find.text('Update required'), findsOneWidget);
  });

  testWidgets('routine policy refresh preserves the active search route', (
    tester,
  ) async {
    appAccessDecision.value = StartupDecision(
      StartupDestination.home,
      release(),
    );
    final query = Object();
    final router = GoRouter(
      refreshListenable: appAccessDecision,
      redirect: (_, state) =>
          appAccessRedirect(state.uri.path, appAccessDecision.value),
      routes: [
        GoRoute(path: '/', builder: (_, _) => const Text('Search form')),
        GoRoute(
          path: '/results',
          builder: (_, state) => Text(
            identical(state.extra, query) ? 'Selected journey' : 'Query lost',
          ),
        ),
        GoRoute(
          path: '/update-required',
          builder: (_, _) => const Text('Update gate'),
        ),
      ],
    );
    addTearDown(router.dispose);
    await tester.pumpWidget(MaterialApp.router(routerConfig: router));
    router.push('/results', extra: query);
    await tester.pumpAndSettle();
    expect(find.text('Selected journey'), findsOneWidget);
    appAccessDecision.value = StartupDecision(
      StartupDestination.home,
      release(),
    );
    await tester.pumpAndSettle();
    expect(find.text('Selected journey'), findsOneWidget);
    appAccessDecision.value = StartupDecision(
      StartupDestination.forceUpdate,
      release(latest: '2.0.0'),
    );
    await tester.pumpAndSettle();
    expect(find.text('Update gate'), findsOneWidget);
    appAccessDecision.value = null;
  });

  testWidgets(
    'mandatory update starts automatically and cancellation keeps the gate',
    (tester) async {
      var attempts = 0;
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            mobileAppConfigProvider.overrideWithValue(release()),
            immediateUpdateProvider.overrideWithValue(() async {
              attempts++;
              return true;
            }),
          ],
          child: const MaterialApp(home: ForceUpdateScreen()),
        ),
      );
      await tester.pumpAndSettle();
      expect(attempts, 1);
      expect(find.text('Update required'), findsOneWidget);
      final pop = tester.widget<PopScope>(find.byType(PopScope));
      expect(pop.canPop, isFalse);
      await tester.tap(find.text('Update now'));
      await tester.pumpAndSettle();
      expect(attempts, 2);
      expect(find.text('Update required'), findsOneWidget);
    },
  );

  test('mandatory release blocks old versions but allows latest and newer', () {
    expect(requiresAppUpdate('1.0.0', release()), isTrue);
    expect(requiresAppUpdate('1.0.1', release()), isFalse);
    expect(requiresAppUpdate('1.1.0', release()), isFalse);
  });
  test('minimum version is enforced even for an optional release', () {
    expect(requiresAppUpdate('1.0.0', release(forced: false)), isFalse);
    expect(
      requiresAppUpdate('1.0.0', release(minimum: '1.0.1', forced: false)),
      isTrue,
    );
  });
  test('version components are compared numerically', () {
    expect(compareAppVersions('1.10.0', '1.2.0'), greaterThan(0));
    expect(compareAppVersions('2.0.0', '1.99.99'), greaterThan(0));
    expect(compareAppVersions('1.0', '1.0.0'), 0);
  });
  test('all app routes stay blocked while an update is required', () {
    final decision = StartupDecision(StartupDestination.forceUpdate, release());
    for (final route in [
      '/home',
      '/login',
      '/search/checkout',
      '/tickets/42',
    ]) {
      expect(appAccessRedirect(route, decision), '/update-required');
    }
    expect(appAccessRedirect('/update-required', decision), isNull);
    expect(
      appAccessRedirect(
        '/update-required',
        StartupDecision(StartupDestination.home, release()),
      ),
      '/',
    );
  });
  test(
    'refresh detects a new mandatory release and offline cannot remove it',
    () async {
      final repository = FakeConfigRepository();
      final container = ProviderContainer(
        overrides: [
          appConfigRepositoryProvider.overrideWithValue(repository),
          installedAppVersionProvider.overrideWith((ref) async => '1.0.0'),
          startupControllerProvider.overrideWith(ReadyStartupController.new),
        ],
      );
      addTearDown(container.dispose);
      expect(
        (await container.read(startupControllerProvider.future)).destination,
        StartupDestination.home,
      );
      final controller = container.read(startupControllerProvider.notifier);
      await controller.checkForUpdates();
      expect(
        container.read(startupControllerProvider).value!.destination,
        StartupDestination.forceUpdate,
      );
      repository.offline = true;
      await controller.checkForUpdates();
      expect(
        container.read(startupControllerProvider).value!.destination,
        StartupDestination.forceUpdate,
      );
    },
  );
}
