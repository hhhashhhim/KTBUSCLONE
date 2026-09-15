import 'package:intl/intl.dart';
import '../../payments/domain/booking_payment.dart';

class CityOption {
  const CityOption({required this.id, required this.name});

  final int id;
  final String name;

  factory CityOption.fromJson(Map<String, dynamic> json) => CityOption(
    id: (json['id'] as num).toInt(),
    name: json['name'].toString(),
  );
}

class SearchQuery {
  const SearchQuery({
    required this.origin,
    required this.destination,
    required this.date,
  });

  final CityOption origin;
  final CityOption destination;
  final DateTime date;

  Map<String, dynamic> get queryParameters => {
    'origin_id': origin.id,
    'destination_id': destination.id,
    'date': DateFormat('yyyy-MM-dd').format(date),
  };
}

class FareOption {
  const FareOption({
    required this.classId,
    required this.className,
    required this.amount,
    required this.originalAmount,
  });

  final int classId;
  final String className;
  final double amount;
  final double originalAmount;

  factory FareOption.fromJson(Map<String, dynamic> json) => FareOption(
    classId: (json['class_id'] as num).toInt(),
    className: (json['class_name'] ?? 'Standard').toString(),
    amount: (json['amount'] as num).toDouble(),
    originalAmount: ((json['original_amount'] ?? json['amount']) as num)
        .toDouble(),
  );
}

class TravelSchedule {
  const TravelSchedule({
    required this.id,
    required this.scheduleDetailId,
    required this.origin,
    required this.destination,
    required this.departureAt,
    required this.busClassName,
    required this.availableSeats,
    required this.totalSeats,
    required this.fares,
    this.arrivalAt,
  });

  final int id;
  final int scheduleDetailId;
  final CityOption origin;
  final CityOption destination;
  final DateTime departureAt;
  final DateTime? arrivalAt;
  final String busClassName;
  final int availableSeats;
  final int totalSeats;
  final List<FareOption> fares;

  double get minimumFare => fares.isEmpty
      ? 0
      : fares.map((fare) => fare.amount).reduce((a, b) => a < b ? a : b);

  factory TravelSchedule.fromJson(Map<String, dynamic> json) {
    final busClass =
        (json['bus_class'] as Map?)?.cast<String, dynamic>() ?? const {};
    return TravelSchedule(
      id: (json['id'] as num).toInt(),
      scheduleDetailId: (json['schedule_detail_id'] as num).toInt(),
      origin: CityOption.fromJson(
        (json['origin'] as Map).cast<String, dynamic>(),
      ),
      destination: CityOption.fromJson(
        (json['destination'] as Map).cast<String, dynamic>(),
      ),
      departureAt: DateTime.parse(json['departure_at'].toString()).toLocal(),
      arrivalAt: json['arrival_at'] == null
          ? null
          : DateTime.parse(json['arrival_at'].toString()).toLocal(),
      busClassName: (busClass['name'] ?? 'Kainat Travels').toString(),
      availableSeats: (json['available_seats'] as num?)?.toInt() ?? 0,
      totalSeats: (json['total_seats'] as num?)?.toInt() ?? 0,
      fares: ((json['fares'] as List?) ?? const [])
          .map(
            (item) =>
                FareOption.fromJson((item as Map).cast<String, dynamic>()),
          )
          .toList(),
    );
  }
}

enum SeatAvailability {
  available,
  booked,
  held,
  reserved,
  male,
  female,
  disabled,
  unavailable,
  empty,
}

class TravelSeat {
  const TravelSeat({
    required this.number,
    required this.row,
    required this.column,
    required this.status,
    required this.price,
    required this.classId,
    this.className,
    this.type,
    this.deck,
    this.genderRestriction,
  });

  final String number;
  final int row;
  final int column;
  final SeatAvailability status;
  final double price;
  final int classId;
  final String? className;
  final String? type;
  final String? deck;
  final String? genderRestriction;

  bool get selectable => status == SeatAvailability.available;

  factory TravelSeat.fromJson(Map<String, dynamic> json) => TravelSeat(
    number: json['number'].toString(),
    row: (json['row'] as num).toInt(),
    column: (json['column'] as num).toInt(),
    status: SeatAvailability.values.firstWhere(
      (value) => value.name == json['status'],
      orElse: () => SeatAvailability.unavailable,
    ),
    price: (json['price'] as num?)?.toDouble() ?? 0,
    classId: (json['class_id'] as num?)?.toInt() ?? 0,
    className: json['class_name']?.toString(),
    type: json['type']?.toString(),
    deck: json['deck']?.toString(),
    genderRestriction: json['gender_restriction']?.toString(),
  );
}

class SeatLayoutResult {
  const SeatLayoutResult({
    required this.seats,
    required this.maximumSelectable,
  });

  final List<TravelSeat> seats;
  final int maximumSelectable;

  factory SeatLayoutResult.fromJson(Map<String, dynamic> json) =>
      SeatLayoutResult(
        seats: ((json['seats'] as List?) ?? const [])
            .map(
              (item) =>
                  TravelSeat.fromJson((item as Map).cast<String, dynamic>()),
            )
            .toList(),
        maximumSelectable:
            (json['maximum_selectable_seats'] as num?)?.toInt() ?? 5,
      );
}

class PassengerDetails {
  const PassengerDetails({
    required this.seatNumber,
    required this.fullName,
    required this.cnic,
    required this.mobile,
    required this.gender,
  });

  final String seatNumber;
  final String fullName;
  final String cnic;
  final String mobile;
  final String gender;

  Map<String, dynamic> toJson() => {
    'seat_number': seatNumber,
    'full_name': fullName,
    'cnic': cnic,
    'mobile': mobile,
    'gender': gender,
  };
}

class SavedPassenger {
  const SavedPassenger({
    required this.id,
    required this.fullName,
    required this.cnic,
    required this.mobile,
    required this.gender,
  });

  final int id;
  final String fullName;
  final String cnic;
  final String mobile;
  final String gender;

  factory SavedPassenger.fromJson(Map<String, dynamic> json) => SavedPassenger(
    id: (json['id'] as num).toInt(),
    fullName: (json['full_name'] ?? '').toString(),
    cnic: (json['cnic'] ?? '').toString(),
    mobile: (json['mobile'] ?? '').toString(),
    gender: (json['gender'] ?? 'male').toString(),
  );
}

class FareQuote {
  const FareQuote({
    required this.token,
    required this.baseFare,
    required this.taxes,
    required this.fees,
    required this.discount,
    required this.walletDeduction,
    this.walletPointsUsed = 0,
    required this.total,
    required this.currency,
    required this.paymentMethods,
    this.paymentEnvironment = 'live',
    this.paymentPreview = false,
  });

  final String token;
  final double baseFare;
  final double taxes;
  final double fees;
  final double discount;
  final double walletDeduction;
  final int walletPointsUsed;
  final double total;
  final String currency;
  final List<String> paymentMethods;
  final String paymentEnvironment;
  final bool paymentPreview;

  factory FareQuote.fromJson(Map<String, dynamic> json) => FareQuote(
    token: json['quote_token'].toString(),
    baseFare: (json['base_fare'] as num).toDouble(),
    taxes: (json['taxes'] as num?)?.toDouble() ?? 0,
    fees: (json['fees'] as num?)?.toDouble() ?? 0,
    discount: (json['discount'] as num?)?.toDouble() ?? 0,
    walletDeduction: (json['wallet_deduction'] as num?)?.toDouble() ?? 0,
    walletPointsUsed: (json['wallet_points_used'] as num?)?.toInt() ?? 0,
    total: (json['total'] as num).toDouble(),
    currency: (json['currency'] ?? 'PKR').toString(),
    paymentEnvironment: (json['payment_environment'] as String?) ?? 'live',
    paymentPreview: json['payment_preview'] == true,
    paymentMethods: ((json['payment_methods'] as List?) ?? const [])
        .map((item) => item.toString())
        .toList(),
  );
}

class FleetMedia {
  const FleetMedia({
    required this.id,
    required this.title,
    required this.mediaType,
    required this.imageUrl,
    this.busClassName,
  });

  final int id;
  final String title;
  final String mediaType;
  final String imageUrl;
  final String? busClassName;

  factory FleetMedia.fromJson(Map<String, dynamic> json) {
    final busClass = (json['bus_class'] as Map?)?.cast<String, dynamic>();
    return FleetMedia(
      id: (json['id'] as num).toInt(),
      title: (json['title'] ?? 'Kainat Travels').toString(),
      mediaType: (json['media_type'] ?? 'exterior').toString(),
      imageUrl: (json['image_url'] ?? '').toString(),
      busClassName: busClass?['name']?.toString(),
    );
  }
}

class LoyaltyWallet {
  const LoyaltyWallet({
    required this.active,
    required this.pointsAvailable,
    this.message,
    this.expiresAt,
    this.category,
    this.discountType,
    this.flatDiscountPerPoint = 0,
    this.percentageDiscountPerPoint = 0,
  });

  final bool active;
  final int pointsAvailable;
  final String? message;
  final String? expiresAt;
  final String? category;
  final String? discountType;
  final double flatDiscountPerPoint;
  final double percentageDiscountPerPoint;

  factory LoyaltyWallet.fromJson(Map<String, dynamic> json) {
    final card = (json['card'] as Map?)?.cast<String, dynamic>() ?? const {};
    final redemption =
        (json['redemption'] as Map?)?.cast<String, dynamic>() ?? const {};
    return LoyaltyWallet(
      active: json['active'] == true,
      pointsAvailable: (json['points_available'] as num?)?.toInt() ?? 0,
      message: json['message']?.toString(),
      expiresAt: json['expires_at']?.toString(),
      category: card['category']?.toString(),
      discountType: redemption['discount_type']?.toString(),
      flatDiscountPerPoint:
          (redemption['flat_discount_per_point'] as num?)?.toDouble() ?? 0,
      percentageDiscountPerPoint:
          (redemption['percentage_discount_per_point'] as num?)?.toDouble() ??
          0,
    );
  }

  String get redemptionLabel {
    if (!active) return message ?? 'No active loyalty card';
    if (discountType == 'percentage') {
      return '${percentageDiscountPerPoint.toStringAsFixed(0)}% discount per point';
    }
    return 'Rs. ${flatDiscountPerPoint.toStringAsFixed(0)} discount per point';
  }
}

class BookingSummary {
  const BookingSummary({
    required this.id,
    required this.reference,
    required this.status,
    required this.paymentStatus,
    required this.originName,
    required this.destinationName,
    required this.departureAt,
    required this.seats,
    required this.total,
    this.passengers = const [],
    this.qrValue,
    this.payment,
  });

  final int id;
  final String reference;
  final String status;
  final String paymentStatus;
  final String originName;
  final String destinationName;
  final DateTime departureAt;
  final List<String> seats;
  final double total;
  final List<BookingPassenger> passengers;
  final String? qrValue;
  final BookingPayment? payment;

  factory BookingSummary.fromJson(Map<String, dynamic> json) => BookingSummary(
    id: (json['id'] as num).toInt(),
    reference: json['reference'].toString(),
    status: json['status'].toString(),
    paymentStatus: (json['payment_status'] ?? 'pending').toString(),
    originName: (json['origin_name'] ?? '').toString(),
    destinationName: (json['destination_name'] ?? '').toString(),
    departureAt: DateTime.parse(json['departure_at'].toString()).toLocal(),
    seats: ((json['seats'] as List?) ?? const [])
        .map((item) => item.toString())
        .toList(),
    total: (json['total'] as num?)?.toDouble() ?? 0,
    passengers: ((json['passengers'] as List?) ?? const [])
        .map(
          (item) =>
              BookingPassenger.fromJson((item as Map).cast<String, dynamic>()),
        )
        .toList(),
    qrValue: json['qr_value']?.toString(),
    payment: json['payment'] is Map
        ? BookingPayment.fromJson(
            (json['payment'] as Map).cast<String, dynamic>(),
          )
        : null,
  );
}

class BookingPassenger {
  const BookingPassenger({
    required this.fullName,
    required this.seatNumber,
    this.gender,
  });

  final String fullName;
  final String seatNumber;
  final String? gender;

  factory BookingPassenger.fromJson(Map<String, dynamic> json) =>
      BookingPassenger(
        fullName: (json['full_name'] ?? '').toString(),
        seatNumber: (json['seat_number'] ?? '').toString(),
        gender: json['gender']?.toString(),
      );
}
