import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/app_widgets.dart';
import '../startup/application/startup_controller.dart';

final notificationsProvider = FutureProvider<List<Map<String, dynamic>>>((
  ref,
) async {
  final raw = await ref.watch(apiClientProvider).get('/notifications');
  if (raw is! List) {
    throw const FormatException('Expected a notification list.');
  }
  return raw.map((item) => (item as Map).cast<String, dynamic>()).toList();
});

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final config = ref.watch(mobileAppConfigProvider);
    if (config?.features.notifications != true) {
      return const Scaffold(
        appBar: AppPageHeader(title: 'Notifications'),
        body: AppStateView(
          icon: Icons.notifications_none_rounded,
          title: 'Notifications are not enabled',
          message:
              'Booking updates will appear here when this service is available.',
        ),
      );
    }
    final notifications = ref.watch(notificationsProvider);
    return Scaffold(
      appBar: const AppPageHeader(title: 'Notifications'),
      body: notifications.when(
        loading: () => const AppLoadingView(),
        error: (error, _) => AppStateView(
          icon: Icons.notifications_off_outlined,
          title: 'Notifications unavailable',
          message: error.toString(),
          actionLabel: 'Try again',
          onAction: () => ref.invalidate(notificationsProvider),
        ),
        data: (values) => values.isEmpty
            ? const AppStateView(
                icon: Icons.notifications_none_rounded,
                title: 'You’re all caught up',
                message: 'Booking and service updates will appear here.',
              )
            : ListView.separated(
                padding: const EdgeInsets.all(AppSpacing.md),
                itemCount: values.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  final item = values[index];
                  final unread = item['read_at'] == null;
                  return AppCard(
                    padding: EdgeInsets.zero,
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        borderRadius: const BorderRadius.all(
                          Radius.circular(22),
                        ),
                        border: unread
                            ? Border.all(
                                color: AppColors.primary.withValues(alpha: .35),
                              )
                            : null,
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const CircleAvatar(
                            backgroundColor: AppColors.primarySoft,
                            foregroundColor: AppColors.primary,
                            child: Icon(Icons.notifications_none_rounded),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        item['title']?.toString() ?? 'Update',
                                        style: const TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ),
                                    if (unread)
                                      const CircleAvatar(
                                        radius: 4,
                                        backgroundColor: AppColors.primary,
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  item['body']?.toString() ?? '',
                                  style: const TextStyle(
                                    color: AppColors.muted,
                                    fontSize: 12,
                                    height: 1.35,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
