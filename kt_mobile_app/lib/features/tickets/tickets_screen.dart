import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/app_widgets.dart';
import '../startup/application/startup_controller.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';
import '../travel/presentation/lovable_search_flow.dart';

class TicketsScreen extends ConsumerStatefulWidget {
  const TicketsScreen({super.key});

  @override
  ConsumerState<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends ConsumerState<TicketsScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final bookings = ref.watch(bookingsProvider);
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 70,
        title: const Text('My trips'),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, color: AppColors.border),
        ),
      ),
      body: Column(
        children: [
          Row(
            children: [
              _TripsTab(
                label: 'Upcoming',
                selected: _tab == 0,
                onTap: () => setState(() => _tab = 0),
              ),
              _TripsTab(
                label: 'Past',
                selected: _tab == 1,
                onTap: () => setState(() => _tab = 1),
              ),
              _TripsTab(
                label: 'Cancelled',
                selected: _tab == 2,
                onTap: () => setState(() => _tab = 2),
              ),
            ],
          ),
          const Divider(height: 1, color: AppColors.border),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(bookingsProvider.future),
              child: bookings.when(
                loading: () =>
                    const AppLoadingView(label: 'Loading your trips…'),
                error: (error, _) => ListView(
                  children: [
                    AppStateView(
                      icon: Icons.cloud_off_rounded,
                      title: 'Could not load trips',
                      message: error.toString(),
                      actionLabel: 'Try again',
                      onAction: () => ref.invalidate(bookingsProvider),
                    ),
                  ],
                ),
                data: (values) {
                  final now = DateTime.now();
                  final filtered = values.where((booking) {
                    final cancelled = booking.status.toLowerCase().contains(
                      'cancel',
                    );
                    return switch (_tab) {
                      0 => booking.departureAt.isAfter(now) && !cancelled,
                      1 => !booking.departureAt.isAfter(now) && !cancelled,
                      _ => cancelled,
                    };
                  }).toList();
                  if (filtered.isEmpty) {
                    return ListView(
                      children: [
                        AppStateView(
                          icon: Icons.confirmation_number_outlined,
                          title: _tab == 0
                              ? 'No upcoming trips'
                              : _tab == 1
                              ? 'No past trips'
                              : 'No cancelled trips',
                          message: _tab == 0
                              ? 'Book a bus and your ticket will appear here.'
                              : 'Trips in this category will appear here.',
                          actionLabel: _tab == 0 ? 'Book a trip' : null,
                          onAction: _tab == 0
                              ? () => context.go('/search')
                              : null,
                        ),
                      ],
                    );
                  }
                  return ListView.separated(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    itemCount: filtered.length,
                    separatorBuilder: (_, _) =>
                        const SizedBox(height: AppSpacing.sm),
                    itemBuilder: (context, index) =>
                        _BookingCard(booking: filtered[index]),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TripsTab extends StatelessWidget {
  const _TripsTab({
    required this.label,
    required this.selected,
    required this.onTap,
  });
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Expanded(
    child: InkWell(
      onTap: onTap,
      child: Container(
        height: 52,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          border: selected
              ? const Border(
                  bottom: BorderSide(color: AppColors.primary, width: 2.5),
                )
              : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? AppColors.primary : AppColors.muted,
            fontSize: 13,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
    ),
  );
}

class _BookingCard extends StatelessWidget {
  const _BookingCard({required this.booking});
  final BookingSummary booking;

  @override
  Widget build(BuildContext context) => AppCard(
    onTap: () => context.push('/tickets/${booking.id}'),
    child: Column(
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
              decoration: const BoxDecoration(
                color: AppColors.primarySoft,
                borderRadius: AppRadius.button,
              ),
              child: Text(
                booking.reference,
                style: const TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w800,
                  fontSize: 11,
                ),
              ),
            ),
            const Spacer(),
            Text(booking.status, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        Row(
          children: [
            const Icon(Icons.directions_bus_rounded, color: AppColors.primary),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${booking.originName} → ${booking.destinationName}',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  Text(
                    DateFormat(
                      'EEE, d MMM · h:mm a',
                    ).format(booking.departureAt),
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
          ],
        ),
        const Divider(),
        Row(
          children: [
            Text(
              'Seats ${booking.seats.join(', ')}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const Spacer(),
            Text(
              formatPkr(booking.total),
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ],
        ),
      ],
    ),
  );
}

class TicketDetailScreen extends ConsumerStatefulWidget {
  const TicketDetailScreen({required this.bookingId, super.key});
  final int bookingId;

  @override
  ConsumerState<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends ConsumerState<TicketDetailScreen> {
  late Future<BookingSummary> _request;

  @override
  void initState() {
    super.initState();
    _request = ref.read(travelRepositoryProvider).booking(widget.bookingId);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: const AppPageHeader(title: 'Ticket'),
    body: FutureBuilder<BookingSummary>(
      future: _request,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const AppLoadingView(label: 'Loading ticket…');
        }
        if (snapshot.hasError) {
          return AppStateView(
            icon: Icons.error_outline_rounded,
            title: 'Ticket unavailable',
            message: snapshot.error.toString(),
          );
        }
        final booking = snapshot.data!;
        final support = ref.watch(mobileAppConfigProvider);
        return ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            Container(
              decoration: const BoxDecoration(
                gradient: AppGradients.ticket,
                borderRadius: AppRadius.card,
              ),
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            const Text(
                              'E-TICKET',
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 10,
                                letterSpacing: 1.2,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              booking.reference,
                              style: const TextStyle(
                                color: AppColors.gold,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppSpacing.lg),
                        Row(
                          children: [
                            Expanded(
                              child: _ticketCity(booking.originName, false),
                            ),
                            const Icon(
                              Icons.directions_bus_rounded,
                              color: AppColors.gold,
                            ),
                            Expanded(
                              child: _ticketCity(booking.destinationName, true),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          DateFormat(
                            'EEEE, d MMMM yyyy · h:mm a',
                          ).format(booking.departureAt),
                          style: const TextStyle(color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    color: Colors.white,
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Column(
                      children: [
                        if (booking.status == 'confirmed' &&
                            booking.paymentStatus == 'paid' &&
                            booking.qrValue != null)
                          QrImageView(data: booking.qrValue!, size: 150)
                        else
                          const Icon(
                            Icons.qr_code_2_rounded,
                            size: 120,
                            color: AppColors.muted,
                          ),
                        const SizedBox(height: AppSpacing.xs),
                        Text(
                          booking.status == 'confirmed' &&
                                  booking.paymentStatus == 'paid'
                              ? 'Show this code at boarding'
                              : 'A boarding code is available after ticket confirmation.',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                        const Divider(height: AppSpacing.lg),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Seats ${booking.seats.join(', ')}'),
                            Text(
                              formatPkr(booking.total),
                              style: const TextStyle(
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppSpacing.xs),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Booking: ${booking.status}'),
                            Text('Payment: ${booking.paymentStatus}'),
                          ],
                        ),
                        if (booking.passengers.isNotEmpty) ...[
                          const Divider(height: AppSpacing.lg),
                          ...booking.passengers.map(
                            (passenger) => Padding(
                              padding: const EdgeInsets.only(
                                bottom: AppSpacing.xxs,
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.person_outline_rounded,
                                    size: 18,
                                  ),
                                  const SizedBox(width: AppSpacing.xs),
                                  Expanded(child: Text(passenger.fullName)),
                                  Text('Seat ${passenger.seatNumber}'),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
            if (booking.payment != null &&
                booking.payment!.status != 'paid') ...[
              const SizedBox(height: AppSpacing.sm),
              FilledButton(
                onPressed: () async {
                  await context.push('/payments/${booking.id}', extra: booking);
                  if (mounted) {
                    setState(
                      () => _request = ref
                          .read(travelRepositoryProvider)
                          .booking(widget.bookingId),
                    );
                  }
                },
                child: const Text('View payment status'),
              ),
            ],
            const SizedBox(height: AppSpacing.sm),
            AppCard(
              color: const Color(0xFFFFF8E8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.info_outline_rounded,
                    color: AppColors.goldDark,
                  ),
                  const SizedBox(width: AppSpacing.xs),
                  Expanded(
                    child: Text(
                      'For cancellation, refund, or rescheduling, please contact Kainat Travels Call Centre or the nearest branch.${support?.supportPhone == null ? '' : ' Call ${support!.supportPhone}.'}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    ),
  );

  Widget _ticketCity(String city, bool right) => Align(
    alignment: right ? Alignment.centerRight : Alignment.centerLeft,
    child: Text(
      city,
      style: const TextStyle(
        color: Colors.white,
        fontSize: 20,
        fontWeight: FontWeight.w800,
      ),
    ),
  );
}
