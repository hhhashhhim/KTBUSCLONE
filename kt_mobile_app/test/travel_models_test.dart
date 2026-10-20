import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

void main() {
  test(
    'new quotes exclude JazzCash and unsupported methods from an old API',
    () {
      final quote = FareQuote.fromJson({
        'quote_token': 'legacy-methods',
        'base_fare': 2500,
        'total': 2500,
        'payment_methods': ['jazzcash', 'counter', 'bank_alfalah', 'unknown'],
      });
      expect(quote.paymentMethods, ['counter', 'bank_alfalah']);
    },
  );

  test('quote preserves separate adjustments and the authoritative total', () {
    final quote = FareQuote.fromJson({
      'quote_token': 'test-quote',
      'base_fare': 2500,
      'discount': 31.25,
      'surcharge': 0,
      'rounding_adjustment': -18.75,
      'wallet_deduction': 100.25,
      'total': 2349.75,
    });

    expect(quote.discount, 31.25);
    expect(quote.surcharge, 0);
    expect(quote.roundingAdjustment, -18.75);
    expect(quote.walletDeduction, 100.25);
    expect(quote.total, 2349.75);
  });

  test('older quote responses default new adjustments to zero', () {
    final quote = FareQuote.fromJson({
      'quote_token': 'old-quote',
      'base_fare': 2500,
      'total': 2600,
    });

    expect(quote.surcharge, 0);
    expect(quote.roundingAdjustment, 0);
    expect(quote.total, 2600);
  });

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
