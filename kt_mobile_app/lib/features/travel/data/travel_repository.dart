import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../payments/domain/booking_payment.dart';
import '../domain/travel_models.dart';

class TravelRepository {
  TravelRepository(this._api);

  final ApiClient _api;

  Future<List<CityOption>> cities() async => _list(
    await _api.get('/cities'),
  ).map((item) => CityOption.fromJson(_map(item))).toList();

  Future<List<FleetMedia>> fleet() async => _list(
    await _api.get('/fleet', queryParameters: {'include_placements': 1}),
  ).map((item) => FleetMedia.fromJson(_map(item))).toList();

  Future<LoyaltyWallet> wallet() async =>
      LoyaltyWallet.fromJson(_map(await _api.get('/wallet')));

  Future<List<SavedPassenger>> savedPassengers() async => _list(
    await _api.get('/saved-passengers'),
  ).map((item) => SavedPassenger.fromJson(_map(item))).toList();

  Future<SavedPassenger> createSavedPassenger({
    required String fullName,
    required String cnic,
    required String mobile,
    required String gender,
  }) async => SavedPassenger.fromJson(
    _map(
      await _api.post(
        '/saved-passengers',
        data: {
          'full_name': fullName,
          'cnic': cnic,
          'mobile': mobile,
          'gender': gender,
        },
      ),
    ),
  );

  Future<SavedPassenger> updateSavedPassenger({
    required int id,
    required String fullName,
    required String cnic,
    required String mobile,
    required String gender,
  }) async => SavedPassenger.fromJson(
    _map(
      await _api.put(
        '/saved-passengers/$id',
        data: {
          'full_name': fullName,
          'cnic': cnic,
          'mobile': mobile,
          'gender': gender,
        },
      ),
    ),
  );

  Future<void> deleteSavedPassenger(int id) async {
    await _api.delete('/saved-passengers/$id');
  }

  Future<List<CityOption>> destinations(int originId) async => _list(
    await _api.get('/destinations', queryParameters: {'origin_id': originId}),
  ).map((item) => CityOption.fromJson(_map(item))).toList();

  Future<List<TravelSchedule>> schedules(SearchQuery query) async => _list(
    await _api.get('/schedules', queryParameters: query.queryParameters),
  ).map((item) => TravelSchedule.fromJson(_map(item))).toList();

  Future<SeatLayoutResult> seats(
    TravelSchedule schedule,
    SearchQuery query,
  ) async => SeatLayoutResult.fromJson(
    _map(
      await _api.get(
        '/schedules/${schedule.scheduleDetailId}/seats',
        queryParameters: query.queryParameters,
      ),
    ),
  );

  Future<FareQuote> quote({
    required SearchQuery query,
    required TravelSchedule schedule,
    required List<TravelSeat> seats,
    required List<PassengerDetails> passengers,
    int pointsToUse = 0,
  }) async => FareQuote.fromJson(
    _map(
      await _api.post(
        '/bookings/quote',
        data: {
          ...query.queryParameters,
          'schedule_detail_id': schedule.scheduleDetailId,
          'seats': seats.map((seat) => seat.number).toList(),
          'passengers': passengers
              .map((passenger) => passenger.toJson())
              .toList(),
          'points_to_use': pointsToUse,
        },
      ),
    ),
  );

  Future<BookingSummary> createBooking({
    required String quoteToken,
    required String paymentMethod,
  }) async {
    if (!isSelectablePaymentMethod(paymentMethod)) {
      throw const ApiException(
        'This payment method is no longer available. Please choose another method.',
      );
    }
    return BookingSummary.fromJson(
      _map(
        await _api.post(
          '/bookings',
          data: {'quote_token': quoteToken, 'payment_method': paymentMethod},
        ),
      ),
    );
  }

  Future<List<BookingSummary>> bookings({CancelToken? cancelToken}) async {
    const perPage = 50;
    final bookings = <int, BookingSummary>{};
    int? expectedTotal;
    var page = 1;
    while (true) {
      if (cancelToken?.isCancelled == true) {
        throw const ApiException('The request was cancelled.');
      }
      final raw = await _api.get(
        '/bookings',
        queryParameters: {'page': page, 'per_page': perPage},
        cancelToken: cancelToken,
      );
      if (cancelToken?.isCancelled == true) {
        throw const ApiException('The request was cancelled.');
      }
      // Older API deployments returned the complete list without an envelope.
      if (raw is List && page == 1) {
        return raw.map((item) => BookingSummary.fromJson(_map(item))).toList();
      }
      final response = _map(raw);
      final values = _list(response['items']);
      final pagination = _map(response['pagination']);
      final total = pagination['total'];
      final lastPage = pagination['last_page'];
      if (total is! int ||
          total < 0 ||
          lastPage is! int ||
          lastPage < 1 ||
          pagination['current_page'] != page ||
          pagination['per_page'] != perPage ||
          lastPage != (total == 0 ? 1 : (total / perPage).ceil()) ||
          (expectedTotal != null && total != expectedTotal) ||
          values.length != (total - (page - 1) * perPage).clamp(0, perPage)) {
        throw const ApiException(
          'Your trips could not be fully loaded. Please try again.',
        );
      }
      expectedTotal = total;
      for (final value in values) {
        final booking = BookingSummary.fromJson(_map(value));
        if (bookings.containsKey(booking.id)) {
          // A concurrent insert/delete can shift offset-based pages. Never
          // present duplicated or incomplete pages as a complete trip history.
          throw const ApiException(
            'Your bookings changed while loading. Please try again.',
          );
        }
        bookings[booking.id] = booking;
      }
      if (page == lastPage) return bookings.values.toList();
      page++;
    }
  }

  Future<BookingSummary> refreshPayment(int id) async =>
      BookingSummary.fromJson(
        _map(await _api.post('/bookings/$id/payment/refresh')),
      );

  Future<BookingSummary> booking(int id) async =>
      BookingSummary.fromJson(_map(await _api.get('/bookings/$id')));

  List<dynamic> _list(dynamic value) {
    if (value is List) return value;
    throw const FormatException('Expected a list from the server.');
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return value.cast<String, dynamic>();
    throw const FormatException('Expected an object from the server.');
  }
}
