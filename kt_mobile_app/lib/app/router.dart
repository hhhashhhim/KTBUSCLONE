import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/presentation/auth_screens.dart';
import '../features/auth/domain/passenger.dart';
import '../features/home/home_screen.dart';
import '../features/payments/presentation/payment_screen.dart';
import '../features/home/fleet_gallery_screen.dart';
import '../features/notifications/notifications_screen.dart';
import '../features/profile/profile_screen.dart';
import '../features/profile/delete_account_screen.dart';
import '../features/profile/saved_passengers_screen.dart';
import '../features/profile/wallet_screen.dart';
import '../features/shell/app_shell.dart';
import '../features/startup/presentation/startup_screens.dart';
import '../features/startup/application/startup_controller.dart';
import '../features/tickets/tickets_screen.dart';
import '../features/travel/domain/travel_models.dart';
import '../features/travel/presentation/lovable_search_flow.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>();
final appAccessDecision = AppAccessDecisionNotifier();

class AppAccessDecisionNotifier extends ChangeNotifier {
  StartupDecision? _value;

  StartupDecision? get value => _value;

  set value(StartupDecision? decision) {
    final previousGate = _gate(_value);
    _value = decision;
    // Refreshing an unchanged gate can discard GoRouter's pushed routes and
    // their journey data. Config consumers still receive every policy refresh.
    if (previousGate != _gate(decision)) notifyListeners();
  }

  StartupDestination? _gate(StartupDecision? decision) =>
      switch (decision?.destination) {
        StartupDestination.forceUpdate => StartupDestination.forceUpdate,
        StartupDestination.maintenance => StartupDestination.maintenance,
        _ => null,
      };
}

String? appAccessRedirect(String path, StartupDecision? decision) {
  final target = switch (decision?.destination) {
    StartupDestination.forceUpdate => '/update-required',
    StartupDestination.maintenance => '/maintenance',
    _ => null,
  };
  if (target != null) return path == target ? null : target;
  if (decision != null &&
      (path == '/update-required' || path == '/maintenance')) {
    // Restore authentication through the normal startup flow after unblocking.
    return '/';
  }
  return null;
}

final appRouter = GoRouter(
  navigatorKey: rootNavigatorKey,
  initialLocation: '/',
  refreshListenable: appAccessDecision,
  redirect: (_, state) =>
      appAccessRedirect(state.uri.path, appAccessDecision.value),
  routes: [
    GoRoute(path: '/', builder: (_, _) => const SplashScreen()),
    GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
    GoRoute(path: '/register', builder: (_, _) => const RegisterScreen()),
    GoRoute(
      path: '/forgot-password',
      builder: (_, _) => const ForgotPasswordScreen(),
    ),
    GoRoute(path: '/verify-otp', builder: (_, _) => const OtpScreen()),
    GoRoute(
      path: '/reset-otp',
      builder: (_, state) => state.extra is String
          ? ResetOtpScreen(mobile: state.extra! as String)
          : const ForgotPasswordScreen(),
    ),
    GoRoute(
      path: '/reset-password',
      builder: (_, state) => state.extra is PasswordResetSession
          ? ResetPasswordScreen(session: state.extra! as PasswordResetSession)
          : const ForgotPasswordScreen(),
    ),
    GoRoute(path: '/maintenance', builder: (_, _) => const MaintenanceScreen()),
    GoRoute(
      path: '/update-required',
      builder: (_, _) => const ForceUpdateScreen(),
    ),
    GoRoute(
      path: '/search/results',
      builder: (_, state) {
        final query = state.extra;
        return AppShell(
          child: query is SearchQuery
              ? ScheduleResultsScreen(query: query)
              : const SearchScreen(),
        );
      },
    ),
    GoRoute(
      path: '/search/seats',
      builder: (_, _) => const AppShell(child: SeatSelectionScreen()),
    ),
    GoRoute(
      path: '/search/passengers',
      builder: (_, _) => const AppShell(child: PassengerDetailsScreen()),
    ),
    GoRoute(
      path: '/search/checkout',
      builder: (_, _) => const AppShell(child: CheckoutScreen()),
    ),
    GoRoute(
      path: '/payments/:bookingId',
      builder: (_, state) => AppShell(
        child: PaymentScreen(
          bookingId: int.tryParse(state.pathParameters['bookingId'] ?? '') ?? 0,
          initialBooking: state.extra is BookingSummary
              ? state.extra as BookingSummary
              : null,
        ),
      ),
    ),
    GoRoute(
      path: '/booking-confirmed',
      builder: (_, state) {
        final booking = state.extra;
        return AppShell(
          child: booking is BookingSummary
              ? BookingConfirmationScreen(booking: booking)
              : const SearchScreen(),
        );
      },
    ),
    GoRoute(
      path: '/tickets/:bookingId',
      builder: (_, state) => AppShell(
        child: TicketDetailScreen(
          bookingId: int.tryParse(state.pathParameters['bookingId'] ?? '') ?? 0,
        ),
      ),
    ),
    GoRoute(
      path: '/notifications',
      builder: (_, _) => const AppShell(child: NotificationsScreen()),
    ),
    GoRoute(
      path: '/profile/edit',
      builder: (_, _) => const AppShell(child: EditProfileScreen()),
    ),
    GoRoute(
      path: '/profile/delete-account',
      builder: (_, _) => const DeleteAccountScreen(),
    ),
    GoRoute(
      path: '/profile/saved-passengers',
      builder: (_, _) => const AppShell(child: SavedPassengersScreen()),
    ),
    GoRoute(
      path: '/wallet',
      builder: (_, _) => const AppShell(child: WalletScreen()),
    ),
    GoRoute(
      path: '/fleet',
      builder: (_, _) => const AppShell(child: FleetGalleryScreen()),
    ),
    ShellRoute(
      builder: (_, _, child) => AppShell(child: child),
      routes: [
        GoRoute(path: '/home', builder: (_, _) => const HomeScreen()),
        GoRoute(path: '/search', builder: (_, _) => const SearchScreen()),
        GoRoute(path: '/tickets', builder: (_, _) => const TicketsScreen()),
        GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen()),
      ],
    ),
  ],
);
