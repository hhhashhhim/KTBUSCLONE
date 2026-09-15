import 'package:flutter/material.dart';
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

String formatPkr(num amount) =>
    'Rs. ${NumberFormat.decimalPattern('en_PK').format(amount.round())}';

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
    return Scaffold(
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: EdgeInsets.fromLTRB(
              16,
              MediaQuery.paddingOf(context).top + 12,
              16,
              42,
            ),
            color: AppColors.primary,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    _CircleBack(onTap: () => context.go('/home')),
                    const SizedBox(width: 12),
                    const Text(
                      'Search buses',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .16),
                    borderRadius: AppRadius.button,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const _TripTypePill(label: 'One way', selected: true),
                      _TripTypePill(
                        label: 'Round trip',
                        onTap: () => _message(
                          'Round trips are not available from the mobile API yet.',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: Transform.translate(
              offset: const Offset(0, -28),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                children: [
                  AppCard(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: [
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
                        const SizedBox(height: 10),
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
                                : (city) => setState(() => _destination = city),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _DateTile(date: _date, onTap: _pickDate),
                            ),
                            const SizedBox(width: 10),
                            const Expanded(child: _PassengerTile()),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 28),
                  FilledButton(
                    onPressed: _search,
                    child: const Text('Search buses'),
                  ),
                ],
              ),
            ),
          ),
        ],
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

class _CircleBack extends StatelessWidget {
  const _CircleBack({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: Colors.white.withValues(alpha: .16),
    shape: const CircleBorder(),
    child: InkWell(
      customBorder: const CircleBorder(),
      onTap: onTap,
      child: const SizedBox(
        width: 42,
        height: 42,
        child: Icon(Icons.chevron_left_rounded, color: Colors.white, size: 27),
      ),
    ),
  );
}

class _TripTypePill extends StatelessWidget {
  const _TripTypePill({required this.label, this.selected = false, this.onTap});
  final String label;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) => InkWell(
    borderRadius: AppRadius.button,
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 9),
      decoration: BoxDecoration(
        color: selected ? Colors.white : Colors.transparent,
        borderRadius: AppRadius.button,
      ),
      child: Text(
        label,
        style: TextStyle(
          color: selected ? AppColors.primary : Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.w700,
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
  Widget build(BuildContext context) => Material(
    color: AppColors.surfaceAlt,
    borderRadius: AppRadius.field,
    child: InkWell(
      borderRadius: AppRadius.field,
      onTap: onChanged == null ? null : () => _chooseCity(context),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        child: Row(
          children: [
            CircleAvatar(
              backgroundColor: iconColor.withValues(alpha: .1),
              foregroundColor: iconColor,
              child: const Icon(Icons.location_on_outlined, size: 19),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 10.5,
                      letterSpacing: .4,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    value?.name ?? 'Choose city',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: onChanged == null
                          ? AppColors.muted
                          : AppColors.foreground,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.expand_more_rounded, color: AppColors.muted),
          ],
        ),
      ),
    ),
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
  Widget build(BuildContext context) => InkWell(
    borderRadius: AppRadius.field,
    onTap: onTap,
    child: _InfoTile(
      label: 'DEPARTURE',
      icon: Icons.calendar_month_outlined,
      value: DateFormat('dd/MM/yyyy').format(date),
    ),
  );
}

class _PassengerTile extends StatelessWidget {
  const _PassengerTile();

  @override
  Widget build(BuildContext context) => const _InfoTile(
    label: 'PASSENGERS',
    icon: Icons.people_outline_rounded,
    value: '1',
  );
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({
    required this.label,
    required this.icon,
    required this.value,
  });
  final String label;
  final IconData icon;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
    height: 76,
    padding: const EdgeInsets.all(13),
    decoration: const BoxDecoration(
      color: AppColors.surfaceAlt,
      borderRadius: AppRadius.field,
    ),
    child: Column(
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
        const SizedBox(height: 7),
        Row(
          children: [
            Icon(icon, color: AppColors.primary, size: 19),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                value,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ],
    ),
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
    return Scaffold(
      appBar: AppPageHeader(
        title: '${query.origin.name} → ${query.destination.name}',
        subtitle:
            '${DateFormat('EEE, d MMM yyyy').format(query.date)} · 1 passenger',
      ),
      body: Column(
        children: [
          SizedBox(
            height: 60,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 11),
              children: [
                const _ResultChip(label: 'Filters', icon: Icons.tune_rounded),
                const SizedBox(width: 8),
                _ResultChip(
                  label: 'Earliest',
                  selected: !_sortByPrice,
                  onTap: () => setState(() => _sortByPrice = false),
                ),
                const SizedBox(width: 8),
                _ResultChip(
                  label: 'Cheapest',
                  selected: _sortByPrice,
                  onTap: () => setState(() => _sortByPrice = true),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.border),
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
                      onAction: () => ref.invalidate(schedulesProvider(query)),
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
                    padding: const EdgeInsets.all(16),
                    itemCount: sorted.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) => _ScheduleCard(
                      schedule: sorted[index],
                      onTap: () {
                        ref
                            .read(bookingFlowProvider.notifier)
                            .begin(query, sorted[index]);
                        context.push('/search/seats');
                      },
                    ),
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

class _ResultChip extends StatelessWidget {
  const _ResultChip({
    required this.label,
    this.icon,
    this.selected = false,
    this.onTap,
  });
  final String label;
  final IconData? icon;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: selected ? AppColors.primary : AppColors.surfaceAlt,
    borderRadius: AppRadius.button,
    child: InkWell(
      onTap: onTap,
      borderRadius: AppRadius.button,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        child: Row(
          children: [
            if (icon != null) ...[
              Icon(icon, size: 16),
              const SizedBox(width: 5),
            ],
            Text(
              label,
              style: TextStyle(
                color: selected ? Colors.white : AppColors.foreground,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class _ScheduleCard extends StatelessWidget {
  const _ScheduleCard({required this.schedule, required this.onTap});
  final TravelSchedule schedule;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => AppCard(
    padding: const EdgeInsets.all(16),
    onTap: onTap,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          schedule.busClassName,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 15),
        Row(
          children: [
            _ScheduleStop(
              city: schedule.origin.name,
              time: schedule.departureAt,
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: Row(
                  children: [
                    const CircleAvatar(
                      radius: 3,
                      backgroundColor: AppColors.gold,
                    ),
                    const Expanded(child: Divider(color: AppColors.border)),
                    Icon(
                      Icons.arrow_forward_rounded,
                      size: 17,
                      color: AppColors.muted.withValues(alpha: .8),
                    ),
                  ],
                ),
              ),
            ),
            _ScheduleStop(
              city: schedule.destination.name,
              time: schedule.arrivalAt,
              right: true,
            ),
          ],
        ),
        const SizedBox(height: 16),
        const Divider(height: 1),
        const SizedBox(height: 13),
        Row(
          children: [
            const Icon(
              Icons.event_seat_outlined,
              color: AppColors.muted,
              size: 17,
            ),
            const SizedBox(width: 6),
            Text(
              '${schedule.availableSeats} seats left',
              style: const TextStyle(color: AppColors.muted, fontSize: 11),
            ),
            const Spacer(),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  formatPkr(schedule.minimumFare),
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const Text(
                  'per seat',
                  style: TextStyle(color: AppColors.muted, fontSize: 9.5),
                ),
              ],
            ),
          ],
        ),
      ],
    ),
  );
}

class _ScheduleStop extends StatelessWidget {
  const _ScheduleStop({
    required this.city,
    required this.time,
    this.right = false,
  });
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
        time == null ? '—' : DateFormat('HH:mm').format(time!),
        style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 3),
      Text(
        city,
        style: const TextStyle(color: AppColors.muted, fontSize: 10.5),
      ),
    ],
  );
}

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
            '${schedule.busClassName} · ${DateFormat('HH:mm').format(schedule.departureAt)}',
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
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
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
              ),
              AppStickyAction(child: _SeatFooter(flow: flow)),
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
    padding: EdgeInsets.fromLTRB(16, 14, 16, 12),
    child: Wrap(
      spacing: 14,
      runSpacing: 8,
      children: [
        _Legend(color: AppColors.surfaceAlt, label: 'Available', border: true),
        _Legend(color: AppColors.primary, label: 'Selected'),
        _Legend(color: Color(0xFFBCC8C0), label: 'Booked'),
        _Legend(color: Color(0xFFFFD48A), label: 'Ladies'),
      ],
    ),
  );
}

class _Legend extends StatelessWidget {
  const _Legend({
    required this.color,
    required this.label,
    this.border = false,
  });
  final Color color;
  final String label;
  final bool border;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Container(
        width: 18,
        height: 18,
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(5),
          border: border ? Border.all(color: AppColors.border) : null,
        ),
      ),
      const SizedBox(width: 6),
      Text(
        label,
        style: const TextStyle(color: AppColors.muted, fontSize: 10.5),
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
    return Center(
      child: Container(
        constraints: const BoxConstraints(maxWidth: 330),
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 22),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(30),
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
          children: [
            const Row(
              children: [
                Text(
                  'Front',
                  style: TextStyle(color: AppColors.muted, fontSize: 11),
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
                  style: TextStyle(color: AppColors.muted, fontSize: 11),
                ),
              ],
            ),
            const SizedBox(height: 18),
            for (final row in rowKeys)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    for (var column = 0; column <= maxColumn; column++) ...[
                      _SeatCell(
                        seat: rows[row]!.cast<TravelSeat?>().firstWhere(
                          (seat) => seat!.column == column,
                          orElse: () => null,
                        ),
                        selected: rows[row]!.any(
                          (seat) =>
                              seat.column == column &&
                              selected.any(
                                (item) => item.number == seat.number,
                              ),
                        ),
                        onTap: onTap,
                      ),
                      if (column == 1 && maxColumn >= 3)
                        const SizedBox(width: 16),
                      if (column < maxColumn) const SizedBox(width: 7),
                    ],
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _SeatCell extends StatelessWidget {
  const _SeatCell({
    required this.seat,
    required this.selected,
    required this.onTap,
  });
  final TravelSeat? seat;
  final bool selected;
  final ValueChanged<TravelSeat> onTap;

  @override
  Widget build(BuildContext context) {
    final value = seat;
    if (value == null || value.status == SeatAvailability.empty) {
      return const SizedBox(width: 43, height: 42);
    }
    final booked = !value.selectable && !selected;
    final color = selected
        ? AppColors.primary
        : value.status == SeatAvailability.female
        ? const Color(0xFFFFE0A6)
        : booked
        ? const Color(0xFFC6CDC8)
        : AppColors.surfaceAlt;
    return Semantics(
      label: 'Seat ${value.number}, ${value.status.name}',
      button: value.selectable,
      child: InkWell(
        onTap: value.selectable || selected ? () => onTap(value) : null,
        borderRadius: BorderRadius.circular(13),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: 43,
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(
              color: selected
                  ? AppColors.primary
                  : value.status == SeatAvailability.female
                  ? AppColors.gold
                  : AppColors.border,
            ),
          ),
          child: Text(
            value.number,
            style: TextStyle(
              color: selected || booked ? Colors.white : AppColors.foreground,
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
            ),
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
        const SizedBox(height: 12),
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
      appBar: const AppPageHeader(title: 'Passenger details'),
      body: Form(
        key: _formKey,
        child: ListView.separated(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
          itemCount: _forms.length,
          separatorBuilder: (_, _) => const SizedBox(height: 14),
          itemBuilder: (context, index) => _PassengerCard(
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
        ),
      ),
      bottomNavigationBar: AppStickyAction(
        child: FilledButton(
          onPressed: _submitting ? null : _continue,
          child: Text(
            _submitting ? 'Saving passenger…' : 'Continue to payment',
          ),
        ),
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

    if (!mounted) return;
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
  Widget build(BuildContext context) => AppCard(
    padding: const EdgeInsets.all(16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const CircleAvatar(
              backgroundColor: AppColors.primarySoft,
              foregroundColor: AppColors.primary,
              child: Icon(Icons.person_outline_rounded),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Passenger ${index + 1}',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  Text(
                    'Seat $seat',
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
            TextButton(onPressed: onUseSaved, child: const Text('Use saved')),
          ],
        ),
        const SizedBox(height: 16),
        _LabelledField(
          label: 'Full name',
          controller: data.name,
          onChanged: (_) => onEdited(),
          validator: (value) => value == null || value.trim().isEmpty
              ? 'Full name is required.'
              : null,
        ),
        const SizedBox(height: 12),
        _LabelledField(
          label: 'CNIC',
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
          controller: data.mobile,
          keyboardType: TextInputType.phone,
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
        const SizedBox(height: 10),
        if (data.loadedFromSaved)
          const ListTile(
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
        else
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            value: data.saveForFuture,
            onChanged: (value) => onSaveForFutureChanged(value ?? false),
            title: const Text(
              'Save this passenger for future use',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
            ),
            subtitle: const Text(
              'Available in Account and the Use saved list.',
              style: TextStyle(fontSize: 11),
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
    final passengers = ref.watch(savedPassengersProvider);
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
    required this.controller,
    this.hint,
    this.keyboardType,
    this.onChanged,
    this.validator,
  });
  final String label;
  final TextEditingController controller;
  final String? hint;
  final TextInputType? keyboardType;
  final ValueChanged<String>? onChanged;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
      ),
      const SizedBox(height: 6),
      TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        onChanged: onChanged,
        validator: validator,
        decoration: InputDecoration(
          hintText: hint,
          fillColor: AppColors.surface,
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
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: AppRadius.button,
    child: Container(
      height: 42,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: selected ? AppColors.primarySoft : AppColors.surface,
        borderRadius: AppRadius.button,
        border: Border.all(
          color: selected ? AppColors.primary : AppColors.border,
        ),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: selected ? AppColors.primary : AppColors.foreground,
          fontSize: 12,
          fontWeight: FontWeight.w700,
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
    if (mounted && requestNumber == _quoteRequest) {
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
    final wallet = ref.watch(loyaltyWalletProvider);
    if (_request == null || flow.schedule == null || flow.query == null) {
      return const _MissingFlow();
    }
    return Scaffold(
      appBar: const AppPageHeader(title: 'Checkout'),
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
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            children: [
              _TripReview(flow: flow),
              const SizedBox(height: 16),
              const AppSectionTitle('Payment method'),
              const SizedBox(height: 10),
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
              const SizedBox(height: 6),
              _FareSummary(quote: quote),
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
          return AppStickyAction(
            child: FilledButton(
              onPressed:
                  snapshot.data!.paymentPreview ||
                      !snapshot.data!.paymentMethods.contains(_paymentMethod) ||
                      _submitting
                  ? null
                  : () => _book(snapshot.data!),
              child: Text(
                snapshot.data!.paymentPreview
                    ? 'Preview only · Payments disabled'
                    : _submitting
                    ? 'Confirming…'
                    : _paymentMethod == 'counter' ||
                          _paymentMethod == 'pay_at_branch'
                    ? 'Reserve · ${formatPkr(snapshot.data!.total)}'
                    : 'Continue · ${formatPkr(snapshot.data!.total)}',
              ),
            ),
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
    setState(() => _submitting = true);
    try {
      final booking = await ref
          .read(travelRepositoryProvider)
          .createBooking(
            quoteToken: quote.token,
            paymentMethod: _paymentMethod!,
          );
      ref.read(bookingFlowProvider.notifier).reset();
      ref.invalidate(bookingsProvider);
      ref.invalidate(loyaltyWalletProvider);
      if (mounted) {
        context.go(
          booking.payment != null
              ? '/payments/${booking.id}'
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

class _TripReview extends StatelessWidget {
  const _TripReview({required this.flow});
  final BookingFlowState flow;

  @override
  Widget build(BuildContext context) {
    final schedule = flow.schedule!;
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${schedule.origin.name} → ${schedule.destination.name}',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                decoration: const BoxDecoration(
                  color: AppColors.primarySoft,
                  borderRadius: AppRadius.button,
                ),
                child: Text(
                  '${flow.selectedSeats.length} seat${flow.selectedSeats.length == 1 ? '' : 's'}',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          Text(
            DateFormat('EEE, d MMM yyyy · HH:mm').format(schedule.departureAt),
            style: const TextStyle(color: AppColors.muted, fontSize: 11),
          ),
          const Divider(height: 26),
          Text(
            'Seats: ${flow.selectedSeats.map((seat) => seat.number).join(', ')}',
            style: const TextStyle(color: AppColors.muted, fontSize: 11),
          ),
        ],
      ),
    );
  }
}

class _FareSummary extends StatelessWidget {
  const _FareSummary({required this.quote});
  final FareQuote quote;

  @override
  Widget build(BuildContext context) => AppCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Fare summary',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 16),
        _fareRow('Fare', quote.baseFare),
        if (quote.taxes != 0) _fareRow('Taxes', quote.taxes),
        if (quote.fees != 0) _fareRow('Service fee', quote.fees),
        if (quote.discount != 0) _fareRow('Discount', -quote.discount),
        if (quote.walletDeduction != 0)
          _fareRow('Wallet', -quote.walletDeduction),
        if (quote.walletPointsUsed != 0)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(
              '${quote.walletPointsUsed} Kainat Points applied',
              style: const TextStyle(color: AppColors.muted, fontSize: 11),
            ),
          ),
        const Divider(height: 24),
        Row(
          children: [
            const Expanded(
              child: Text(
                'Total',
                style: TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
            Text(
              formatPkr(quote.total),
              style: const TextStyle(
                color: AppColors.primary,
                fontSize: 21,
                fontWeight: FontWeight.w900,
              ),
            ),
          ],
        ),
      ],
    ),
  );

  Widget _fareRow(String label, double value) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
        ),
        Text(
          formatPkr(value),
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        ),
      ],
    ),
  );
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
