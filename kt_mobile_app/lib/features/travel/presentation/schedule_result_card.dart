import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../domain/travel_models.dart';

class ScheduleResultCard extends StatelessWidget {
  const ScheduleResultCard({
    required this.schedule,
    required this.onTap,
    super.key,
  });

  final TravelSchedule schedule;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final className = schedule.busClassName
        .replaceFirst(
          RegExp(r'\s*\|\s*\d+\s+seats\s*$', caseSensitive: false),
          '',
        )
        .replaceAll('*', '×');
    final fare = schedule.lowestFare;
    final canSelect = schedule.availableSeats > 0 && fare != null;
    final price = _SchedulePrice(fare: fare);
    final action = _ScheduleAction(
      availableSeats: schedule.availableSeats,
      enabled: canSelect,
      onTap: onTap,
    );

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(25),
        boxShadow: const [
          BoxShadow(color: Color(0xFFD1DFD5), offset: Offset(0, 5)),
          BoxShadow(
            color: Color(0x172C5139),
            blurRadius: 22,
            offset: Offset(0, 12),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(25),
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(25),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Colors.white, Color(0xFFFCFEFC)],
            ),
            border: Border.all(color: Colors.white, width: 1.5),
          ),
          child: InkWell(
            onTap: canSelect ? onTap : null,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          className,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            height: 1.3,
                            letterSpacing: -.15,
                          ),
                        ),
                      ),
                      if (schedule.totalSeats > 0) ...[
                        const SizedBox(width: 12),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEDF5EF),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFFDDEADF)),
                          ),
                          child: Text(
                            '${schedule.totalSeats} seats',
                            semanticsLabel:
                                'Coach capacity: ${schedule.totalSeats} seats',
                            style: const TextStyle(
                              color: AppColors.primaryDeep,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(14, 15, 14, 0),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 13,
                    ),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      gradient: const LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Color(0xFFEDF4EF), Color(0xFFF6FAF7)],
                      ),
                      border: Border.all(color: const Color(0xFFE2ECE4)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          flex: 4,
                          child: _JourneyEndpoint(
                            time: schedule.departureAt,
                            city: schedule.origin.name,
                          ),
                        ),
                        Expanded(
                          flex: 3,
                          child: _JourneyProgress(
                            duration: schedule.roundedJourneyDuration,
                          ),
                        ),
                        Expanded(
                          flex: 4,
                          child: _JourneyEndpoint(
                            time: schedule.journeyDuration == null
                                ? null
                                : schedule.arrivalAt,
                            city: schedule.destination.name,
                            right: true,
                            dayOffset: schedule.arrivalDayOffset,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const Padding(
                  padding: EdgeInsets.fromLTRB(14, 13, 14, 8),
                  child: _BusAmenities(),
                ),
                const SizedBox(
                  height: 16,
                  child: ExcludeSemantics(
                    child: CustomPaint(painter: _TicketDividerPainter()),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 17),
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      // Keep fares and controls readable at larger text sizes.
                      if (constraints.maxWidth < 290 ||
                          MediaQuery.textScalerOf(context).scale(14) > 19) {
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [price, const SizedBox(height: 12), action],
                        );
                      }
                      return Row(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Expanded(child: price),
                          const SizedBox(width: 10),
                          action,
                        ],
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _JourneyEndpoint extends StatelessWidget {
  const _JourneyEndpoint({
    required this.time,
    required this.city,
    this.right = false,
    this.dayOffset = 0,
  });

  final DateTime? time;
  final String city;
  final bool right;
  final int dayOffset;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: right
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      _ScheduleTime(time: time, right: right),
      const SizedBox(height: 6),
      Text(
        city,
        textAlign: right ? TextAlign.right : TextAlign.left,
        style: const TextStyle(
          color: Color(0xFF577561),
          fontSize: 11,
          fontWeight: FontWeight.w600,
          height: 1.25,
        ),
      ),
      if (right && time != null) ...[
        const SizedBox(height: 4),
        Text(
          dayOffset > 0
              ? 'Estimated · +$dayOffset ${dayOffset == 1 ? 'day' : 'days'}'
              : 'Estimated',
          textAlign: TextAlign.right,
          style: const TextStyle(
            fontSize: 9,
            color: AppColors.muted,
            height: 1.2,
          ),
        ),
      ],
    ],
  );
}

class _ScheduleAction extends StatelessWidget {
  const _ScheduleAction({
    required this.availableSeats,
    required this.enabled,
    required this.onTap,
  });

  final int availableSeats;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.end,
    children: [
      Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.event_seat_outlined,
            size: 13,
            color: enabled ? AppColors.primaryDark : AppColors.muted,
          ),
          const SizedBox(width: 5),
          Flexible(
            child: Text(
              availableSeats > 0 ? '$availableSeats seats left' : 'Sold out',
              style: TextStyle(
                color: enabled ? AppColors.primaryDark : AppColors.muted,
                fontSize: 10,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
      const SizedBox(height: 8),
      Container(
        padding: const EdgeInsets.only(bottom: 4),
        decoration: BoxDecoration(
          color: enabled ? const Color(0xFF045B29) : const Color(0xFFD1DAD3),
          borderRadius: BorderRadius.circular(14),
          boxShadow: enabled
              ? const [
                  BoxShadow(
                    color: Color(0x3320733B),
                    blurRadius: 12,
                    offset: Offset(0, 6),
                  ),
                ]
              : null,
        ),
        child: DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            gradient: enabled
                ? const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      Color(0xFF30B95C),
                      Color(0xFF0B8A37),
                      Color(0xFF087331),
                    ],
                  )
                : null,
          ),
          child: FilledButton(
            onPressed: enabled ? onTap : null,
            style: FilledButton.styleFrom(
              backgroundColor: Colors.transparent,
              foregroundColor: Colors.white,
              disabledBackgroundColor: const Color(0xFFE8EEE9),
              disabledForegroundColor: const Color(0xFF839389),
              shadowColor: Colors.transparent,
              minimumSize: const Size(0, 44),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              side: BorderSide(
                color: enabled
                    ? const Color(0xFF70CC8A)
                    : const Color(0xFFF2F6F3),
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Flexible(
                  child: Text(
                    'Select seats',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
                  ),
                ),
                SizedBox(width: 7),
                Icon(Icons.arrow_forward_rounded, size: 16),
              ],
            ),
          ),
        ),
      ),
    ],
  );
}

class _TicketDividerPainter extends CustomPainter {
  const _TicketDividerPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final line = Paint()
      ..color = const Color(0xFFD9E5DC)
      ..strokeWidth = 1;
    for (var x = 14.0; x < size.width - 14; x += 8) {
      canvas.drawLine(
        Offset(x, size.height / 2),
        Offset(x + 4, size.height / 2),
        line,
      );
    }
    final cutout = Paint()..color = const Color(0xFFEFF5F0);
    canvas.drawCircle(Offset(0, size.height / 2), 7, cutout);
    canvas.drawCircle(Offset(size.width, size.height / 2), 7, cutout);
  }

  @override
  bool shouldRepaint(covariant _TicketDividerPainter oldDelegate) => false;
}

class _JourneyProgress extends StatelessWidget {
  const _JourneyProgress({required this.duration});
  final Duration? duration;

  @override
  Widget build(BuildContext context) {
    final value = duration;
    final hours = value?.inHours ?? 0;
    final minutes = (value?.inMinutes ?? 0) % 60;
    final label = [
      if (hours > 0) '$hours hr',
      if (minutes > 0) '$minutes min',
    ].join(' ');
    return Padding(
      padding: EdgeInsets.fromLTRB(5, value == null ? 13 : 1, 5, 0),
      child: Column(
        children: [
          if (value != null) ...[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(7),
                border: Border.all(color: const Color(0xFFE1ECE4)),
              ),
              child: Text(
                label,
                textAlign: TextAlign.center,
                semanticsLabel: 'Estimated travel time: $label',
                style: const TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w600,
                  color: AppColors.muted,
                ),
              ),
            ),
            const SizedBox(height: 5),
          ],
          const Row(
            children: [
              Icon(Icons.circle, size: 5, color: AppColors.primary),
              Expanded(child: Divider(color: AppColors.border)),
              Icon(
                Icons.arrow_forward_rounded,
                size: 17,
                color: AppColors.muted,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ScheduleTime extends StatelessWidget {
  const _ScheduleTime({required this.time, this.right = false});

  final DateTime? time;
  final bool right;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: right
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text.rich(
        TextSpan(
          text: time == null ? '—' : DateFormat('h:mm').format(time!),
          children: time == null
              ? null
              : [
                  TextSpan(
                    text: ' ${DateFormat('a').format(time!)}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
        ),
        textAlign: right ? TextAlign.right : TextAlign.left,
        semanticsLabel: time == null
            ? 'Arrival time unavailable'
            : DateFormat('h:mm a').format(time!),
        style: const TextStyle(
          fontSize: 22,
          fontWeight: FontWeight.w800,
          height: 1.1,
        ),
      ),
    ],
  );
}

class _SchedulePrice extends StatelessWidget {
  const _SchedulePrice({required this.fare});
  final FareOption? fare;

  String _format(num value) => NumberFormat.currency(
    locale: 'en_PK',
    symbol: 'PKR ',
    decimalDigits: value % 1 == 0 ? 0 : 2,
  ).format(value);

  @override
  Widget build(BuildContext context) {
    final value = fare;
    if (value == null) {
      return const Text(
        'Fare unavailable',
        style: TextStyle(color: AppColors.muted),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (value.originalAmount > value.amount) ...[
          Text(
            _format(value.originalAmount),
            semanticsLabel: 'Original fare ${_format(value.originalAmount)}',
            style: const TextStyle(
              color: Color(0xFF788AA5),
              fontSize: 13,
              decoration: TextDecoration.lineThrough,
              decorationColor: Color(0xFF788AA5),
              decorationThickness: 1.5,
            ),
          ),
          const SizedBox(height: 3),
        ],
        Text(
          _format(value.amount),
          semanticsLabel: 'Current fare ${_format(value.amount)} per passenger',
          style: const TextStyle(
            color: AppColors.primaryDark,
            fontSize: 23,
            fontWeight: FontWeight.w900,
            height: 1.15,
          ),
        ),
        const SizedBox(height: 3),
        const Text(
          'per passenger',
          style: TextStyle(color: AppColors.muted, fontSize: 10),
        ),
      ],
    );
  }
}

class _BusAmenities extends StatelessWidget {
  const _BusAmenities();

  // Fleet-wide amenities confirmed by the operator; these are not API fields.
  static const _amenities = [
    (Icons.wifi_rounded, 'Wi-Fi'),
    (Icons.charging_station_rounded, 'Charging'),
    (Icons.restaurant_rounded, 'Meals'),
    (Icons.weekend_rounded, 'Reclining seats'),
    (Icons.desktop_windows_rounded, 'Entertainment'),
    (Icons.luggage_rounded, 'Luggage'),
  ];

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
    child: Row(
      children: [
        for (final (icon, label) in _amenities)
          Expanded(
            child: Tooltip(
              message: label,
              child: Container(
                height: 34,
                margin: const EdgeInsets.symmetric(horizontal: 3),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFFFFFBF1), Color(0xFFFCEBD4)],
                  ),
                  border: Border.all(color: const Color(0xFFFFF5E7)),
                  boxShadow: const [
                    BoxShadow(color: Color(0xFFEAD6B7), offset: Offset(0, 2)),
                  ],
                ),
                child: Icon(
                  icon,
                  size: 19,
                  color: const Color(0xFFCB740F),
                  shadows: const [
                    Shadow(color: Colors.white, offset: Offset(0, -1)),
                  ],
                ),
              ),
            ),
          ),
      ],
    ),
  );
}
