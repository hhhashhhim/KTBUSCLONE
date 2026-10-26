import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/widgets/app_widgets.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';
import 'notification_providers.dart';
import 'notification_push.dart';
import 'notification_widgets.dart';
export 'notification_providers.dart' show notificationsProvider;

String notificationTime(DateTime date) =>
    '${DateFormat('d MMM, h:mm a').format(date.toUtc().add(const Duration(hours: 5)))} PKT';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});
  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  int page = 1;
  bool busy = false;
  String? message;
  Future<void> perform(Future<void> Function() action) async {
    setState(() {
      busy = true;
      message = null;
    });
    try {
      await action();
    } catch (error) {
      if (mounted) setState(() => message = error.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(authControllerProvider.select((s) => s.passenger?.id), (_, _) {
      setState(() {
        page = 1;
        message = null;
      });
    });
    final config = ref.watch(mobileAppConfigProvider);
    final signedIn = ref.watch(authControllerProvider).isAuthenticated;
    if (!signedIn) {
      return Scaffold(
        appBar: const AppPageHeader(title: 'Alerts'),
        body: AppStateView(
          icon: Icons.lock_outline,
          title: 'Sign in to view your alerts',
          message:
              'Booking updates and announcements are private to your account.',
          actionLabel: 'Sign in',
          onAction: () => context.go('/login'),
        ),
      );
    }
    if (config?.features.notifications != true) {
      return const Scaffold(
        appBar: AppPageHeader(title: 'Alerts'),
        body: AppStateView(
          icon: Icons.notifications_none,
          title: 'Alerts are not available yet',
          message: 'Your booking status is available in My Trips.',
        ),
      );
    }
    final data = ref.watch(notificationPageProvider(page));
    return Scaffold(
      appBar: const AppPageHeader(
        title: 'Alerts',
        subtitle: 'Your journey, up to date',
      ),
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFEEF6F0), Color(0xFFFAFCFA)],
            stops: [0, 0.7],
          ),
        ),
        child: Column(
          children: [
            if (config?.features.pushNotifications == true &&
                ref.watch(pushClientProvider).available)
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                child: AlertSurface(
                  padding: const EdgeInsets.fromLTRB(16, 8, 8, 8),
                  child: Row(
                    children: [
                      const Icon(Icons.notifications_active_outlined, size: 22),
                      const SizedBox(width: 12),
                      const Expanded(child: Text('Get updates on your phone')),
                      TextButton(
                        onPressed: busy
                            ? null
                            : () => perform(() async {
                                await ref
                                    .read(notificationPushProvider)
                                    .enable();
                                if (mounted) {
                                  setState(
                                    () => message = 'Phone alerts enabled.',
                                  );
                                }
                              }),
                        child: const Text('Enable'),
                      ),
                    ],
                  ),
                ),
              ),
            if (message != null)
              Padding(
                padding: const EdgeInsets.all(12),
                child: Semantics(liveRegion: true, child: Text(message!)),
              ),
            Expanded(
              child: data.when(
                loading: () => const AppLoadingView(),
                error: (error, _) => AppStateView(
                  icon: Icons.wifi_off,
                  title: 'Could not load alerts',
                  message: error.toString(),
                  actionLabel: 'Retry',
                  onAction: () =>
                      ref.invalidate(notificationPageProvider(page)),
                ),
                data: (value) => RefreshIndicator(
                  onRefresh: () async {
                    ref.invalidate(notificationPageProvider);
                    await ref.read(notificationPageProvider(page).future);
                  },
                  child: LayoutBuilder(
                    builder: (context, constraints) => ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: EdgeInsets.fromLTRB(
                        ((constraints.maxWidth - 560) / 2).clamp(
                          20.0,
                          double.infinity,
                        ),
                        24,
                        ((constraints.maxWidth - 560) / 2).clamp(
                          20.0,
                          double.infinity,
                        ),
                        28,
                      ),
                      children: [
                        AlertsInboxHeading(unread: value.unread),
                        if (value.unread > 0 && value.items.isNotEmpty)
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton.icon(
                              onPressed: busy
                                  ? null
                                  : () => perform(() async {
                                      await ref
                                          .read(notificationRepositoryProvider)
                                          .readThrough(value.items.first.id);
                                      ref.invalidate(notificationPageProvider);
                                    }),
                              icon: const Icon(
                                Icons.done_all_rounded,
                                size: 18,
                              ),
                              label: const Text('Mark these and older as read'),
                            ),
                          ),
                        const SizedBox(height: 22),
                        if (value.items.isEmpty)
                          AlertsEmptyState(
                            onViewTrips: () => context.go('/tickets'),
                          ),
                        for (final item in value.items)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 16),
                            child: AlertInboxCard(
                              item: item,
                              timeLabel: notificationTime(item.createdAt),
                              onTap: () =>
                                  context.push('/notifications/${item.id}'),
                            ),
                          ),
                        const SizedBox(height: 26),
                        const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.swipe_down_rounded,
                              size: 16,
                              color: Color(0xFF607568),
                            ),
                            SizedBox(width: 7),
                            Flexible(
                              child: Text(
                                'Pull down to refresh',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Color(0xFF607568),
                                ),
                              ),
                            ),
                          ],
                        ),
                        if (value.lastPage > 1)
                          Wrap(
                            alignment: WrapAlignment.spaceBetween,
                            crossAxisAlignment: WrapCrossAlignment.center,
                            spacing: 12,
                            runSpacing: 8,
                            children: [
                              TextButton(
                                onPressed: page > 1
                                    ? () => setState(() => page--)
                                    : null,
                                child: const Text('Previous'),
                              ),
                              Text('Page $page of ${value.lastPage}'),
                              TextButton(
                                onPressed: page < value.lastPage
                                    ? () => setState(() => page++)
                                    : null,
                                child: const Text('Next'),
                              ),
                            ],
                          ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class NotificationDetailScreen extends ConsumerWidget {
  const NotificationDetailScreen({required this.id, super.key});
  final int id;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notice = ref.watch(notificationDetailProvider(id));
    ref.listen(notificationDetailProvider(id), (_, next) {
      final item = next.value;
      if (!next.isLoading && item != null && item.readAt == null) {
        ref
            .read(notificationRepositoryProvider)
            .read(id)
            .then((_) {
              ref.invalidate(notificationPageProvider);
            })
            .catchError((Object _) {});
      }
    });
    return Scaffold(
      appBar: const AppPageHeader(title: 'Your update'),
      body: notice.when(
        loading: () => const AppLoadingView(),
        error: (error, _) => AppStateView(
          icon: Icons.info_outline,
          title: 'Update unavailable',
          message: error.toString(),
          actionLabel: 'Back to alerts',
          onAction: () => context.go('/notifications'),
        ),
        data: (item) => ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Text(item.title, style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text(notificationTime(item.createdAt)),
            const SizedBox(height: 24),
            Text(item.body, style: Theme.of(context).textTheme.bodyLarge),
            if (item.bookingId != null)
              Padding(
                padding: const EdgeInsets.only(top: 24),
                child: FilledButton(
                  onPressed: () => context.push('/tickets/${item.bookingId}'),
                  child: const Text('View booking'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
