import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

void main() {
  test(
    'schedule reads the special count and defaults older responses to zero',
    () {
      final payload = <String, dynamic>{
        'id': 1,
        'schedule_detail_id': 2,
        'origin': {'id': 1, 'name': 'Multan'},
        'destination': {'id': 2, 'name': 'Rawalpindi'},
        'departure_at': '2026-11-08T13:44:00',
        'available_seats': 20,
      };
      expect(TravelSchedule.fromJson(payload).availableSpecialSeats, 0);
      final schedule = TravelSchedule.fromJson({
        ...payload,
        'available_special_seats': 3,
      });
      expect(schedule.availableSpecialSeats, 3);
      expect(schedule.availableSeats, 20);
    },
  );

  test(
    'special fare requires an explicit server flag, a saving and an available seat',
    () {
      final ordinary = <String, dynamic>{
        'number': '12',
        'row': 0,
        'column': 0,
        'class_id': 1,
        'status': 'available',
        'price': 2000,
        'base_fare': 2500,
      };
      expect(TravelSeat.fromJson(ordinary).hasSpecialFare, isFalse);
      final special = {
        ...ordinary,
        'is_special': true,
        'quota_discount': 500,
        'quota_discount_percentage': 20,
        'fare_before_quota_discount': 2500,
      };
      final seat = TravelSeat.fromJson(special);
      expect(seat.hasSpecialFare, isTrue);
      expect(seat.quotaDiscountPercentage, 20);
      expect(seat.fareBeforeQuotaDiscount, 2500);
      for (final change in [
        {'is_special': false},
        {'is_special': 'Yes'},
        {'quota_discount': 0},
        {'status': 'booked'},
        {'status': 'unavailable'},
        {'status': 'unknown'},
      ]) {
        expect(
          TravelSeat.fromJson({...special, ...change}).hasSpecialFare,
          isFalse,
        );
      }
    },
  );

  test(
    'special savings split the reported discount without changing server totals',
    () {
      final quote = FareQuote.fromJson({
        'quote_token': 'special',
        'base_fare': 2500,
        'discount': 625,
        'quota_discount': 250,
        'total': 1900,
      });
      expect(quote.regularDiscount, 375);
      expect(quote.quotaDiscount, 250);
      expect(quote.discount, 625);
      expect(quote.total, 1900);
    },
  );
  test(
    'seat keeps the API original fare and defaults old responses to price',
    () {
      final payload = <String, dynamic>{
        'number': '1',
        'row': 0,
        'column': 0,
        'class_id': 1,
        'status': 'available',
        'price': 2150,
      };
      expect(TravelSeat.fromJson(payload).baseFare, 2150);
      final seat = TravelSeat.fromJson({...payload, 'base_fare': 2500});
      expect(seat.baseFare, 2500);
      expect(seat.price, 2150);
    },
  );

  test('journey duration rounds to half hours and preserves arrival', () {
    final departure = DateTime(2099, 10, 30, 23, 59);
    for (final (minutes, rounded) in [
      (1, 30),
      (14, 30),
      (15, 30),
      (44, 30),
      (45, 60),
      (60, 60),
      (314, 300),
      (315, 330),
      (344, 330),
      (345, 360),
    ]) {
      final arrival = departure.add(Duration(minutes: minutes));
      final schedule = TravelSchedule(
        id: 1,
        scheduleDetailId: 1,
        origin: const CityOption(id: 1, name: 'Karachi'),
        destination: const CityOption(id: 2, name: 'Rawalpindi'),
        departureAt: departure,
        arrivalAt: arrival,
        busClassName: 'Executive',
        availableSeats: 1,
        totalSeats: 42,
        fares: const [],
      );
      expect(
        schedule.roundedJourneyDuration?.inMinutes,
        rounded,
        reason: '$minutes minutes',
      );
      expect(schedule.arrivalAt, arrival);
      expect(schedule.arrivalDayOffset, 1);
    }
  });

  test(
    'missing or nonpositive arrival has no duration; long journeys retain day offset',
    () {
      final departure = DateTime(2099, 10, 30, 23, 59);
      for (final minutes in [null, -1, 0, 1501]) {
        final schedule = TravelSchedule(
          id: 1,
          scheduleDetailId: 1,
          origin: const CityOption(id: 1, name: 'Karachi'),
          destination: const CityOption(id: 2, name: 'Rawalpindi'),
          departureAt: departure,
          arrivalAt: minutes == null
              ? null
              : departure.add(Duration(minutes: minutes)),
          busClassName: 'Executive',
          availableSeats: 1,
          totalSeats: 42,
          fares: const [],
        );
        expect(
          schedule.roundedJourneyDuration?.inMinutes,
          minutes == 1501 ? 1500 : null,
        );
        expect(schedule.arrivalDayOffset, minutes == 1501 ? 2 : 0);
      }
    },
  );

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
    expect(quote.quotaDiscount, 0);
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
          'class_id': 4,
          'class_name': 'Premium',
          'amount': 4000,
          'original_amount': 4800,
        },
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
    expect(schedule.lowestFare?.classId, 3);
    expect(schedule.lowestFare?.originalAmount, 2700);
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
    expect(booked.bookedGender, isNull);
    for (final gender in ['male', 'female']) {
      final seat = TravelSeat.fromJson({
        'number': 'A3',
        'row': 0,
        'column': 2,
        'status': 'booked',
        'booked_gender': gender,
        'gender_restriction': 'female',
      });
      expect(seat.bookedGender, gender);
      expect(seat.genderRestriction, 'female');
      expect(seat.status, SeatAvailability.booked);
      expect(seat.selectable, isFalse);
    }
  });
}
