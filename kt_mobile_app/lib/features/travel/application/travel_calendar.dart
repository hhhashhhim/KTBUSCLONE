import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../startup/application/startup_controller.dart';

/// Calendar-only values: never convert the server's day to the device timezone.
class TravelCalendar {
  TravelCalendar.fromServerDate(String? value) : today = _parse(value);

  final DateTime today;
  DateTime get lastDate => DateTime(today.year, today.month, today.day + 90);

  DateTime selection(DateTime? selected) {
    if (selected == null || selected.isBefore(today)) return today;
    if (selected.isAfter(lastDate)) return lastDate;
    return selected;
  }

  static DateTime _parse(String? value) {
    if (value == null || !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(value)) {
      throw const FormatException('Travel date is unavailable. Please retry.');
    }
    final parts = value.split('-').map(int.parse).toList();
    final date = DateTime(parts[0], parts[1], parts[2]);
    if (parts[0] < 1 ||
        date.year != parts[0] ||
        date.month != parts[1] ||
        date.day != parts[2]) {
      throw const FormatException('Travel date is unavailable. Please retry.');
    }
    return date;
  }
}

final travelCalendarProvider = FutureProvider.autoDispose<TravelCalendar>((
  ref,
) async {
  final config = await ref.watch(appConfigRepositoryProvider).fetch();
  return TravelCalendar.fromServerDate(config.serverDate);
}, retry: (retryCount, error) => null);
