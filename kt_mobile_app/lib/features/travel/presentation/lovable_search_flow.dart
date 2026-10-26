import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../auth/application/auth_controller.dart';
import '../../payments/presentation/payment_method_selector.dart';
import '../application/travel_providers.dart';
import '../domain/travel_models.dart';
import 'checkout_widgets.dart';
import 'journey_search_widgets.dart';
import 'passenger_details_widgets.dart';
import 'schedule_result_card.dart';
import 'schedule_results_widgets.dart';

String formatPkr(num amount) => NumberFormat.currency(
  locale: 'en_PK',
  symbol: 'Rs. ',
  decimalDigits: amount % 1 == 0 ? 0 : 2,
).format(amount);

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  CityOption? _origin;
  CityOption? _destination;
  DateTime _date = DateTime.now();

  @override
  Widget build(BuildContext context) {
    final cities = ref.watch(citiesProvider);
    final destinations = _origin == null
        ? const AsyncValue<List<CityOption>>.data([])
        : ref.watch(destinationsProvider(_origin!.id));
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark,
      child: Scaffold(
        body: DecoratedBox(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color(0xFFE8F2EB), AppColors.background],
            ),
          ),
          child: SingleChildScrollView(
            child: Column(
              children: [
                JourneySearchHeader(
                  onBack: () => context.go('/home'),
                  tripTypes: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF096639),
                      borderRadius: AppRadius.button,
                      border: Border.all(color: const Color(0xFF3C9760)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Flexible(
                          child: _TripTypePill(
                            label: 'One way',
                            selected: true,
                          ),
                        ),
                        Flexible(
                          child: _TripTypePill(
                            label: 'Round trip',
                            onTap: () => _message(
                              'Round trips are not available from the mobile API yet.',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Transform.translate(
                    offset: const Offset(0, -26),
                    child: Column(
                      children: [
                        JourneySearchCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Padding(
                                padding: EdgeInsets.only(bottom: 18),
                                child: Row(
                                  children: [
                                    Icon(
                                      Icons.route_rounded,
                                      color: AppColors.primary,
                                      size: 20,
                                    ),
                                    SizedBox(width: 9),
                                    Expanded(
                                      child: Text(
                                        'Your travel details',
                                        style: TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              cities.when(
                                loading: () => const LinearProgressIndicator(),
                                error: (error, _) => _InlineLoadError(
                                  error: error,
                                  onRetry: () => ref.invalidate(citiesProvider),
                                ),
                                data: (values) => _CityField(
                                  label: 'FROM',
                                  iconColor: AppColors.primary,
                                  value: _origin,
                                  values: values,
                                  onChanged: (city) => setState(() {
                                    _origin = city;
                                    _destination = null;
                                  }),
                                ),
                              ),
                              const SizedBox(height: 14),
                              destinations.when(
                                loading: () => const LinearProgressIndicator(),
                                error: (error, _) => _InlineLoadError(
                                  error: error,
                                  onRetry: () {
                                    if (_origin != null) {
                                      ref.invalidate(
                                        destinationsProvider(_origin!.id),
                                      );
                                    }
                                  },
                                ),
                                data: (values) => _CityField(
                                  label: 'TO',
                                  iconColor: AppColors.goldDark,
                                  value: _destination,
                                  values: values,
                                  onChanged: _origin == null
                                      ? null
                                      : (city) =>
                                            setState(() => _destination = city),
                                ),
                              ),
                              const SizedBox(height: 14),
                              _DateTile(date: _date, onTap: _pickDate),
                              const SizedBox(height: 24),
                              JourneySearchButton(onPressed: _search),
                            ],
                          ),
                        ),
                        const Padding(
                          padding: EdgeInsets.fromLTRB(16, 28, 16, 0),
                          child: Text(
                            'Choose your route, then find your bus\nand your favourite seat.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: AppColors.muted,
                              fontSize: 12,
                              height: 1.6,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _pickDate() async {
    final value = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );
    if (value != null) setState(() => _date = value);
  }

  void _search() {
    if (_origin == null || _destination == null) {
      _message('Choose both origin and destination.');
      return;
    }
    if (_origin!.id == _destination!.id) {
      _message('Origin and destination must be different.');
      return;
    }
    context.push(
      '/search/results',
      extra: SearchQuery(
        origin: _origin!,
        destination: _destination!,
        date: _date,
      ),
    );
  }

  void _message(String value) => ScaffoldMessenger.of(
    context,
  ).showSnackBar(SnackBar(content: Text(value)));
}

class _TripTypePill extends StatelessWidget {
  const _TripTypePill({required this.label, this.selected = false, this.onTap});
  final String label;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) => Semantics(
    selected: selected,
    button: true,
    child: Container(
      decoration: BoxDecoration(
        borderRadius: AppRadius.button,
        boxShadow: selected
            ? const [
                BoxShadow(
                  color: Color(0x55002816),
                  offset: Offset(0, 2),
                  blurRadius: 4,
                ),
              ]
            : null,
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: AppRadius.button,
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            gradient: selected
                ? const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Colors.white, Color(0xFFE2F0E6)],
                  )
                : null,
            borderRadius: AppRadius.button,
          ),
          child: InkWell(
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
              child: Text(
                label,
                style: TextStyle(
                  color: selected ? AppColors.primaryDeep : Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

class _CityField extends StatelessWidget {
  const _CityField({
    required this.label,
    required this.iconColor,
    required this.value,
    required this.values,
    required this.onChanged,
  });
  final String label;
  final Color iconColor;
  final CityOption? value;
  final List<CityOption> values;
  final ValueChanged<CityOption?>? onChanged;

  @override
  Widget build(BuildContext context) => JourneyField(
    label: label,
    value: value?.name ?? 'Choose city',
    icon: label == 'FROM'
        ? Icons.my_location_rounded
        : Icons.location_on_rounded,
    gold: iconColor == AppColors.goldDark,
    enabled: onChanged != null,
    onTap: () => _chooseCity(context),
  );

  Future<void> _chooseCity(BuildContext context) async {
    final selected = await showModalBottomSheet<CityOption>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _CityPickerSheet(
        title: label == 'FROM' ? 'Choose departure city' : 'Choose destination',
        values: values,
        selected: value,
      ),
    );
    if (selected != null) onChanged?.call(selected);
  }
}

class _CityPickerSheet extends StatefulWidget {
  const _CityPickerSheet({
    required this.title,
    required this.values,
    required this.selected,
  });

  final String title;
  final List<CityOption> values;
  final CityOption? selected;

  @override
  State<_CityPickerSheet> createState() => _CityPickerSheetState();
}

class _CityPickerSheetState extends State<_CityPickerSheet> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  String _query = '';

  List<CityOption> get _filtered {
    final query = _query.trim().toLowerCase();
    if (query.isEmpty) return widget.values;
    return widget.values
        .where((city) => city.name.toLowerCase().contains(query))
        .toList();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final media = MediaQuery.of(context);
    final availableHeight =
        media.size.height - media.padding.top - media.viewInsets.bottom;
    final keyboardVisible = media.viewInsets.bottom > 0;
    final results = _filtered;

    return Align(
      alignment: Alignment.bottomCenter,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        height: availableHeight * (keyboardVisible ? .96 : .78),
        decoration: const BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 38,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 8, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      widget.title,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
              child: TextField(
                controller: _searchController,
                autofocus: true,
                textInputAction: TextInputAction.search,
                decoration: const InputDecoration(
                  hintText: 'Type a city name',
                  prefixIcon: Icon(Icons.search_rounded),
                  suffixIcon: Icon(Icons.location_city_outlined),
                ),
                onChanged: (value) {
                  setState(() => _query = value);
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    if (_scrollController.hasClients) {
                      _scrollController.jumpTo(0);
                    }
                  });
                },
              ),
            ),
            Expanded(
              child: results.isEmpty
                  ? const Center(
                      child: Text(
                        'No matching cities',
                        style: TextStyle(color: AppColors.muted),
                      ),
                    )
                  : Scrollbar(
                      controller: _scrollController,
                      thumbVisibility: true,
                      child: ListView.separated(
                        controller: _scrollController,
                        keyboardDismissBehavior:
                            ScrollViewKeyboardDismissBehavior.onDrag,
                        padding: const EdgeInsets.fromLTRB(8, 0, 8, 16),
                        itemCount: results.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (context, index) {
                          final city = results[index];
                          final selected = city.id == widget.selected?.id;
                          return ListTile(
                            title: Text(
                              city.name,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            trailing: selected
                                ? const Icon(
                                    Icons.check_circle_rounded,
                                    color: AppColors.primary,
                                  )
                                : null,
                            onTap: () => Navigator.pop(context, city),
                          );
                        },
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DateTile extends StatelessWidget {
  const _DateTile({required this.date, required this.onTap});
  final DateTime date;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => JourneyField(
    label: 'DEPARTURE',
    icon: Icons.calendar_month_rounded,
    value: DateFormat('EEE, d MMM yyyy').format(date),
    isDate: true,
    onTap: onTap,
  );
}

class ScheduleResultsScreen extends ConsumerStatefulWidget {
  const ScheduleResultsScreen({required this.query, super.key});
  final SearchQuery query;

  @override
  ConsumerState<ScheduleResultsScreen> createState() =>
      _ScheduleResultsScreenState();
}

class _ScheduleResultsScreenState extends ConsumerState<ScheduleResultsScreen> {
  bool _sortByPrice = false;

  @override
  Widget build(BuildContext context) {
    final query = widget.query;
    final schedules = ref.watch(schedulesProvider(query));
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: const Color(0xFFEFF5F0),
        body: Column(
          children: [
            ScheduleResultsHeader(query: query),
            ScheduleSortBar(
              sortByPrice: _sortByPrice,
              onChanged: (value) => setState(() => _sortByPrice = value),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => ref.refresh(schedulesProvider(query).future),
                child: schedules.when(
                  loading: () =>
                      const AppLoadingView(label: 'Finding available buses…'),
                  error: (error, _) => ListView(
                    children: [
                      AppStateView(
                        icon: Icons.cloud_off_rounded,
                        title: 'Could not load schedules',
                        message: error.toString(),
                        actionLabel: 'Try again',
                        onAction: () =>
                            ref.invalidate(schedulesProvider(query)),
                      ),
                    ],
                  ),
                  data: (values) {
                    final sorted = [...values]
                      ..sort(
                        (a, b) => _sortByPrice
                            ? a.minimumFare.compareTo(b.minimumFare)
                            : a.departureAt.compareTo(b.departureAt),
                      );
                    if (sorted.isEmpty) {
                      return ListView(
                        children: const [
                          AppStateView(
                            icon: Icons.event_busy_rounded,
                            title: 'No buses found',
                            message: 'Try another travel date or route.',
                          ),
                        ],
                      );
                    }
                    return ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(18, 14, 18, 28),
                      itemCount: sorted.length + 1,
                      separatorBuilder: (_, _) => const SizedBox(height: 20),
                      itemBuilder: (context, index) {
                        if (index == 0) {
                          return Row(
                            children: [
                              const Expanded(
                                child: Text(
                                  'Available buses',
                                  style: TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: -.3,
                                  ),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 5,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFE1ECE3),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: Colors.white),
                                ),
                                child: Text(
                                  '${sorted.length} ${sorted.length == 1 ? 'departure' : 'departures'}',
                                  style: const TextStyle(
                                    color: AppColors.primaryDeep,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          );
                        }
                        final schedule = sorted[index - 1];
                        return ScheduleResultCard(
                          key: ValueKey(schedule.scheduleDetailId),
                          schedule: schedule,
                          onTap: () {
                            ref
                                .read(bookingFlowProvider.notifier)
                                .begin(query, schedule);
                            context.push('/search/seats');
                          },
                        );
                      },
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

const _maleBookedSeatColor = Color(0xFFD7E8FF);
const _femaleBookedSeatColor = Color(0xFFFFD8E8);
const _maleSeatAccent = Color(0xFF3269A8);
const _femaleSeatAccent = Color(0xFFA6416A);

class SeatSelectionScreen extends ConsumerStatefulWidget {
  const SeatSelectionScreen({super.key});

  @override
  ConsumerState<SeatSelectionScreen> createState() =>
      _SeatSelectionScreenState();
}

class _SeatSelectionScreenState extends ConsumerState<SeatSelectionScreen> {
  Future<SeatLayoutResult>? _request;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final flow = ref.read(bookingFlowProvider);
    if (_request == null && flow.query != null && flow.schedule != null) {
      _request = ref
          .read(travelRepositoryProvider)
          .seats(flow.schedule!, flow.query!);
    }
  }

  @override
  Widget build(BuildContext context) {
    final flow = ref.watch(bookingFlowProvider);
    if (_request == null || flow.schedule == null) return const _MissingFlow();
    final schedule = flow.schedule!;
    return Scaffold(
      appBar: AppPageHeader(
        title: 'Select seats',
        subtitle:
            '${schedule.busClassName} · ${DateFormat('h:mm a').format(schedule.departureAt)}',
      ),
      body: FutureBuilder<SeatLayoutResult>(
        future: _request,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppLoadingView(
              label: 'Loading live seat availability…',
            );
          }
          if (snapshot.hasError) {
            return AppStateView(
              icon: Icons.event_seat_outlined,
              title: 'Seat map unavailable',
              message: snapshot.error.toString(),
              actionLabel: 'Try again',
              onAction: () => setState(() {
                _request = ref
                    .read(travelRepositoryProvider)
                    .seats(flow.schedule!, flow.query!);
              }),
            );
          }
          final result = snapshot.data!;
          if (flow.availableSeats.isEmpty) {
            WidgetsBinding.instance.addPostFrameCallback(
              (_) => ref
                  .read(bookingFlowProvider.notifier)
                  .setSeatLayout(result.seats),
            );
          }
          return Column(
            children: [
              const _SeatLegend(),
              Expanded(
                child: _SeatGrid(
                  seats: result.seats,
                  selected: flow.selectedSeats,
                  onTap: (seat) {
                    final changed = ref
                        .read(bookingFlowProvider.notifier)
                        .toggleSeat(seat, result.maximumSelectable);
                    if (!changed && seat.selectable) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            'Select up to ${result.maximumSelectable} seats.',
                          ),
                        ),
                      );
                    }
                  },
                ),
              ),
              AppStickyAction(
                // Preserve seat touch targets on phones with larger insets by
                // keeping the footer's decorative spacing compact.
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
                child: _SeatFooter(flow: flow),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _SeatLegend extends StatelessWidget {
  const _SeatLegend();

  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    child: Row(
      children: [
        Expanded(
          child: _Legend(
            color: AppColors.surface,
            label: 'Available',
            border: true,
          ),
        ),
        SizedBox(width: 6),
        Expanded(
          child: _Legend(color: AppColors.primary, label: 'Selected'),
        ),
        SizedBox(width: 6),
        Expanded(
          child: _Legend(
            color: _maleBookedSeatColor,
            label: 'Male',
            detail: '(booked)',
          ),
        ),
        SizedBox(width: 6),
        Expanded(
          child: _Legend(
            color: _femaleBookedSeatColor,
            label: 'Female',
            detail: '(booked)',
          ),
        ),
      ],
    ),
  );
}

class _Legend extends StatelessWidget {
  const _Legend({
    required this.color,
    required this.label,
    this.detail,
    this.border = false,
  });
  final Color color;
  final String label;
  final String? detail;
  final bool border;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisAlignment: MainAxisAlignment.center,
    children: [
      Container(
        width: 12,
        height: 16,
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(4),
          border: border ? Border.all(color: AppColors.border) : null,
        ),
      ),
      const SizedBox(width: 5),
      Flexible(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: const TextStyle(
                fontSize: 10,
                height: 1,
                fontWeight: FontWeight.w700,
              ),
            ),
            if (detail != null) ...[
              Text(
                detail!,
                style: const TextStyle(
                  color: AppColors.muted,
                  fontSize: 8.5,
                  height: 1,
                ),
              ),
            ],
          ],
        ),
      ),
    ],
  );
}

class _SeatGrid extends StatelessWidget {
  const _SeatGrid({
    required this.seats,
    required this.selected,
    required this.onTap,
  });
  final List<TravelSeat> seats;
  final List<TravelSeat> selected;
  final ValueChanged<TravelSeat> onTap;

  @override
  Widget build(BuildContext context) {
    if (seats.isEmpty) {
      return const AppStateView(
        icon: Icons.event_seat_outlined,
        title: 'No seat layout',
        message: 'This schedule does not have a seat layout configured.',
      );
    }
    final rows = <int, List<TravelSeat>>{};
    for (final seat in seats) {
      rows.putIfAbsent(seat.row, () => []).add(seat);
    }
    final rowKeys = rows.keys.toList()..sort();
    final maxColumn = seats
        .map((seat) => seat.column)
        .reduce((a, b) => a > b ? a : b);
    final lookup = {
      for (final seat in seats) '${seat.row}:${seat.column}': seat,
    };
    final selectedNumbers = selected.map((seat) => seat.number).toSet();
    final textScaler = MediaQuery.textScalerOf(context);
    final headerHeight = math.max(16.0, textScaler.scale(11) * 1.2);
    final minimumSeatHeight = math.max(36.0, textScaler.scale(12) * 1.2 + 16);
    final preferredSeatHeight = math.max(44.0, minimumSeatHeight);

    return LayoutBuilder(
      builder: (context, constraints) {
        // Size against the actual space between the legend and sticky footer.
        // Keep text at its requested scale and scroll instead of shrinking
        // seats below their minimum usable size.
        const outerPadding = EdgeInsets.symmetric(horizontal: 12, vertical: 4);
        const cardPadding = EdgeInsets.symmetric(horizontal: 12, vertical: 6);
        const headerGap = 4.0;
        const borderWidth = 2.0;
        final heightForRows =
            constraints.maxHeight -
            outerPadding.vertical -
            cardPadding.vertical -
            borderWidth -
            headerHeight -
            headerGap;
        final rowGap = rowKeys.length == 1
            ? 0.0
            : ((heightForRows - rowKeys.length * preferredSeatHeight) /
                      (rowKeys.length - 1))
                  .clamp(0.0, 6.0);
        final seatHeight =
            ((heightForRows - rowGap * (rowKeys.length - 1)) / rowKeys.length)
                .clamp(minimumSeatHeight, preferredSeatHeight);
        final columnCount = maxColumn + 1;
        const columnGap = 7.0;
        final aisleGap = maxColumn >= 3 ? 16.0 : 0.0;
        final cardWidth = math.min(
          330.0,
          constraints.maxWidth - outerPadding.horizontal,
        );
        final minimumSeatWidth = math.max(44.0, textScaler.scale(12) * 2 + 16);
        final seatWidth =
            ((cardWidth -
                        cardPadding.horizontal -
                        borderWidth -
                        aisleGap -
                        columnGap * maxColumn) /
                    columnCount)
                .clamp(minimumSeatWidth, math.max(48.0, minimumSeatWidth))
                .toDouble();
        final contentWidth = math.max(
          cardWidth,
          seatWidth * columnCount +
              columnGap * maxColumn +
              aisleGap +
              cardPadding.horizontal +
              borderWidth,
        );

        return SingleChildScrollView(
          key: const ValueKey('seat-map-vertical-scroll'),
          padding: outerPadding,
          child: SingleChildScrollView(
            key: const ValueKey('seat-map-horizontal-scroll'),
            scrollDirection: Axis.horizontal,
            child: SizedBox(
              width: math.max(
                constraints.maxWidth - outerPadding.horizontal,
                contentWidth,
              ),
              child: Center(
                child: Container(
                  width: contentWidth,
                  padding: cardPadding,
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: AppColors.border),
                    boxShadow: const [
                      BoxShadow(
                        color: Color(0x1220442E),
                        blurRadius: 18,
                        offset: Offset(0, 5),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SizedBox(
                        height: headerHeight,
                        child: const Row(
                          children: [
                            Text(
                              'Front',
                              style: TextStyle(
                                color: AppColors.muted,
                                fontSize: 11,
                                height: 1.2,
                              ),
                            ),
                            Spacer(),
                            Icon(
                              Icons.directions_bus_rounded,
                              size: 16,
                              color: AppColors.muted,
                            ),
                            SizedBox(width: 4),
                            Text(
                              'Driver',
                              style: TextStyle(
                                color: AppColors.muted,
                                fontSize: 11,
                                height: 1.2,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: headerGap),
                      for (var index = 0; index < rowKeys.length; index++) ...[
                        if (index > 0) SizedBox(height: rowGap),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            for (
                              var column = 0;
                              column < columnCount;
                              column++
                            ) ...[
                              _SeatCell(
                                seat: lookup['${rowKeys[index]}:$column'],
                                selected: selectedNumbers.contains(
                                  lookup['${rowKeys[index]}:$column']?.number,
                                ),
                                width: seatWidth,
                                height: seatHeight,
                                onTap: onTap,
                              ),
                              if (column == 1 && maxColumn >= 3)
                                SizedBox(width: aisleGap),
                              if (column < maxColumn)
                                const SizedBox(width: columnGap),
                            ],
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

class _SeatCell extends StatelessWidget {
  const _SeatCell({
    required this.seat,
    required this.selected,
    required this.width,
    required this.height,
    required this.onTap,
  });
  final TravelSeat? seat;
  final bool selected;
  final double width;
  final double height;
  final ValueChanged<TravelSeat> onTap;

  @override
  Widget build(BuildContext context) {
    final value = seat;
    if (value == null || value.status == SeatAvailability.empty) {
      return SizedBox(width: width, height: height);
    }
    final booked = !value.selectable && !selected;
    final bookedGender = switch (value.status) {
      SeatAvailability.male => 'male',
      SeatAvailability.female => 'female',
      SeatAvailability.booked => value.bookedGender,
      _ => null,
    };
    final hasBookedGender = bookedGender == 'male' || bookedGender == 'female';
    final bookedColor = bookedGender == 'female'
        ? _femaleBookedSeatColor
        : _maleBookedSeatColor;
    final color = selected
        ? AppColors.primary
        : booked
        ? bookedColor
        : AppColors.surface;
    final foreground = selected
        ? Colors.white
        : booked
        ? bookedGender == 'female'
              ? _femaleSeatAccent
              : _maleSeatAccent
        : AppColors.foreground;
    final borderColor = selected
        ? AppColors.primaryDark
        : booked
        ? foreground.withValues(alpha: .38)
        : AppColors.border;
    return Semantics(
      label:
          'Seat ${value.number}, ${selected
              ? 'selected'
              : hasBookedGender
              ? '$bookedGender booked'
              : value.status.name}',
      button: value.selectable,
      selected: selected,
      child: InkWell(
        onTap: value.selectable || selected ? () => onTap(value) : null,
        borderRadius: BorderRadius.circular(13),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: width,
          height: height,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color,
            borderRadius: const BorderRadius.vertical(
              top: Radius.circular(12),
              bottom: Radius.circular(9),
            ),
            border: Border.all(color: borderColor, width: selected ? 1.5 : 1),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: .22),
                      blurRadius: 7,
                      offset: const Offset(0, 3),
                    ),
                  ]
                : null,
          ),
          child: Stack(
            fit: StackFit.expand,
            children: [
              Center(
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Text(
                    value.number,
                    style: TextStyle(
                      color: foreground,
                      fontSize: 12,
                      height: 1.2,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),
              Positioned(
                left: 9,
                right: 9,
                bottom: 5,
                child: Container(
                  height: 3,
                  decoration: BoxDecoration(
                    color: foreground.withValues(alpha: selected ? .45 : .18),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              if (selected || booked)
                Positioned(
                  top: 3,
                  right: 3,
                  child: Icon(
                    selected ? Icons.check_rounded : Icons.lock_rounded,
                    size: 9,
                    color: foreground.withValues(alpha: selected ? 1 : .65),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SeatFooter extends StatelessWidget {
  const _SeatFooter({required this.flow});
  final BookingFlowState flow;

  @override
  Widget build(BuildContext context) {
    final total = flow.selectedSeats.fold<double>(
      0,
      (sum, seat) => sum + seat.price,
    );
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${flow.selectedSeats.length} seat${flow.selectedSeats.length == 1 ? '' : 's'} selected',
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 10.5,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    flow.selectedSeats.isEmpty
                        ? 'None selected'
                        : flow.selectedSeats
                              .map((seat) => seat.number)
                              .join(', '),
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                const Text(
                  'Total',
                  style: TextStyle(color: AppColors.muted, fontSize: 10.5),
                ),
                Text(
                  formatPkr(total),
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 8),
        FilledButton(
          onPressed: flow.selectedSeats.isEmpty
              ? null
              : () => context.push('/search/passengers'),
          child: const Text('Continue'),
        ),
      ],
    );
  }
}

class PassengerDetailsScreen extends ConsumerStatefulWidget {
  const PassengerDetailsScreen({super.key});

  @override
  ConsumerState<PassengerDetailsScreen> createState() =>
      _PassengerDetailsScreenState();
}

class _PassengerData {
  final name = TextEditingController();
  final cnic = TextEditingController();
  final mobile = TextEditingController();
  String gender = 'male';
  bool saveForFuture = false;
  bool loadedFromSaved = false;

  void fill({
    required String fullName,
    required String cnicValue,
    required String mobileValue,
    required String genderValue,
    bool fromSaved = false,
  }) {
    name.text = fullName;
    cnic.text = cnicValue;
    mobile.text = mobileValue;
    gender = ['male', 'female', 'other'].contains(genderValue)
        ? genderValue
        : 'male';
    loadedFromSaved = fromSaved;
    saveForFuture = false;
  }

  void dispose() {
    name.dispose();
    cnic.dispose();
    mobile.dispose();
  }
}

class _PassengerDetailsScreenState
    extends ConsumerState<PassengerDetailsScreen> {
  final _formKey = GlobalKey<FormState>();
  final _forms = <_PassengerData>[];
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    final selected = ref.read(bookingFlowProvider).selectedSeats;
    _forms.addAll(List.generate(selected.length, (_) => _PassengerData()));
    final passenger = ref.read(authControllerProvider).passenger;
    if (_forms.isNotEmpty && passenger != null) {
      _forms.first.fill(
        fullName: passenger.fullName,
        cnicValue: passenger.cnic ?? '',
        mobileValue: passenger.mobile,
        genderValue: passenger.gender ?? 'male',
      );
    }
  }

  @override
  void dispose() {
    for (final form in _forms) {
      form.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final flow = ref.watch(bookingFlowProvider);
    if (flow.selectedSeats.isEmpty || _forms.isEmpty) {
      return const _MissingFlow();
    }
    return Scaffold(
      backgroundColor: const Color(0xFFEDF3EE),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: EdgeInsets.zero,
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          children: [
            const PassengerDetailsHeader(),
            Transform.translate(
              offset: const Offset(0, -18),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(18, 0, 18, 12),
                child: Column(
                  children: [
                    for (var index = 0; index < _forms.length; index++) ...[
                      if (index > 0) const SizedBox(height: 24),
                      _PassengerCard(
                        index: index,
                        seat: flow.selectedSeats[index].number,
                        data: _forms[index],
                        onUseSaved: () => _useSavedPassenger(index),
                        onEdited: () => _markEdited(index),
                        onGenderChanged: (value) => setState(() {
                          _forms[index]
                            ..gender = value
                            ..loadedFromSaved = false;
                        }),
                        onSaveForFutureChanged: (value) =>
                            setState(() => _forms[index].saveForFuture = value),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: PassengerContinueAction(
        submitting: _submitting,
        onPressed: _continue,
      ),
    );
  }

  Future<void> _useSavedPassenger(int index) async {
    final selected = await showModalBottomSheet<SavedPassenger>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _SavedPassengerSheet(),
    );
    if (selected == null || !mounted) return;
    setState(() {
      _forms[index].fill(
        fullName: selected.fullName,
        cnicValue: selected.cnic,
        mobileValue: selected.mobile,
        genderValue: selected.gender,
        fromSaved: true,
      );
    });
  }

  void _markEdited(int index) {
    if (!_forms[index].loadedFromSaved) return;
    setState(() => _forms[index].loadedFromSaved = false);
  }

  Future<void> _continue() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    final session = ref.read(passengerSessionProvider);
    final seats = ref.read(bookingFlowProvider).selectedSeats;
    final passengers = [
      for (var index = 0; index < _forms.length; index++)
        PassengerDetails(
          seatNumber: seats[index].number,
          fullName: _forms[index].name.text.trim(),
          cnic: _forms[index].cnic.text.trim(),
          mobile: _forms[index].mobile.text.trim(),
          gender: _forms[index].gender,
        ),
    ];
    final toSave = [
      for (var index = 0; index < _forms.length; index++)
        if (_forms[index].saveForFuture && !_forms[index].loadedFromSaved)
          passengers[index],
    ];

    if (toSave.isNotEmpty) {
      setState(() => _submitting = true);
      try {
        final repository = ref.read(travelRepositoryProvider);
        await Future.wait([
          for (final passenger in toSave)
            repository.createSavedPassenger(
              fullName: passenger.fullName,
              cnic: passenger.cnic.replaceAll(RegExp(r'\D'), ''),
              mobile: passenger.mobile.replaceAll(RegExp(r'\D'), ''),
              gender: passenger.gender,
            ),
        ]);
        ref.invalidate(savedPassengersProvider);
      } catch (error) {
        if (mounted) {
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(SnackBar(content: Text(error.toString())));
        }
        return;
      } finally {
        if (mounted) setState(() => _submitting = false);
      }
    }

    if (!mounted || ref.read(passengerSessionProvider) != session) return;
    ref.read(bookingFlowProvider.notifier).setPassengers(passengers);
    context.push('/search/checkout');
  }
}

class _PassengerCard extends StatelessWidget {
  const _PassengerCard({
    required this.index,
    required this.seat,
    required this.data,
    required this.onUseSaved,
    required this.onEdited,
    required this.onGenderChanged,
    required this.onSaveForFutureChanged,
  });
  final int index;
  final String seat;
  final _PassengerData data;
  final VoidCallback onUseSaved;
  final VoidCallback onEdited;
  final ValueChanged<String> onGenderChanged;
  final ValueChanged<bool> onSaveForFutureChanged;

  @override
  Widget build(BuildContext context) => PassengerDetailsCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        PassengerCardHeading(
          number: index + 1,
          seat: seat,
          onUseSaved: onUseSaved,
        ),
        const Padding(
          padding: EdgeInsets.symmetric(vertical: 18),
          child: Divider(height: 1, color: Color(0xFFE2EBE4)),
        ),
        _LabelledField(
          label: 'Full name',
          icon: Icons.person_outline_rounded,
          controller: data.name,
          textCapitalization: TextCapitalization.words,
          autofillHints: const [AutofillHints.name],
          onChanged: (_) => onEdited(),
          validator: (value) => value == null || value.trim().isEmpty
              ? 'Full name is required.'
              : null,
        ),
        const SizedBox(height: 12),
        _LabelledField(
          label: 'CNIC',
          icon: Icons.badge_outlined,
          controller: data.cnic,
          hint: '#####-#######-#',
          keyboardType: TextInputType.number,
          onChanged: (_) => onEdited(),
          validator: (value) =>
              value == null || value.replaceAll(RegExp(r'\D'), '').length != 13
              ? 'Enter a valid 13-digit CNIC.'
              : null,
        ),
        const SizedBox(height: 12),
        _LabelledField(
          label: 'Mobile number',
          icon: Icons.phone_outlined,
          controller: data.mobile,
          keyboardType: TextInputType.phone,
          autofillHints: const [AutofillHints.telephoneNumber],
          textInputAction: TextInputAction.done,
          onChanged: (_) => onEdited(),
          validator: (value) {
            final digits = value?.replaceAll(RegExp(r'\D'), '') ?? '';
            return digits.length < 10 || digits.length > 15
                ? 'Enter a valid mobile number.'
                : null;
          },
        ),
        const SizedBox(height: 14),
        const Text(
          'Gender',
          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            for (final value in ['male', 'female', 'other']) ...[
              Expanded(
                child: _GenderPill(
                  label: value[0].toUpperCase() + value.substring(1),
                  selected: data.gender == value,
                  onTap: () => onGenderChanged(value),
                ),
              ),
              if (value != 'other') const SizedBox(width: 8),
            ],
          ],
        ),
        const SizedBox(height: 18),
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF0F5F0),
            borderRadius: BorderRadius.circular(15),
            border: Border.all(color: const Color(0xFFE0EAE1)),
          ),
          child: data.loadedFromSaved
              ? const ListTile(
                  contentPadding: EdgeInsets.zero,
                  dense: true,
                  leading: Icon(
                    Icons.check_circle_outline_rounded,
                    color: AppColors.primary,
                  ),
                  title: Text(
                    'Already saved to your account',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                )
              : CheckboxListTile(
                  contentPadding: const EdgeInsets.only(left: 2, right: 10),
                  controlAffinity: ListTileControlAffinity.leading,
                  value: data.saveForFuture,
                  onChanged: (value) => onSaveForFutureChanged(value ?? false),
                  activeColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15),
                  ),
                  checkboxShape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(5),
                  ),
                  title: const Text(
                    'Save this passenger for future use',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                  ),
                  subtitle: const Text(
                    'Available in Account and the Use saved list.',
                    style: TextStyle(fontSize: 11),
                  ),
                ),
        ),
      ],
    ),
  );
}

class _SavedPassengerSheet extends ConsumerWidget {
  const _SavedPassengerSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passengers = ref.watch(
      savedPassengersProvider(ref.watch(passengerSessionProvider)),
    );
    return SafeArea(
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.sizeOf(context).height * .72,
        ),
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
        decoration: const BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 38,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Saved passengers',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Flexible(
              child: passengers.when(
                loading: () => const Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator(),
                ),
                error: (error, _) => AppStateView(
                  icon: Icons.people_outline_rounded,
                  title: 'Could not load passengers',
                  message: error.toString(),
                  actionLabel: 'Try again',
                  onAction: () => ref.invalidate(savedPassengersProvider),
                ),
                data: (items) => items.isEmpty
                    ? AppStateView(
                        icon: Icons.people_outline_rounded,
                        title: 'No saved passengers',
                        message:
                            'Add passengers in Account for faster checkout.',
                        actionLabel: 'Manage passengers',
                        onAction: () {
                          Navigator.pop(context);
                          context.push('/profile/saved-passengers');
                        },
                      )
                    : ListView.separated(
                        shrinkWrap: true,
                        itemCount: items.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final passenger = items[index];
                          return Material(
                            color: AppColors.primarySoft,
                            borderRadius: AppRadius.card,
                            child: ListTile(
                              shape: const RoundedRectangleBorder(
                                borderRadius: AppRadius.card,
                              ),
                              leading: const CircleAvatar(
                                backgroundColor: Colors.white,
                                foregroundColor: AppColors.primary,
                                child: Icon(Icons.person_outline_rounded),
                              ),
                              title: Text(
                                passenger.fullName,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              subtitle: Text(
                                '${passenger.cnic}  ·  ${passenger.mobile}',
                              ),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () => Navigator.pop(context, passenger),
                            ),
                          );
                        },
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LabelledField extends StatelessWidget {
  const _LabelledField({
    required this.label,
    required this.icon,
    required this.controller,
    this.hint,
    this.keyboardType,
    this.onChanged,
    this.validator,
    this.textCapitalization = TextCapitalization.none,
    this.autofillHints,
    this.textInputAction = TextInputAction.next,
  });
  final String label;
  final IconData icon;
  final TextEditingController controller;
  final String? hint;
  final TextInputType? keyboardType;
  final ValueChanged<String>? onChanged;
  final String? Function(String?)? validator;
  final TextCapitalization textCapitalization;
  final Iterable<String>? autofillHints;
  final TextInputAction textInputAction;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
      ),
      const SizedBox(height: 6),
      DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(15),
          gradient: const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFE8EFE9), Color(0xFFF7FAF7)],
          ),
          boxShadow: const [
            BoxShadow(color: Colors.white, offset: Offset(1, 2), blurRadius: 2),
          ],
        ),
        child: TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          onChanged: onChanged,
          validator: validator,
          textCapitalization: textCapitalization,
          autofillHints: autofillHints,
          textInputAction: textInputAction,
          scrollPadding: const EdgeInsets.all(32),
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          decoration: InputDecoration(
            prefixIcon: Icon(icon, size: 19, color: const Color(0xFF74947D)),
            hintText: hint,
            fillColor: Colors.transparent,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 15,
            ),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(15),
              borderSide: const BorderSide(color: Color(0xFFD5E1D7)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(15),
              borderSide: const BorderSide(
                color: AppColors.primary,
                width: 1.5,
              ),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(15),
              borderSide: const BorderSide(color: AppColors.danger),
            ),
            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(15),
              borderSide: const BorderSide(color: AppColors.danger, width: 1.5),
            ),
            errorMaxLines: 2,
          ),
        ),
      ),
    ],
  );
}

class _GenderPill extends StatelessWidget {
  const _GenderPill({
    required this.label,
    required this.selected,
    required this.onTap,
  });
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Semantics(
    selected: selected,
    button: true,
    child: AnimatedContainer(
      duration: MediaQuery.disableAnimationsOf(context)
          ? Duration.zero
          : const Duration(milliseconds: 150),
      constraints: const BoxConstraints(minHeight: 46),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: selected
              ? [const Color(0xFF3BA862), const Color(0xFF0B793D)]
              : [Colors.white, const Color(0xFFF0F5F0)],
        ),
        borderRadius: BorderRadius.circular(13),
        border: Border.all(
          color: selected ? const Color(0xFF69BD83) : const Color(0xFFDCE7DE),
        ),
        boxShadow: [
          BoxShadow(
            color: selected ? const Color(0xFF075A30) : const Color(0xFFD6E1D8),
            offset: const Offset(0, 3),
          ),
          if (selected)
            const BoxShadow(
              color: Color(0x26386E46),
              offset: Offset(0, 5),
              blurRadius: 7,
            ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(13),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Container(
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 14),
            child: Text(
              label,
              style: TextStyle(
                color: selected ? Colors.white : AppColors.muted,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  Future<FareQuote>? _request;
  int _quoteRequest = 0;
  String? _paymentMethod;
  int _pointsToUse = 0;
  bool _submitting = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final flow = ref.read(bookingFlowProvider);
    if (_request == null &&
        flow.query != null &&
        flow.schedule != null &&
        flow.selectedSeats.isNotEmpty &&
        flow.passengers.isNotEmpty) {
      _request = _loadQuote(flow);
    }
  }

  Future<FareQuote> _loadQuote(BookingFlowState flow) async {
    final session = ref.read(passengerSessionProvider);
    final requestNumber = ++_quoteRequest;
    final quote = await ref
        .read(travelRepositoryProvider)
        .quote(
          query: flow.query!,
          schedule: flow.schedule!,
          seats: flow.selectedSeats,
          passengers: flow.passengers,
          pointsToUse: _pointsToUse,
        );
    if (mounted &&
        ref.read(passengerSessionProvider) == session &&
        requestNumber == _quoteRequest) {
      setState(() {
        if (!quote.paymentMethods.contains(_paymentMethod)) {
          _paymentMethod = quote.paymentMethods.firstOrNull;
        }
      });
      ref.read(bookingFlowProvider.notifier).setQuote(quote);
    }
    return quote;
  }

  @override
  Widget build(BuildContext context) {
    final flow = ref.watch(bookingFlowProvider);
    final wallet = ref.watch(
      loyaltyWalletProvider(ref.watch(passengerSessionProvider)),
    );
    if (_request == null || flow.schedule == null || flow.query == null) {
      return const _MissingFlow();
    }
    return Scaffold(
      backgroundColor: checkoutBackground,
      appBar: const CheckoutHeader(),
      body: FutureBuilder<FareQuote>(
        future: _request,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppLoadingView(label: 'Confirming the latest fare…');
          }
          if (snapshot.hasError) {
            return AppStateView(
              icon: Icons.receipt_long_outlined,
              title: 'Could not confirm fare',
              message: snapshot.error.toString(),
              actionLabel: 'Try again',
              onAction: () => setState(() {
                _request = _loadQuote(flow);
              }),
            );
          }
          final quote = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.fromLTRB(18, 22, 18, 24),
            children: [
              CheckoutBookingSummary(
                flow: flow,
                totalLabel: formatPkr(quote.total),
              ),
              const SizedBox(height: 28),
              const AppSectionTitle('Payment method'),
              const SizedBox(height: 12),
              PaymentMethodSelector(
                availableMethods: quote.paymentMethods,
                environment: quote.paymentEnvironment,
                previewOnly: quote.paymentPreview,
                selectedMethod: _paymentMethod,
                onSelected: (method) => setState(() => _paymentMethod = method),
                onRetry: () => setState(() {
                  _request = _loadQuote(flow);
                }),
              ),
              _walletSelector(wallet, flow),
            ],
          );
        },
      ),
      bottomNavigationBar: FutureBuilder<FareQuote>(
        future: _request,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done ||
              !snapshot.hasData ||
              snapshot.hasError) {
            return const SizedBox.shrink();
          }
          return CheckoutAction(
            submitting: _submitting,
            onPressed:
                snapshot.data!.paymentPreview ||
                    !snapshot.data!.paymentMethods.contains(_paymentMethod) ||
                    _submitting
                ? null
                : () => _book(snapshot.data!),
            label: snapshot.data!.paymentPreview
                ? 'Preview only · Payments disabled'
                : _submitting
                ? 'Confirming…'
                : _paymentMethod == 'counter' ||
                      _paymentMethod == 'pay_at_branch'
                ? 'Reserve · ${formatPkr(snapshot.data!.total)}'
                : 'Continue · ${formatPkr(snapshot.data!.total)}',
          );
        },
      ),
    );
  }

  Widget _walletSelector(
    AsyncValue<LoyaltyWallet> wallet,
    BookingFlowState flow,
  ) {
    return wallet.maybeWhen(
      data: (data) {
        if (!data.active || data.pointsAvailable < 1) {
          return const SizedBox.shrink();
        }
        final selected = _pointsToUse.clamp(0, data.pointsAvailable);
        return Padding(
          padding: const EdgeInsets.only(top: 6, bottom: 10),
          child: AppCard(
            child: Row(
              children: [
                const CircleAvatar(
                  backgroundColor: AppColors.primarySoft,
                  foregroundColor: AppColors.primary,
                  child: Icon(Icons.stars_rounded),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Use Kainat Points',
                        style: TextStyle(fontWeight: FontWeight.w800),
                      ),
                      Text(
                        '${data.pointsAvailable} available · ${data.redemptionLabel}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: !_submitting && selected > 0
                      ? () => setState(() {
                          _pointsToUse = selected - 1;
                          _request = _loadQuote(flow);
                        })
                      : null,
                  icon: const Icon(Icons.remove_circle_outline_rounded),
                ),
                Text(
                  '$selected',
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                IconButton(
                  onPressed: !_submitting && selected < data.pointsAvailable
                      ? () => setState(() {
                          _pointsToUse = selected + 1;
                          _request = _loadQuote(flow);
                        })
                      : null,
                  icon: const Icon(Icons.add_circle_outline_rounded),
                ),
              ],
            ),
          ),
        );
      },
      orElse: () => const SizedBox.shrink(),
    );
  }

  Future<void> _book(FareQuote quote) async {
    if (quote.paymentPreview) return;
    if (_submitting || !quote.paymentMethods.contains(_paymentMethod)) return;
    final session = ref.read(passengerSessionProvider);
    setState(() => _submitting = true);
    try {
      final booking = await ref
          .read(travelRepositoryProvider)
          .createBooking(
            quoteToken: quote.token,
            paymentMethod: _paymentMethod!,
          );
      if (!mounted || ref.read(passengerSessionProvider) != session) return;
      ref.read(bookingFlowProvider.notifier).reset();
      ref.invalidate(bookingsProvider);
      ref.invalidate(loyaltyWalletProvider);
      if (mounted) {
        context.go(
          booking.payment != null
              ? '/payments/${booking.id}?checkout=1'
              : '/booking-confirmed',
          extra: booking,
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }
}

class BookingConfirmationScreen extends StatelessWidget {
  const BookingConfirmationScreen({required this.booking, super.key});
  final BookingSummary booking;

  @override
  Widget build(BuildContext context) => Scaffold(
    body: Stack(
      children: [
        Container(
          height: 370,
          width: double.infinity,
          decoration: const BoxDecoration(gradient: AppGradients.ticket),
        ),
        SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 44, 16, 28),
            children: [
              Center(
                child: Container(
                  width: 66,
                  height: 66,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .16),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.check_circle_outline_rounded,
                    color: AppColors.gold,
                    size: 42,
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                booking.status == 'confirmed'
                    ? 'Booking confirmed!'
                    : 'Reservation created',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 25,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 7),
              Text(
                booking.paymentStatus == 'paid'
                    ? 'Your payment has been received.'
                    : 'Pay at your branch to confirm your tickets.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
              const SizedBox(height: 18),
              Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 18,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .18),
                    borderRadius: AppRadius.button,
                  ),
                  child: Text(
                    booking.reference,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 1.2,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              AppCard(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: _ConfirmationStop('From', booking.originName),
                        ),
                        const Icon(
                          Icons.directions_bus_rounded,
                          color: AppColors.primary,
                        ),
                        Expanded(
                          child: _ConfirmationStop(
                            'To',
                            booking.destinationName,
                            right: true,
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 28),
                    Row(
                      children: [
                        Expanded(
                          child: _DetailValue(
                            'DATE',
                            DateFormat(
                              'EEE, d MMM yyyy',
                            ).format(booking.departureAt),
                          ),
                        ),
                        Expanded(
                          child: _DetailValue(
                            'SEATS',
                            booking.seats.join(', '),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: _DetailValue(
                            'PASSENGERS',
                            '${booking.passengers.length}',
                          ),
                        ),
                        Expanded(
                          child: _DetailValue(
                            'TOTAL',
                            formatPkr(booking.total),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              AppCard(
                color: const Color(0xFFFFF7E4),
                child: const Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.location_on_outlined, color: AppColors.goldDark),
                    SizedBox(width: 9),
                    Expanded(
                      child: Text(
                        'Reach the terminal early and carry your CNIC and booking reference.',
                        style: TextStyle(
                          color: AppColors.goldDark,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () => context.go('/tickets/${booking.id}'),
                child: const Text('View ticket'),
              ),
              TextButton(
                onPressed: () => context.go('/home'),
                child: const Text('Back to home'),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _ConfirmationStop extends StatelessWidget {
  const _ConfirmationStop(this.label, this.value, {this.right = false});
  final String label;
  final String value;
  final bool right;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: right
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(color: AppColors.muted, fontSize: 10.5),
      ),
      const SizedBox(height: 5),
      Text(
        value,
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
      ),
    ],
  );
}

class _DetailValue extends StatelessWidget {
  const _DetailValue(this.label, this.value);
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(
          color: AppColors.muted,
          fontSize: 9.5,
          letterSpacing: .4,
        ),
      ),
      const SizedBox(height: 4),
      Text(
        value,
        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
      ),
    ],
  );
}

class _InlineLoadError extends StatelessWidget {
  const _InlineLoadError({required this.error, required this.onRetry});
  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Expanded(
        child: Text(
          error.toString(),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: AppColors.muted, fontSize: 11),
        ),
      ),
      TextButton(onPressed: onRetry, child: const Text('Retry')),
    ],
  );
}

class _MissingFlow extends StatelessWidget {
  const _MissingFlow();

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: const AppPageHeader(title: 'Booking'),
    body: AppStateView(
      icon: Icons.route_outlined,
      title: 'Start a new search',
      message: 'Your booking selection is no longer available.',
      actionLabel: 'Search buses',
      onAction: () => context.go('/search'),
    ),
  );
}
