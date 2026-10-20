import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';
import 'notification_repository.dart';

final notificationRepositoryProvider = Provider(
  (ref) => NotificationRepository(ref.watch(apiClientProvider)),
);
final notificationPageProvider = FutureProvider.autoDispose
    .family<NotificationPage, int>((ref, page) async {
      final account = ref.watch(
        authControllerProvider.select((state) => state.passenger?.id),
      );
      if (account == null ||
          ref.watch(mobileAppConfigProvider)?.features.notifications != true) {
        return const NotificationPage();
      }
      return ref.watch(notificationRepositoryProvider).load(page);
    });
final notificationDetailProvider = FutureProvider.autoDispose
    .family<AppNotification, int>((ref, id) async {
      final account = ref.watch(
        authControllerProvider.select((state) => state.passenger?.id),
      );
      if (account == null) {
        throw StateError('Sign in to read this notification.');
      }
      return ref.watch(notificationRepositoryProvider).find(id);
    });
// Keep the existing account-deletion invalidation hook compatible.
final notificationsProvider = notificationPageProvider;
