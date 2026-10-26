import 'dart:async';

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
import '../payments/domain/booking_payment_action.dart';
import '../startup/application/startup_controller.dart';
import '../auth/application/auth_controller.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';
import '../travel/presentation/lovable_search_flow.dart';
import 'trip_ticket_card.dart';

class TicketsScreen extends ConsumerStatefulWidget {
  const TicketsScreen({super.key});

  @override
  ConsumerState<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends ConsumerState<TicketsScreen>
    with WidgetsBindingObserver {
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) ref.invalidate(bookingsProvider);
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed &&
        ModalRoute.of(context)?.isCurrent == true) {
      ref.invalidate(bookingsProvider);
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bookings = ref.watch(
      bookingsProvider(ref.watch(passengerSessionProvider)),
    );
    final now = DateTime.now();
    final groups = <List<BookingSummary>>[[], [], []];
    for (final booking in bookings.value ?? <BookingSummary>[]) {
      final cancelled = booking.status.toLowerCase().contains('cancel');
      final group = cancelled
          ? 2
          : booking.departureAt.isAfter(now)
          ? 0
          : 1;
      groups[group].add(booking);
    }
    final filtered = groups[_tab];
    return Scaffold(
      backgroundColor: const Color(0xFFF0F5F1),
      body: SafeArea(
        bottom: false,
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 1100),
            child: LayoutBuilder(
              builder: (context, constraints) {
                final columns =
                    constraints.maxWidth >= 760 &&
                        MediaQuery.textScalerOf(context).scale(20) <= 24
                    ? 2
                    : 1;
                final padding = constraints.maxWidth >= 600 ? 28.0 : 20.0;
                return RefreshIndicator(
                  onRefresh: () => ref.refresh(
                    bookingsProvider(ref.read(passengerSessionProvider)).future,
                  ),
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverPadding(
                        padding: EdgeInsets.fromLTRB(padding, 20, padding, 0),
                        sliver: SliverToBoxAdapter(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const _TripsHeader(),
                              const SizedBox(height: 24),
                              Container(
                                padding: const EdgeInsets.all(5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFE2EBE4),
                                  borderRadius: BorderRadius.circular(18),
                                  border: Border.all(color: Colors.white),
                                ),
                                child: Row(
                                  children: [
                                    for (var i = 0; i < 3; i++)
                                      _TripsTab(
                                        label: const [
                                          'Upcoming',
                                          'Past',
                                          'Cancelled',
                                        ][i],
                                        count: bookings.hasValue
                                            ? groups[i].length
                                            : null,
                                        selected: _tab == i,
                                        onTap: () => setState(() => _tab = i),
                                      ),
                                  ],
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.fromLTRB(
                                  2,
                                  24,
                                  2,
                                  16,
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        const [
                                          'Your next journeys',
                                          'Your travel history',
                                          'Cancelled bookings',
                                        ][_tab],
                                        style: const TextStyle(
                                          color: AppColors.foreground,
                                          fontSize: 17,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    const Icon(
                                      Icons.confirmation_number_outlined,
                                      size: 20,
                                      color: AppColors.primaryDark,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      ...bookings.when(
                        loading: () => [
                          const SliverFillRemaining(
                            hasScrollBody: false,
                            child: AppLoadingView(label: 'Loading your trips…'),
                          ),
                        ],
                        error: (error, _) => [
                          SliverFillRemaining(
                            hasScrollBody: false,
                            child: AppStateView(
                              icon: Icons.cloud_off_rounded,
                              title: 'Could not load trips',
                              message: error.toString(),
                              actionLabel: 'Try again',
                              onAction: () => ref.invalidate(bookingsProvider),
                            ),
                          ),
                        ],
                        data: (_) {
                          if (filtered.isEmpty) {
                            return [
                              SliverFillRemaining(
                                hasScrollBody: false,
                                child: AppStateView(
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
                              ),
                            ];
                          }
                          return [
                            SliverPadding(
                              padding: EdgeInsets.fromLTRB(
                                padding,
                                0,
                                padding,
                                28,
                              ),
                              sliver: SliverList.builder(
                                itemCount: (filtered.length / columns).ceil(),
                                itemBuilder: (context, row) => Padding(
                                  padding: const EdgeInsets.only(bottom: 22),
                                  child: Row(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      for (
                                        var column = 0;
                                        column < columns;
                                        column++
                                      ) ...[
                                        if (column > 0)
                                          const SizedBox(width: 22),
                                        Expanded(
                                          child:
                                              row * columns + column <
                                                  filtered.length
                                              ? TripTicketCard(
                                                  booking:
                                                      filtered[row * columns +
                                                          column],
                                                  onTap: () async {
                                                    await context.push(
                                                      '/tickets/${filtered[row * columns + column].id}',
                                                    );
                                                    if (context.mounted) {
                                                      ref.invalidate(
                                                        bookingsProvider,
                                                      );
                                                    }
                                                  },
                                                )
                                              : const SizedBox.shrink(),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ];
                        },
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}

class _TripsTab extends StatelessWidget {
  const _TripsTab({
    required this.label,
    required this.count,
    required this.selected,
    required this.onTap,
  });
  final String label;
  final int? count;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Semantics(
      selected: selected,
      button: true,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: AnimatedContainer(
            duration: MediaQuery.disableAnimationsOf(context)
                ? Duration.zero
                : const Duration(milliseconds: 180),
            constraints: const BoxConstraints(minHeight: 52),
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 12),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: selected ? Colors.white : Colors.transparent,
              borderRadius: BorderRadius.circular(14),
              boxShadow: selected
                  ? const [
                      BoxShadow(
                        color: Color(0x18213F2A),
                        blurRadius: 8,
                        offset: Offset(0, 3),
                      ),
                    ]
                  : null,
            ),
            child: Wrap(
              alignment: WrapAlignment.center,
              crossAxisAlignment: WrapCrossAlignment.center,
              spacing: 5,
              runSpacing: 3,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    color: selected
                        ? AppColors.primaryDeep
                        : const Color(0xFF52675A),
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (count != null)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: selected
                          ? AppColors.primarySoft
                          : const Color(0xFFD2DFD5),
                      borderRadius: BorderRadius.circular(7),
                    ),
                    child: Text(
                      '$count',
                      style: const TextStyle(
                        color: AppColors.primaryDeep,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

class _TripsHeader extends StatelessWidget {
  const _TripsHeader();

  @override
  Widget build(BuildContext context) => Container(
    clipBehavior: Clip.antiAlias,
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(26),
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF208D50), Color(0xFF075C32), Color(0xFF063E29)],
      ),
      border: Border.all(color: const Color(0xFF76BD8B)),
      boxShadow: const [
        BoxShadow(color: Color(0xFF064027), offset: Offset(0, 5)),
        BoxShadow(
          color: Color(0x2414472B),
          blurRadius: 22,
          offset: Offset(0, 12),
        ),
      ],
    ),
    child: Stack(
      children: [
        Positioned(
          right: -24,
          top: -46,
          child: ExcludeSemantics(
            child: Container(
              width: 190,
              height: 190,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.07),
                  width: 26,
                ),
              ),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(24),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'YOUR TRAVEL COLLECTION',
                      style: TextStyle(
                        color: Color(0xFFBCE5C9),
                        fontSize: 10,
                        letterSpacing: 1.8,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'My trips',
                      style: Theme.of(context).textTheme.headlineMedium
                          ?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.7,
                          ),
                    ),
                    const SizedBox(height: 7),
                    const Text(
                      'Every journey, all in one place.',
                      style: TextStyle(color: Color(0xFFD1E9D9), fontSize: 12),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 14),
              const TripTicketEmblem(),
            ],
          ),
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

class _TicketDetailScreenState extends ConsumerState<TicketDetailScreen>
    with WidgetsBindingObserver {
  late Future<BookingSummary> _request;
  BookingSummary? _lastBooking;
  String? _paymentError;
  bool _loading = false;
  bool _openingPayment = false;
  Timer? _timer;
  String? _checkedExpiry;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _request = _fetchBooking();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      final payment = _lastBooking?.payment;
      if (payment?.status != 'pending' ||
          ModalRoute.of(context)?.isCurrent != true ||
          WidgetsBinding.instance.lifecycleState != AppLifecycleState.resumed) {
        return;
      }
      setState(() {});
      if (!_loading &&
          !payment!.expiresAt.isAfter(DateTime.now()) &&
          _checkedExpiry != payment.id) {
        _checkedExpiry = payment.id;
        _reload();
      }
    });
  }

  Future<BookingSummary> _fetchBooking() async {
    _loading = true;
    _paymentError = null;
    try {
      final repository = ref.read(travelRepositoryProvider);
      var booking = await repository.booking(widget.bookingId);
      if (booking.payment != null &&
          ['pending', 'expired'].contains(booking.payment!.status)) {
        try {
          booking = await repository.refreshPayment(widget.bookingId);
        } catch (error) {
          // Keep the booking visible, but require a successful status check
          // before offering checkout or a replacement booking.
          _paymentError = error.toString();
        }
      }
      if (mounted) {
        _lastBooking = booking;
        ref.invalidate(bookingsProvider);
      }
      return booking;
    } finally {
      _loading = false;
    }
  }

  Future<void> _reload() async {
    if (_loading) return;
    setState(() {
      _request = _fetchBooking();
    });
    // FutureBuilder displays failures and provides a retry action.
    try {
      await _request;
    } catch (_) {}
  }

  Future<void> _openPayment(
    BookingSummary booking, {
    bool checkout = false,
  }) async {
    if (_openingPayment) return;
    _openingPayment = true;
    try {
      await context.push(
        '/payments/${booking.id}${checkout ? '?checkout=1' : ''}',
        extra: booking,
      );
      if (mounted) await _reload();
    } finally {
      _openingPayment = false;
    }
  }

  void _bookAgain() {
    ref.read(bookingFlowProvider.notifier).reset();
    context.go('/search');
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed &&
        ModalRoute.of(context)?.isCurrent == true) {
      _reload();
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
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
            actionLabel: 'Try again',
            onAction: _reload,
          );
        }
        final booking = snapshot.data!;
        final action = _paymentError != null
            ? BookingPaymentAction.checkPaymentStatus
            : bookingPaymentAction(booking);
        final support = ref.watch(mobileAppConfigProvider);
        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
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
                                child: _ticketCity(
                                  booking.destinationName,
                                  true,
                                ),
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
                              Expanded(
                                child: Text(
                                  'Seats ${booking.seats.join(', ')}',
                                ),
                              ),
                              const SizedBox(width: AppSpacing.xs),
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
                              Expanded(
                                child: Text('Booking: ${booking.status}'),
                              ),
                              const SizedBox(width: AppSpacing.xs),
                              Expanded(
                                child: Text(
                                  'Payment: ${booking.paymentStatus}',
                                  textAlign: TextAlign.right,
                                ),
                              ),
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
              if (_paymentError != null) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Could not verify payment. $_paymentError',
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ],
              if (action == BookingPaymentAction.completePayment) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(paymentTimeRemaining(booking.payment!.expiresAt)),
              ],
              if (action != BookingPaymentAction.none &&
                  action != BookingPaymentAction.viewTicket) ...[
                const SizedBox(height: AppSpacing.sm),
                FilledButton(
                  onPressed: action == BookingPaymentAction.bookAgain
                      ? _bookAgain
                      : () => _openPayment(
                          booking,
                          checkout:
                              action == BookingPaymentAction.completePayment,
                        ),
                  child: Text(action.label),
                ),
                if (action == BookingPaymentAction.bookAgain) ...[
                  const Text(
                    'Your reservation expired. Select seats again at the current fare. If you were charged, check payment status or contact support first.',
                  ),
                  TextButton(
                    onPressed: () => _openPayment(booking),
                    child: const Text('View payment status'),
                  ),
                ],
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
          ),
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
