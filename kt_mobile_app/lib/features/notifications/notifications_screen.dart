import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/widgets/app_widgets.dart';
import '../../core/theme/app_colors.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';
import 'notification_providers.dart';
import 'notification_push.dart';
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
      appBar: const AppPageHeader(title: 'Alerts'),
      body: Column(
        children: [
          if (config?.features.pushNotifications == true &&
              ref.watch(pushClientProvider).available)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: Row(
                children: [
                  const Expanded(child: Text('Get updates on your phone')),
                  TextButton(
                    onPressed: busy
                        ? null
                        : () => perform(() async {
                            await ref.read(notificationPushProvider).enable();
                            if (mounted) {
                              setState(() => message = 'Phone alerts enabled.');
                            }
                          }),
                    child: const Text('Enable'),
                  ),
                ],
              ),
            ),
          if (message != null)
            Padding(
              padding: const EdgeInsets.all(12),
              child: Text(message!, semanticsLabel: message),
            ),
          Expanded(
            child: data.when(
              loading: () => const AppLoadingView(),
              error: (error, _) => AppStateView(
                icon: Icons.wifi_off,
                title: 'Could not load alerts',
                message: error.toString(),
                actionLabel: 'Retry',
                onAction: () => ref.invalidate(notificationPageProvider(page)),
              ),
              data: (value) => RefreshIndicator(
                onRefresh: () async {
                  ref.invalidate(notificationPageProvider);
                  await ref.read(notificationPageProvider(page).future);
                },
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(16),
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            '${value.unread} unread',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                        ),
                        if (value.unread > 0 && value.items.isNotEmpty)
                          TextButton(
                            onPressed: busy
                                ? null
                                : () => perform(() async {
                                    await ref
                                        .read(notificationRepositoryProvider)
                                        .readThrough(value.items.first.id);
                                    ref.invalidate(notificationPageProvider);
                                  }),
                            child: const Text('Mark these and older as read'),
                          ),
                      ],
                    ),
                    if (value.items.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 60),
                        child: Column(
                          children: [
                            Icon(Icons.notifications_none, size: 42),
                            SizedBox(height: 12),
                            Text('You’re all caught up'),
                            SizedBox(height: 8),
                            Text(
                              'Booking updates and announcements will appear here.',
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      ),
                    for (final item in value.items)
                      Card(
                        color: item.readAt == null
                            ? AppColors.primarySoft
                            : null,
                        child: ListTile(
                          contentPadding: const EdgeInsets.all(16),
                          leading: Icon(
                            item.readAt == null
                                ? Icons.mark_email_unread_outlined
                                : Icons.notifications_none,
                          ),
                          title: Text(
                            item.title,
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item.body,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  notificationTime(item.createdAt),
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ],
                            ),
                          ),
                          onTap: () =>
                              context.push('/notifications/${item.id}'),
                        ),
                      ),
                    if (value.lastPage > 1)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
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
        ],
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
