import '../../travel/domain/travel_models.dart';

enum BookingPaymentAction {
  none,
  completePayment,
  checkPaymentStatus,
  bookAgain,
  viewTicket;

  String get label => switch (this) {
    none => '',
    completePayment => 'Complete payment',
    checkPaymentStatus => 'Check payment status',
    bookAgain => 'Book again',
    viewTicket => 'View ticket',
  };
}

// Present only actions supported by the current API. A signed checkout URL is
// issued only before checkout starts; pending without that URL is unresolved.
// The server rechecks reservation ownership, seats and expiry at checkout.
BookingPaymentAction bookingPaymentAction(
  BookingSummary booking, {
  DateTime? now,
}) {
  if (booking.status == 'confirmed' && booking.paymentStatus == 'paid') {
    return BookingPaymentAction.viewTicket;
  }
  final payment = booking.payment;
  if (payment == null) return BookingPaymentAction.none;
  if (booking.paymentStatus == 'paid' ||
      booking.status == 'review_required' ||
      payment.status == 'paid' ||
      payment.status == 'review_required') {
    return BookingPaymentAction.checkPaymentStatus;
  }
  if (payment.status == 'expired' && booking.status == 'expired') {
    return BookingPaymentAction.bookAgain;
  }
  if (booking.status == 'pending' &&
      booking.paymentStatus == 'pending' &&
      payment.status == 'pending' &&
      payment.checkoutUrl?.isNotEmpty == true &&
      payment.expiresAt.isAfter(now ?? DateTime.now())) {
    return BookingPaymentAction.completePayment;
  }
  // Unknown states and local timeout require a server check, never a retry.
  return BookingPaymentAction.checkPaymentStatus;
}

String paymentTimeRemaining(DateTime expiresAt, {DateTime? now}) {
  final seconds = expiresAt.difference(now ?? DateTime.now()).inSeconds;
  if (seconds <= 0) return 'Payment window ended. Check payment status.';
  return 'Time left to pay: ${seconds ~/ 60}:${(seconds % 60).toString().padLeft(2, '0')}';
}
