import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_shadows.dart';
import '../../core/widgets/app_widgets.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passenger = ref.watch(authControllerProvider).passenger;
    final bookings = ref.watch(bookingsProvider);
    final config = ref.watch(mobileAppConfigProvider);
    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(bookingsProvider.future),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            children: [
              Row(
                children: [
                  const AppLogo(height: 36),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Container(
                      height: 48,
                      padding: const EdgeInsets.fromLTRB(5, 4, 12, 4),
                      decoration: const BoxDecoration(
                        gradient: AppGradients.primary,
                        borderRadius: AppRadius.button,
                        boxShadow: AppShadows.card,
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 19,
                            backgroundColor: Colors.white.withValues(alpha: .2),
                            child: Text(
                              (passenger?.fullName ?? 'T').characters.first
                                  .toUpperCase(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'ASSALAM-O-ALAIKUM',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 8.5,
                                    letterSpacing: .5,
                                  ),
                                ),
                                Text(
                                  passenger?.fullName ?? 'Traveller',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 13,
                                    height: 1.25,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (config?.features.notifications == true) ...[
                    const SizedBox(width: 9),
                    Stack(
                      children: [
                        Material(
                          color: AppColors.surfaceAlt,
                          shape: const CircleBorder(),
                          child: IconButton(
                            onPressed: () => context.go('/notifications'),
                            icon: const Icon(Icons.notifications_none_rounded),
                          ),
                        ),
                        const Positioned(
                          right: 10,
                          top: 8,
                          child: CircleAvatar(
                            radius: 3.5,
                            backgroundColor: AppColors.gold,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 14),
              AppCard(
                padding: const EdgeInsets.all(16),
                onTap: () => context.go('/search'),
                child: Column(
                  children: [
                    Row(
                      children: [
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Where to today?',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              SizedBox(height: 2),
                              Text(
                                'Search buses across Pakistan',
                                style: TextStyle(
                                  color: AppColors.muted,
                                  fontSize: 11.5,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          width: 44,
                          height: 44,
                          decoration: const BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.search_rounded,
                            color: Colors.white,
                            size: 25,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 13),
                    const Row(
                      children: [
                        Expanded(
                          child: _RouteField(
                            label: 'FROM',
                            value: 'Choose origin',
                          ),
                        ),
                        SizedBox(width: 9),
                        Expanded(
                          child: _RouteField(
                            label: 'TO',
                            value: 'Choose destination',
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              bookings.when(
                loading: () => const _UpcomingSkeleton(),
                error: (error, _) => AppCard(
                  child: _InlineLoadFailure(
                    message: error.toString(),
                    onRetry: () => ref.invalidate(bookingsProvider),
                  ),
                ),
                data: (values) {
                  BookingSummary? upcoming;
                  for (final booking in values) {
                    if (booking.departureAt.isAfter(DateTime.now())) {
                      upcoming = booking;
                      break;
                    }
                  }
                  return upcoming == null
                      ? const SizedBox.shrink()
                      : _UpcomingTicket(booking: upcoming);
                },
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _QuickAction(
                      icon: Icons.confirmation_number_outlined,
                      label: 'My trips',
                      onTap: () => context.go('/tickets'),
                    ),
                  ),
                  const SizedBox(width: 9),
                  Expanded(
                    child: _QuickAction(
                      icon: Icons.person_outline_rounded,
                      label: 'Account',
                      onTap: () => context.go('/profile'),
                    ),
                  ),
                  if (config?.features.notifications == true) ...[
                    const SizedBox(width: 9),
                    Expanded(
                      child: _QuickAction(
                        icon: Icons.notifications_none_rounded,
                        label: 'Alerts',
                        onTap: () => context.go('/notifications'),
                      ),
                    ),
                  ],
                  const SizedBox(width: 9),
                  Expanded(
                    child: _QuickAction(
                      icon: Icons.headset_mic_outlined,
                      label: 'Support',
                      onTap: () => context.go('/profile'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              const _BusSeatGuideBanner(),
              const SizedBox(height: 16),
              const _FleetSection(),
            ],
          ),
        ),
      ),
    );
  }
}

class _RouteField extends StatelessWidget {
  const _RouteField({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
    height: 62,
    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
    decoration: const BoxDecoration(
      color: AppColors.surfaceAlt,
      borderRadius: AppRadius.field,
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 9,
            letterSpacing: .4,
            color: AppColors.muted,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          value,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
        ),
      ],
    ),
  );
}

class _UpcomingTicket extends StatelessWidget {
  const _UpcomingTicket({required this.booking});
  final BookingSummary booking;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: () => context.push('/tickets/${booking.id}'),
    borderRadius: AppRadius.card,
    child: Container(
      padding: const EdgeInsets.all(16),
      decoration: const BoxDecoration(
        gradient: AppGradients.ticket,
        borderRadius: AppRadius.card,
        boxShadow: AppShadows.elevated,
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: const BoxDecoration(
                  color: AppColors.gold,
                  borderRadius: AppRadius.button,
                ),
                child: const Text(
                  'NEXT TRIP',
                  style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  booking.reference,
                  style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 10,
                    letterSpacing: 1.2,
                  ),
                ),
              ),
              _StatusPill(booking.status),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _TicketStop(booking.originName, booking.departureAt),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 10),
                child: Icon(
                  Icons.directions_bus_filled_rounded,
                  color: AppColors.gold,
                  size: 24,
                ),
              ),
              Expanded(
                child: _TicketStop(booking.destinationName, null, right: true),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Divider(color: Colors.white.withValues(alpha: .22), height: 1),
          const SizedBox(height: 12),
          Row(
            children: [
              Text(
                'Seat ${booking.seats.join(', ')}',
                style: const TextStyle(color: Colors.white70, fontSize: 11),
              ),
              const Spacer(),
              Text(
                DateFormat('d MMM yyyy').format(booking.departureAt),
                style: const TextStyle(color: Colors.white70, fontSize: 11),
              ),
            ],
          ),
        ],
      ),
    ),
  );
}

class _TicketStop extends StatelessWidget {
  const _TicketStop(this.city, this.time, {this.right = false});
  final String city;
  final DateTime? time;
  final bool right;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: right
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text(
        city,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 18,
          height: 1.1,
          fontWeight: FontWeight.w800,
        ),
      ),
      if (time != null) ...[
        const SizedBox(height: 4),
        Text(
          DateFormat('h:mm a').format(time!),
          style: const TextStyle(color: Colors.white70, fontSize: 11),
        ),
      ],
    ],
  );
}

class _StatusPill extends StatelessWidget {
  const _StatusPill(this.status);
  final String status;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
    decoration: BoxDecoration(
      color: Colors.white.withValues(alpha: .16),
      borderRadius: AppRadius.button,
    ),
    child: Text(
      status,
      style: const TextStyle(
        color: Colors.white,
        fontSize: 9.5,
        fontWeight: FontWeight.w700,
      ),
    ),
  );
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: AppColors.surface,
    borderRadius: AppRadius.card,
    child: InkWell(
      onTap: onTap,
      borderRadius: AppRadius.card,
      child: Container(
        height: 88,
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 12),
        decoration: const BoxDecoration(
          borderRadius: AppRadius.card,
          boxShadow: AppShadows.card,
        ),
        child: Column(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: AppColors.primarySoft,
              foregroundColor: AppColors.primary,
              child: Icon(icon, size: 21),
            ),
            const SizedBox(height: 7),
            Text(
              label,
              maxLines: 1,
              style: const TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class _FleetCard extends StatelessWidget {
  const _FleetCard({required this.imageUrl, required this.label});
  final String imageUrl;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
    width: 222,
    margin: const EdgeInsets.only(right: 10),
    clipBehavior: Clip.antiAlias,
    decoration: const BoxDecoration(
      color: AppColors.surface,
      borderRadius: AppRadius.card,
      boxShadow: AppShadows.card,
    ),
    child: Stack(
      fit: StackFit.expand,
      children: [
        Image.network(
          imageUrl,
          fit: BoxFit.cover,
          errorBuilder: (_, _, _) => const ColoredBox(
            color: AppColors.primarySoft,
            child: Center(
              child: Icon(
                Icons.directions_bus_rounded,
                size: 42,
                color: AppColors.primary,
              ),
            ),
          ),
        ),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Colors.transparent, Color(0xB0000000)],
              stops: [.55, 1],
            ),
          ),
        ),
        Positioned(
          left: 13,
          right: 13,
          bottom: 11,
          child: Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    ),
  );
}

class _FleetSection extends ConsumerStatefulWidget {
  const _FleetSection();

  @override
  ConsumerState<_FleetSection> createState() => _FleetSectionState();
}

class _FleetSectionState extends ConsumerState<_FleetSection> {
  static const _cardExtent = 232.0;

  final ScrollController _controller = ScrollController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _showNextImage() {
    if (!_controller.hasClients) return;

    final position = _controller.position;
    final nextOffset = position.pixels + _cardExtent;
    final target = nextOffset >= position.maxScrollExtent
        ? (position.pixels >= position.maxScrollExtent - 4
              ? position.minScrollExtent
              : position.maxScrollExtent)
        : nextOffset;

    _controller.animateTo(
      target,
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeOutCubic,
    );
  }

  @override
  Widget build(BuildContext context) {
    final gallery = ref.watch(fleetGalleryProvider);
    return Column(
      children: [
        Align(
          alignment: Alignment.centerRight,
          child: TextButton.icon(
            onPressed: _showNextImage,
            icon: const Icon(Icons.swipe_rounded, size: 14),
            label: const Text('Swipe', style: TextStyle(fontSize: 11)),
          ),
        ),
        SizedBox(
          height: 158,
          child: gallery.when(
            loading: () => const Center(
              child: SizedBox(
                height: 26,
                width: 26,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
            error: (_, _) => Center(
              child: TextButton.icon(
                onPressed: () => ref.invalidate(fleetGalleryProvider),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Could not load fleet gallery'),
              ),
            ),
            data: (items) {
              if (items.isEmpty) {
                return const Center(
                  child: Text(
                    'Fleet photos will appear here when added by Kainat Travels.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: AppColors.muted, fontSize: 12),
                  ),
                );
              }
              return Scrollbar(
                controller: _controller,
                child: ListView.builder(
                  controller: _controller,
                  primary: false,
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(
                    parent: AlwaysScrollableScrollPhysics(),
                  ),
                  itemCount: items.length,
                  itemBuilder: (context, index) => _FleetCard(
                    imageUrl: items[index].imageUrl,
                    label: items[index].title,
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _BusSeatGuideBanner extends StatelessWidget {
  const _BusSeatGuideBanner();

  @override
  Widget build(BuildContext context) => AppCard(
    padding: EdgeInsets.zero,
    onTap: () => context.push('/fleet'),
    child: Container(
      width: double.infinity,
      padding: const EdgeInsets.all(15),
      decoration: const BoxDecoration(
        gradient: AppGradients.ticket,
        borderRadius: AppRadius.card,
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: .16),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.airline_seat_recline_extra_rounded,
              color: Colors.white,
              size: 27,
            ),
          ),
          const SizedBox(width: 13),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Bus & seat guide',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'Explore bus types, interiors and seat information',
                  style: TextStyle(color: Colors.white70, fontSize: 11.5),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: Colors.white),
        ],
      ),
    ),
  );
}

class _UpcomingSkeleton extends StatelessWidget {
  const _UpcomingSkeleton();

  @override
  Widget build(BuildContext context) => Container(
    height: 166,
    decoration: const BoxDecoration(
      color: AppColors.surfaceAlt,
      borderRadius: AppRadius.card,
    ),
  );
}

class _InlineLoadFailure extends StatelessWidget {
  const _InlineLoadFailure({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const Icon(Icons.cloud_off_outlined, color: AppColors.muted),
      const SizedBox(width: 10),
      Expanded(
        child: Text(message, maxLines: 2, overflow: TextOverflow.ellipsis),
      ),
      TextButton(onPressed: onRetry, child: const Text('Retry')),
    ],
  );
}
