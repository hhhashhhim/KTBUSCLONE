import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

void main() {
  test('schedule parser preserves server-provided fare and availability', () {
    final schedule = TravelSchedule.fromJson({
      'id': 7,
      'schedule_detail_id': 17,
      'origin': {'id': 1, 'name': 'Lahore'},
      'destination': {'id': 2, 'name': 'Islamabad'},
      'departure_at': '2026-07-19T08:00:00+05:00',
      'arrival_at': null,
      'bus_class': {'name': 'Business'},
      'available_seats': 12,
      'total_seats': 36,
      'fares': [
        {
          'class_id': 3,
          'class_name': 'Business',
          'amount': 2500,
          'original_amount': 2700,
        },
      ],
    });

    expect(schedule.availableSeats, 12);
    expect(schedule.minimumFare, 2500);
    expect(schedule.fares.single.originalAmount, 2700);
  });

  test('only server-available seats are selectable', () {
    final available = TravelSeat.fromJson({
      'number': 'A1',
      'row': 0,
      'column': 0,
      'status': 'available',
      'price': 2500,
      'class_id': 3,
    });
    final booked = TravelSeat.fromJson({
      'number': 'A2',
      'row': 0,
      'column': 1,
      'status': 'booked',
      'price': 2500,
      'class_id': 3,
    });

    expect(available.selectable, isTrue);
    expect(booked.selectable, isFalse);
  });
}
