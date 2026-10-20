import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/app_environment.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../travel/application/travel_providers.dart';
import '../../travel/domain/travel_models.dart';
import '../domain/booking_payment.dart';
import '../domain/booking_payment_action.dart';
import 'payment_checkout_screen.dart';

final paymentLauncherProvider =
    Provider<Future<bool> Function(BuildContext, Uri)>((ref) {
      return (context, uri) async {
        await Navigator.of(context).push<void>(
          MaterialPageRoute(
            builder: (_) => PaymentCheckoutScreen(checkoutUri: uri),
          ),
        );
        return true;
      };
    });

class PaymentScreen extends ConsumerStatefulWidget {
  const PaymentScreen({
    required this.bookingId,
    this.initialBooking,
    this.openCheckoutOnEntry = false,
    super.key,
  });
  final int bookingId;
  final BookingSummary? initialBooking;
  final bool openCheckoutOnEntry;

  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen>
    with WidgetsBindingObserver {
  BookingSummary? _booking;
  String? _error;
  bool _checking = false;
  bool _opening = false;
  bool _verified = false;
  late bool _startingCheckout;
  Timer? _timer;
  int _ticks = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _booking = widget.initialBooking;
    _startingCheckout = widget.openCheckoutOnEntry;
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) return;
      // Consume the pay intent once. Closing checkout, polling, and resuming
      // the app must only verify status, never automatically replay payment.
      if (_startingCheckout) {
        await _openCheckout();
        if (mounted) setState(() => _startingCheckout = false);
      } else {
        await _load();
      }
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed &&
          ModalRoute.of(context)?.isCurrent == true &&
          !_opening &&
          _booking?.payment?.status == 'pending') {
        setState(() {});
        if (++_ticks % 15 == 0) _load();
      }
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed &&
        !_opening &&
        ModalRoute.of(context)?.isCurrent == true) {
      _load();
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  Future<bool> _load() async {
    if (_checking) return false;
    setState(() {
      _checking = true;
      _verified = false;
      _error = null;
    });
    try {
      final repository = ref.read(travelRepositoryProvider);
      // A deep link may have no initial booking. Discover its payment before
      // verifying, just as when arriving from ticket details.
      var booking = _booking ?? await repository.booking(widget.bookingId);
      if (!mounted) return false;
      setState(() => _booking = booking);
      booking = booking.payment != null
          ? await repository.refreshPayment(widget.bookingId)
          : await repository.booking(widget.bookingId);
      if (!mounted) return false;
      setState(() {
        _booking = booking;
        _verified = true;
      });
      ref.invalidate(bookingsProvider);
      ref.invalidate(loyaltyWalletProvider);
      return true;
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
      return false;
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  Future<void> _openCheckout() async {
    if (_opening || _checking) return;
    setState(() {
      _opening = true;
      _error = null;
    });
    try {
      if (!await _load() || !mounted) return;
      if (bookingPaymentAction(_booking!) !=
          BookingPaymentAction.completePayment) {
        return;
      }
      final uri = trustedCheckoutUri(
        _booking?.payment?.checkoutUrl,
        AppEnvironmentConfig.apiBaseUrl,
        allowLocalSandbox:
            AppEnvironmentConfig.environment == AppEnvironment.development &&
            _booking?.payment?.environment == 'sandbox',
      );
      if (uri == null) {
        setState(
          () => _error =
              'Checkout is unavailable. Check payment status and try again.',
        );
        return;
      }
      setState(() => _verified = false);
      final opened = await ref.read(paymentLauncherProvider)(context, uri);
      if (!opened && mounted) {
        setState(() => _error = 'Could not open checkout. Please try again.');
      } else if (opened && mounted) {
        // Closing the WebView or reaching the return page is only a reason to
        // inquire. The backend remains the authority on payment success.
        await _load();
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open checkout. Please try again.');
      }
    } finally {
      if (mounted) setState(() => _opening = false);
    }
  }

  void _bookAgain() {
    ref.read(bookingFlowProvider.notifier).reset();
    context.go('/search');
  }

  void _goBack() {
    if (context.canPop()) {
      context.pop();
    } else {
      // Booking creation replaces the completed booking flow. Keep a usable
      // destination when this payment screen has no previous route.
      context.go('/tickets');
    }
  }

  @override
  Widget build(BuildContext context) {
    final booking = _booking;
    final payment = booking?.payment;
    final confirmed =
        booking?.status == 'confirmed' && booking?.paymentStatus == 'paid';
    final status = payment?.status;
    final action = booking == null
        ? BookingPaymentAction.none
        : bookingPaymentAction(booking);
    final canComplete = action == BookingPaymentAction.completePayment;
    final canBookAgain = action == BookingPaymentAction.bookAgain && _verified;
    final title = confirmed
        ? 'Payment received'
        : switch (status) {
            'expired' => 'Reservation expired',
            'review_required' => 'Payment needs review',
            'pending' =>
              canComplete && _verified
                  ? 'Complete your payment'
                  : 'Payment awaiting verification',
            _ => 'Booking payment',
          };
    final message = confirmed
        ? 'Your tickets are confirmed and ready to view.'
        : switch (status) {
            'expired' =>
              'The payment window has ended. If you were charged, check payment status or contact support before booking again.',
            'review_required' =>
              'Your payment was received, but the tickets could not be confirmed. Contact support with your booking reference.',
            'pending' =>
              canComplete && _verified
                  ? 'Complete checkout before the reservation expires. Your tickets will be confirmed after payment verification.'
                  : 'Check the latest payment status before making another payment. If you were charged, your payment may still be processing.',
            _ => 'Open your booking to see its payment instructions.',
          };
    return PopScope<Object?>(
      canPop: context.canPop(),
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) context.go('/tickets');
      },
      child: Scaffold(
        appBar: AppPageHeader(title: 'Payment', onBack: _goBack),
        body: _startingCheckout
            ? const AppLoadingView(label: 'Opening secure payment…')
            : booking == null && _checking
            ? const AppLoadingView(label: 'Loading payment…')
            : ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  Icon(
                    confirmed
                        ? Icons.check_circle_outline
                        : Icons.payments_outlined,
                    size: 60,
                    color: AppColors.primary,
                  ),
                  const SizedBox(height: 16),
                  if (payment?.environment == 'sandbox') ...[
                    const Text(
                      'Sandbox · Test payments',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: AppColors.primary,
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],
                  Text(
                    title,
                    style: Theme.of(context).textTheme.headlineSmall,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 12),
                  Text(message, textAlign: TextAlign.center),
                  if (booking != null) ...[
                    const SizedBox(height: 24),
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            booking.reference,
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '${booking.originName} → ${booking.destinationName}',
                          ),
                          Text('Rs. ${booking.total.toStringAsFixed(0)}'),
                          if (payment != null) ...[
                            const SizedBox(height: 8),
                            Text(paymentMethodLabel(payment.method)),
                            if (canComplete) ...[
                              Text(paymentTimeRemaining(payment.expiresAt)),
                              Text(
                                'Checkout closes at ${DateFormat('h:mm a').format(payment.expiresAt)}',
                              ),
                            ],
                          ],
                        ],
                      ),
                    ),
                  ],
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                      semanticsLabel: 'Payment error: $_error',
                    ),
                  ],
                  const SizedBox(height: 24),
                  if (canComplete && _verified)
                    FilledButton(
                      onPressed: _opening || _checking ? null : _openCheckout,
                      child: Text(
                        _opening ? 'Opening checkout…' : 'Complete payment',
                      ),
                    ),
                  if (canBookAgain)
                    FilledButton(
                      onPressed: _opening || _checking ? null : _bookAgain,
                      child: const Text('Book again'),
                    ),
                  if (!confirmed && (canComplete && _verified || canBookAgain))
                    TextButton(
                      onPressed: _checking || _opening ? null : _load,
                      child: Text(
                        _checking ? 'Checking…' : 'View payment status',
                      ),
                    )
                  else if (!confirmed)
                    FilledButton(
                      onPressed: _checking || _opening ? null : _load,
                      child: Text(
                        _checking ? 'Checking…' : 'Check payment status',
                      ),
                    ),
                  if (confirmed)
                    FilledButton(
                      onPressed: () => context.go('/tickets/${booking!.id}'),
                      child: const Text('View ticket'),
                    ),
                  TextButton(
                    onPressed: () => context.go('/tickets'),
                    child: const Text('My bookings'),
                  ),
                  if (status == 'review_required' || status == 'expired')
                    TextButton(
                      onPressed: () => context.push('/tickets/${booking!.id}'),
                      child: const Text('Booking details & support'),
                    ),
                ],
              ),
      ),
    );
  }
}
