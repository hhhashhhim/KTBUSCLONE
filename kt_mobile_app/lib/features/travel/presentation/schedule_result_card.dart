import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_widgets.dart';
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
    final action = Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.event_seat_outlined,
              size: 14,
              color: canSelect ? AppColors.primaryDark : AppColors.muted,
            ),
            const SizedBox(width: 5),
            Flexible(
              child: Text(
                schedule.availableSeats > 0
                    ? '${schedule.availableSeats} seats left'
                    : 'Sold out',
                style: TextStyle(
                  color: canSelect ? AppColors.primaryDark : AppColors.muted,
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            gradient: canSelect
                ? const LinearGradient(
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                    colors: [AppColors.primaryDark, AppColors.primary],
                  )
                : null,
            boxShadow: canSelect
                ? [
                    BoxShadow(
                      color: AppColors.primaryDark.withValues(alpha: .12),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ]
                : const [],
          ),
          child: FilledButton(
            onPressed: canSelect ? onTap : null,
            style: FilledButton.styleFrom(
              backgroundColor: Colors.transparent,
              shadowColor: Colors.transparent,
              minimumSize: const Size(0, 44),
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              side: canSelect
                  ? const BorderSide(color: Color(0x40FFFFFF), width: 1.5)
                  : BorderSide.none,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Flexible(
                  child: Text(
                    'Select seats',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12),
                  ),
                ),
                SizedBox(width: 6),
                Icon(Icons.arrow_forward_rounded, size: 16),
              ],
            ),
          ),
        ),
      ],
    );

    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      onTap: canSelect ? onTap : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.primarySoft,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.directions_bus_rounded,
                  color: AppColors.primaryDark,
                  size: 23,
                ),
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      className,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        height: 1.3,
                      ),
                    ),
                    if (schedule.totalSeats > 0) ...[
                      const SizedBox(height: 3),
                      Text(
                        '${schedule.totalSeats}-seat coach',
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 4,
                child: _ScheduleTime(time: schedule.departureAt),
              ),
              Expanded(
                flex: 3,
                child: _JourneyProgress(
                  duration: schedule.roundedJourneyDuration,
                ),
              ),
              Expanded(
                flex: 4,
                child: _ScheduleTime(
                  time: schedule.journeyDuration == null
                      ? null
                      : schedule.arrivalAt,
                  right: true,
                  estimated: true,
                  dayOffset: schedule.arrivalDayOffset,
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          DefaultTextStyle.merge(
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(flex: 4, child: Text(schedule.origin.name)),
                const Spacer(flex: 3),
                Expanded(
                  flex: 4,
                  child: Text(
                    schedule.destination.name,
                    textAlign: TextAlign.right,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const _BusAmenities(),
          const SizedBox(height: 12),
          const Divider(height: 1, color: AppColors.border),
          const SizedBox(height: 10),
          LayoutBuilder(
            builder: (context, constraints) {
              // Give larger accessibility text its own row instead of shrinking it.
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
                  const SizedBox(width: 12),
                  action,
                ],
              );
            },
          ),
        ],
      ),
    );
  }
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
      padding: EdgeInsets.fromLTRB(5, value == null ? 13 : 2, 5, 0),
      child: Column(
        children: [
          if (value != null) ...[
            Text(
              label,
              textAlign: TextAlign.center,
              semanticsLabel: 'Estimated travel time: $label',
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: AppColors.muted,
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
  const _ScheduleTime({
    required this.time,
    this.right = false,
    this.estimated = false,
    this.dayOffset = 0,
  });

  final DateTime? time;
  final bool right;
  final bool estimated;
  final int dayOffset;

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
          fontSize: 24,
          fontWeight: FontWeight.w800,
          height: 1.1,
        ),
      ),
      if (estimated && time != null) ...[
        const SizedBox(height: 2),
        Text(
          dayOffset > 0
              ? 'Estimated · +$dayOffset ${dayOffset == 1 ? 'day' : 'days'}'
              : 'Estimated',
          textAlign: right ? TextAlign.right : TextAlign.left,
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
              child: Icon(icon, size: 24, color: const Color(0xFFFF7900)),
            ),
          ),
      ],
    ),
  );
}
