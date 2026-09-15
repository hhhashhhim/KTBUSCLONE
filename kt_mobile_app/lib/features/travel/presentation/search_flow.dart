import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_widgets.dart';
import '../application/travel_providers.dart';
import '../domain/travel_models.dart';

String formatPkr(num amount) => NumberFormat.currency(
  locale: 'en_PK',
  symbol: 'PKR ',
  decimalDigits: 0,
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

    return Scaffold(
      appBar: AppBar(title: const Text('Book a trip')),
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(AppSpacing.lg),
                decoration: const BoxDecoration(
                  gradient: AppGradients.primary,
                  borderRadius: AppRadius.card,
                ),
                child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.route_rounded, color: AppColors.gold, size: 30),
                    SizedBox(height: AppSpacing.md),
                    Text(
                      'Where to today?',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 23,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    SizedBox(height: AppSpacing.xs),
                    Text(
                      'Choose a valid Kainat Travels route and departure date.',
                      style: TextStyle(color: Color(0xDFFFFFFF), height: 1.4),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              AppCard(
                child: Column(
                  children: [
                    cities.when(
                      loading: () => const LinearProgressIndicator(),
                      error: (error, _) => _LoadError(
                        message: error.toString(),
                        onRetry: () => ref.invalidate(citiesProvider),
                      ),
                      data: (values) => DropdownButtonFormField<CityOption>(
                        initialValue: _origin,
                        decoration: const InputDecoration(
                          labelText: 'From',
                          prefixIcon: Icon(Icons.trip_origin_rounded),
                        ),
                        items: values
                            .map(
                              (city) => DropdownMenuItem(
                                value: city,
                                child: Text(city.name),
                              ),
                            )
                            .toList(),
                        onChanged: (city) => setState(() {
                          _origin = city;
                          _destination = null;
                        }),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    destinations.when(
                      loading: () => const LinearProgressIndicator(),
                      error: (error, _) => _LoadError(
                        message: error.toString(),
                        onRetry: () {
                          if (_origin != null) {
                            ref.invalidate(destinationsProvider(_origin!.id));
                          }
                        },
                      ),
                      data: (values) => DropdownButtonFormField<CityOption>(
                        initialValue: _destination,
                        decoration: const InputDecoration(
                          labelText: 'To',
                          prefixIcon: Icon(Icons.location_on_rounded),
                        ),
                        items: values
                            .map(
                              (city) => DropdownMenuItem(
                                value: city,
                                child: Text(city.name),
                              ),
                            )
                            .toList(),
                        onChanged: _origin == null
                            ? null
                            : (city) => setState(() => _destination = city),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    InkWell(
                      borderRadius: AppRadius.field,
                      onTap: _pickDate,
                      child: InputDecorator(
                        decoration: const InputDecoration(
                          labelText: 'Departure date',
                          prefixIcon: Icon(Icons.calendar_month_rounded),
                        ),
                        child: Text(DateFormat('EEEE, d MMMM').format(_date)),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    FilledButton.icon(
                      onPressed: _search,
                      icon: const Icon(Icons.search_rounded),
                      label: const Text('Search buses'),
                    ),
                  ],
                ),
              ),
            ],
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

class ScheduleResultsScreen extends ConsumerWidget {
  const ScheduleResultsScreen({required this.query, super.key});

  final SearchQuery query;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schedules = ref.watch(schedulesProvider(query));
    return Scaffold(
      appBar: AppBar(
        title: Text('${query.origin.name} → ${query.destination.name}'),
      ),
      body: RefreshIndicator(
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
          data: (values) => values.isEmpty
              ? ListView(
                  children: const [
                    AppStateView(
                      icon: Icons.event_busy_rounded,
                      title: 'No buses found',
                      message: 'Try another travel date or route.',
                    ),
                  ],
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  itemCount: values.length,
                  separatorBuilder: (_, _) =>
                      const SizedBox(height: AppSpacing.sm),
                  itemBuilder: (context, index) => _ScheduleCard(
                    schedule: values[index],
                    onTap: () {
                      ref
                          .read(bookingFlowProvider.notifier)
                          .begin(query, values[index]);
                      context.push('/search/seats');
                    },
                  ),
                ),
        ),
      ),
    );
  }
}

class _ScheduleCard extends StatelessWidget {
  const _ScheduleCard({required this.schedule, required this.onTap});

  final TravelSchedule schedule;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => AppCard(
    onTap: onTap,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: AppColors.primarySoft,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                schedule.busClassName,
                style: const TextStyle(
                  color: AppColors.primary,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const Spacer(),
            Text(
              '${schedule.availableSeats} seats left',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            _time(context, schedule.departureAt, schedule.origin.name),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
                child: Row(
                  children: [
                    const CircleAvatar(
                      radius: 3,
                      backgroundColor: AppColors.gold,
                    ),
                    Expanded(
                      child: Divider(
                        color: AppColors.border.withValues(alpha: 0.9),
                      ),
                    ),
                    const Icon(
                      Icons.directions_bus_rounded,
                      color: AppColors.primary,
                      size: 20,
                    ),
                    Expanded(
                      child: Divider(
                        color: AppColors.border.withValues(alpha: 0.9),
                      ),
                    ),
                    const CircleAvatar(
                      radius: 3,
                      backgroundColor: AppColors.gold,
                    ),
                  ],
                ),
              ),
            ),
            _time(
              context,
              schedule.arrivalAt,
              schedule.destination.name,
              right: true,
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        const Divider(height: 1),
        const SizedBox(height: AppSpacing.sm),
        Row(
          children: [
            const Icon(Icons.wifi_rounded, color: AppColors.muted, size: 17),
            const SizedBox(width: AppSpacing.xs),
            const Icon(Icons.ac_unit_rounded, color: AppColors.muted, size: 17),
            const Spacer(),
            Text(
              'from ${formatPkr(schedule.minimumFare)}',
              style: const TextStyle(
                color: AppColors.primary,
                fontSize: 16,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ],
    ),
  );

  Widget _time(
    BuildContext context,
    DateTime? value,
    String city, {
    bool right = false,
  }) => Column(
    crossAxisAlignment: right
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text(
        value == null ? '—' : DateFormat('h:mm a').format(value),
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
      ),
      Text(city, style: Theme.of(context).textTheme.bodySmall),
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
    if (_request == null) return const _MissingFlow();
    return Scaffold(
      appBar: AppBar(title: const Text('Select seats')),
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
                  padding: const EdgeInsets.all(AppSpacing.md),
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
              _SeatFooter(flow: flow),
            ],
          );
        },
      ),
    );
  }
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
    final maxRow = seats
        .map((seat) => seat.row)
        .reduce((a, b) => a > b ? a : b);
    final maxColumn = seats
        .map((seat) => seat.column)
        .reduce((a, b) => a > b ? a : b);
    final lookup = {
      for (final seat in seats) '${seat.row}:${seat.column}': seat,
    };
    return AppCard(
      child: Column(
        children: [
          const Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Front', style: TextStyle(fontWeight: FontWeight.w700)),
              Icon(
                Icons.airline_seat_recline_extra_rounded,
                color: AppColors.muted,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          for (var row = 0; row <= maxRow; row++)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.xs),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  for (var column = 0; column <= maxColumn; column++)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: _SeatCell(
                        seat: lookup['$row:$column'],
                        selected:
                            lookup['$row:$column'] != null &&
                            selected.any(
                              (item) =>
                                  item.number == lookup['$row:$column']!.number,
                            ),
                        onTap: onTap,
                      ),
                    ),
                ],
              ),
            ),
        ],
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
      return const SizedBox(width: 45, height: 43);
    }
    final color = selected
        ? AppColors.primary
        : switch (value.status) {
            SeatAvailability.available => AppColors.surface,
            SeatAvailability.female => const Color(0xFFFFD8E8),
            SeatAvailability.male => const Color(0xFFD7E8FF),
            SeatAvailability.held ||
            SeatAvailability.reserved => const Color(0xFFFFE7B7),
            _ => AppColors.surfaceAlt,
          };
    return Semantics(
      button: value.selectable,
      label: 'Seat ${value.number}, ${value.status.name}',
      child: InkWell(
        onTap: value.selectable || selected ? () => onTap(value) : null,
        borderRadius: BorderRadius.circular(10),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: 45,
          height: 43,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: selected
                  ? AppColors.primary
                  : value.selectable
                  ? AppColors.border
                  : Colors.transparent,
              width: 1.4,
            ),
          ),
          child: Text(
            value.number,
            style: TextStyle(
              color: selected ? Colors.white : AppColors.foreground,
              fontWeight: FontWeight.w700,
              fontSize: 12,
            ),
          ),
        ),
      ),
    );
  }
}

class _SeatLegend extends StatelessWidget {
  const _SeatLegend();

  @override
  Widget build(BuildContext context) => const Padding(
    padding: EdgeInsets.fromLTRB(
      AppSpacing.md,
      AppSpacing.xs,
      AppSpacing.md,
      0,
    ),
    child: Wrap(
      spacing: AppSpacing.md,
      runSpacing: AppSpacing.xs,
      children: [
        _LegendItem(color: AppColors.surface, label: 'Available', border: true),
        _LegendItem(color: AppColors.primary, label: 'Selected'),
        _LegendItem(color: AppColors.surfaceAlt, label: 'Booked'),
        _LegendItem(color: Color(0xFFFFD8E8), label: 'Female'),
      ],
    ),
  );
}

class _LegendItem extends StatelessWidget {
  const _LegendItem({
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
        width: 16,
        height: 16,
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(5),
          border: border ? Border.all(color: AppColors.border) : null,
        ),
      ),
      const SizedBox(width: 5),
      Text(label, style: Theme.of(context).textTheme.bodySmall),
    ],
  );
}

class _SeatFooter extends StatelessWidget {
  const _SeatFooter({required this.flow});
  final BookingFlowState flow;

  @override
  Widget build(BuildContext context) => Container(
    padding: EdgeInsets.fromLTRB(
      AppSpacing.md,
      AppSpacing.sm,
      AppSpacing.md,
      MediaQuery.paddingOf(context).bottom + AppSpacing.sm,
    ),
    decoration: const BoxDecoration(
      color: AppColors.surface,
      border: Border(top: BorderSide(color: AppColors.border)),
    ),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                flow.selectedSeats.isEmpty
                    ? 'No seats selected'
                    : flow.selectedSeats.map((seat) => seat.number).join(', '),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            Text(
              formatPkr(
                flow.selectedSeats.fold<double>(
                  0,
                  (sum, seat) => sum + seat.price,
                ),
              ),
              style: const TextStyle(
                color: AppColors.primary,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        FilledButton(
          onPressed: flow.selectedSeats.isEmpty
              ? null
              : () => context.push('/search/passengers'),
          child: const Text('Continue'),
        ),
      ],
    ),
  );
}

class PassengerDetailsScreen extends ConsumerStatefulWidget {
  const PassengerDetailsScreen({super.key});

  @override
  ConsumerState<PassengerDetailsScreen> createState() =>
      _PassengerDetailsScreenState();
}

class _PassengerFormData {
  _PassengerFormData(this.seat);
  final TravelSeat seat;
  final name = TextEditingController();
  final cnic = TextEditingController();
  final mobile = TextEditingController();
  String gender = 'male';

  void dispose() {
    name.dispose();
    cnic.dispose();
    mobile.dispose();
  }
}

class _PassengerDetailsScreenState
    extends ConsumerState<PassengerDetailsScreen> {
  final _formKey = GlobalKey<FormState>();
  late final List<_PassengerFormData> _forms;

  @override
  void initState() {
    super.initState();
    _forms = ref
        .read(bookingFlowProvider)
        .selectedSeats
        .map(_PassengerFormData.new)
        .toList();
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
    if (_forms.isEmpty) return const _MissingFlow();
    return Scaffold(
      appBar: AppBar(title: const Text('Passenger details')),
      body: Form(
        key: _formKey,
        child: ListView.builder(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.md,
            AppSpacing.xs,
            AppSpacing.md,
            110,
          ),
          itemCount: _forms.length,
          itemBuilder: (context, index) => Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: _passengerCard(index, _forms[index]),
          ),
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: FilledButton(
            onPressed: _continue,
            child: const Text('Review fare'),
          ),
        ),
      ),
    );
  }

  Widget _passengerCard(int index, _PassengerFormData data) => AppCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const CircleAvatar(
              backgroundColor: AppColors.primarySoft,
              foregroundColor: AppColors.primary,
              child: Icon(Icons.person_rounded),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Passenger ${index + 1}',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  Text(
                    'Seat ${data.seat.number}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            if (index > 0)
              TextButton(
                onPressed: () => setState(() {
                  data.mobile.text = _forms.first.mobile.text;
                }),
                child: const Text('Copy contact'),
              ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        TextFormField(
          controller: data.name,
          decoration: const InputDecoration(labelText: 'Full name'),
          validator: (value) => value == null || value.trim().isEmpty
              ? 'Full name is required.'
              : null,
        ),
        const SizedBox(height: AppSpacing.sm),
        TextFormField(
          controller: data.cnic,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'CNIC',
            hintText: '#####-#######-#',
          ),
          validator: (value) => value == null || value.trim().isEmpty
              ? 'CNIC is required.'
              : null,
        ),
        const SizedBox(height: AppSpacing.sm),
        TextFormField(
          controller: data.mobile,
          keyboardType: TextInputType.phone,
          decoration: const InputDecoration(labelText: 'Mobile number'),
          validator: (value) => value == null || value.trim().isEmpty
              ? 'Mobile is required.'
              : null,
        ),
        const SizedBox(height: AppSpacing.sm),
        SegmentedButton<String>(
          segments: const [
            ButtonSegment(
              value: 'male',
              label: Text('Male'),
              icon: Icon(Icons.male_rounded),
            ),
            ButtonSegment(
              value: 'female',
              label: Text('Female'),
              icon: Icon(Icons.female_rounded),
            ),
          ],
          selected: {data.gender},
          onSelectionChanged: (value) =>
              setState(() => data.gender = value.first),
        ),
      ],
    ),
  );

  void _continue() {
    if (!_formKey.currentState!.validate()) return;
    ref
        .read(bookingFlowProvider.notifier)
        .setPassengers(
          _forms
              .map(
                (data) => PassengerDetails(
                  seatNumber: data.seat.number,
                  fullName: data.name.text.trim(),
                  cnic: data.cnic.text.trim(),
                  mobile: data.mobile.text.trim(),
                  gender: data.gender,
                ),
              )
              .toList(),
        );
    context.push('/search/checkout');
  }
}

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  Future<FareQuote>? _request;
  String? _paymentMethod;
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
      _request = ref
          .read(travelRepositoryProvider)
          .quote(
            query: flow.query!,
            schedule: flow.schedule!,
            seats: flow.selectedSeats,
            passengers: flow.passengers,
          );
    }
  }

  @override
  Widget build(BuildContext context) {
    final flow = ref.watch(bookingFlowProvider);
    if (_request == null) return const _MissingFlow();
    return Scaffold(
      appBar: AppBar(title: const Text('Review booking')),
      body: FutureBuilder<FareQuote>(
        future: _request,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppLoadingView(label: 'Confirming the live fare…');
          }
          if (snapshot.hasError) {
            return AppStateView(
              icon: Icons.receipt_long_outlined,
              title: 'Could not confirm fare',
              message: snapshot.error.toString(),
              actionLabel: 'Try again',
              onAction: () => setState(
                () => _request = ref
                    .read(travelRepositoryProvider)
                    .quote(
                      query: flow.query!,
                      schedule: flow.schedule!,
                      seats: flow.selectedSeats,
                      passengers: flow.passengers,
                    ),
              ),
            );
          }
          final quote = snapshot.data!;
          if (flow.quote == null) {
            WidgetsBinding.instance.addPostFrameCallback(
              (_) => ref.read(bookingFlowProvider.notifier).setQuote(quote),
            );
          }
          _paymentMethod ??= quote.paymentMethods.firstOrNull;
          return ListView(
            padding: const EdgeInsets.all(AppSpacing.md),
            children: [
              _TripReview(flow: flow),
              const SizedBox(height: AppSpacing.sm),
              _FareSummary(quote: quote),
              const SizedBox(height: AppSpacing.sm),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Payment method',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    if (quote.paymentMethods.isEmpty)
                      Text(
                        'Online booking is not enabled by the backend. No payment method will be invented.',
                        style: Theme.of(context).textTheme.bodySmall,
                      )
                    else
                      for (final method in quote.paymentMethods)
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Icon(
                            _paymentMethod == method
                                ? Icons.radio_button_checked_rounded
                                : Icons.radio_button_off_rounded,
                            color: _paymentMethod == method
                                ? AppColors.primary
                                : AppColors.muted,
                          ),
                          title: Text(_paymentLabel(method)),
                          onTap: () => setState(() => _paymentMethod = method),
                        ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              FilledButton(
                onPressed: _paymentMethod == null || _submitting
                    ? null
                    : () => _book(quote),
                child: Text(
                  _submitting
                      ? 'Creating booking…'
                      : 'Continue with ${formatPkr(quote.total)}',
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(
                'The backend revalidates seats and fare before creating a ticket. Cancellation, refund or rescheduling is handled by the Call Centre or nearest branch.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _book(FareQuote quote) async {
    setState(() => _submitting = true);
    try {
      final booking = await ref
          .read(travelRepositoryProvider)
          .createBooking(
            quoteToken: quote.token,
            paymentMethod: _paymentMethod!,
          );
      ref.invalidate(bookingsProvider);
      if (mounted) context.go('/booking-confirmed', extra: booking);
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

  String _paymentLabel(String method) => switch (method) {
    'jazzcash' => 'JazzCash',
    'wallet' => 'Kainat Wallet',
    'counter' => 'Pay at branch',
    _ => method,
  };
}

class _TripReview extends StatelessWidget {
  const _TripReview({required this.flow});
  final BookingFlowState flow;

  @override
  Widget build(BuildContext context) => AppCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '${flow.query!.origin.name} → ${flow.query!.destination.name}',
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(
          '${DateFormat('EEE, d MMM').format(flow.schedule!.departureAt)} · ${DateFormat('h:mm a').format(flow.schedule!.departureAt)} · ${flow.schedule!.busClassName}',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        const Divider(height: AppSpacing.lg),
        Text(
          'Seats ${flow.selectedSeats.map((seat) => seat.number).join(', ')}',
        ),
      ],
    ),
  );
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
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: AppSpacing.sm),
        _line('Base fare', quote.baseFare),
        _line('Taxes', quote.taxes),
        _line('Service fees', quote.fees),
        if (quote.discount > 0) _line('Discount', -quote.discount),
        if (quote.walletDeduction > 0) _line('Wallet', -quote.walletDeduction),
        const Divider(),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Total', style: TextStyle(fontWeight: FontWeight.w800)),
            Text(
              formatPkr(quote.total),
              style: const TextStyle(
                color: AppColors.primary,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ],
    ),
  );

  Widget _line(String label, double value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [Text(label), Text(formatPkr(value))],
    ),
  );
}

class BookingConfirmationScreen extends StatelessWidget {
  const BookingConfirmationScreen({required this.booking, super.key});
  final BookingSummary booking;

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CircleAvatar(
              radius: 38,
              backgroundColor: AppColors.primarySoft,
              child: Icon(
                Icons.check_rounded,
                color: AppColors.primary,
                size: 42,
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(
              'Booking received',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              booking.reference,
              style: const TextStyle(
                color: AppColors.primary,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              'Payment: ${booking.paymentStatus}. Your ticket status is ${booking.status}.',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: AppSpacing.lg),
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
    ),
  );
}

class _LoadError extends StatelessWidget {
  const _LoadError({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const Icon(Icons.error_outline_rounded, color: AppColors.danger),
      const SizedBox(width: AppSpacing.xs),
      Expanded(
        child: Text(message, style: Theme.of(context).textTheme.bodySmall),
      ),
      IconButton(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded)),
    ],
  );
}

class _MissingFlow extends StatelessWidget {
  const _MissingFlow();

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(),
    body: AppStateView(
      icon: Icons.route_outlined,
      title: 'Start a new search',
      message: 'This booking session has expired or is incomplete.',
      actionLabel: 'Search buses',
      onAction: () => context.go('/search'),
    ),
  );
}

extension _FirstOrNull<T> on List<T> {
  T? get firstOrNull => isEmpty ? null : first;
}
