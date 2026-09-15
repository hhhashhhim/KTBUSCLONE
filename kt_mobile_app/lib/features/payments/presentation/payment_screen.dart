import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/config/app_environment.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../travel/application/travel_providers.dart';
import '../../travel/domain/travel_models.dart';
import '../domain/booking_payment.dart';

final paymentLauncherProvider = Provider<Future<bool> Function(Uri)>((ref) {
  return (uri) => launchUrl(uri, mode: LaunchMode.externalApplication);
});

class PaymentScreen extends ConsumerStatefulWidget {
  const PaymentScreen({
    required this.bookingId,
    this.initialBooking,
    super.key,
  });
  final int bookingId;
  final BookingSummary? initialBooking;

  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen>
    with WidgetsBindingObserver {
  BookingSummary? _booking;
  String? _error;
  bool _checking = false;
  bool _opening = false;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _booking = widget.initialBooking;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _load(verify: _booking?.payment != null);
    });
    _timer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed &&
          _booking?.payment?.status == 'pending') {
        _load(verify: true);
      }
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _booking?.payment != null) {
      _load(verify: true);
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  Future<void> _load({bool verify = false}) async {
    if (_checking) return;
    setState(() {
      _checking = true;
      _error = null;
    });
    try {
      final repository = ref.read(travelRepositoryProvider);
      final booking = verify
          ? await repository.refreshPayment(widget.bookingId)
          : await repository.booking(widget.bookingId);
      if (!mounted) return;
      setState(() => _booking = booking);
      ref.invalidate(bookingsProvider);
      ref.invalidate(loyaltyWalletProvider);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  Future<void> _openCheckout() async {
    if (_opening) return;
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
    setState(() {
      _opening = true;
      _error = null;
    });
    try {
      final opened = await ref.read(paymentLauncherProvider)(uri);
      if (!opened && mounted) {
        setState(() => _error = 'Could not open checkout. Please try again.');
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open checkout. Please try again.');
      }
    } finally {
      if (mounted) setState(() => _opening = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final booking = _booking;
    final payment = booking?.payment;
    final confirmed =
        booking?.status == 'confirmed' && booking?.paymentStatus == 'paid';
    final status = payment?.status;
    final title = confirmed
        ? 'Payment received'
        : switch (status) {
            'expired' => 'Reservation expired',
            'review_required' => 'Payment needs review',
            'pending' => 'Complete your payment',
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
              'Complete checkout with your payment provider, then return here. Your tickets will be confirmed after payment verification.',
            _ => 'Open your booking to see its payment instructions.',
          };
    return Scaffold(
      appBar: const AppPageHeader(title: 'Payment'),
      body: booking == null && _checking
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
                          if (status == 'pending')
                            Text(
                              'Checkout closes at ${DateFormat('h:mm a').format(payment.expiresAt)}',
                            ),
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
                if (!confirmed &&
                    status == 'pending' &&
                    payment?.checkoutUrl != null &&
                    payment!.expiresAt.isAfter(DateTime.now()))
                  FilledButton(
                    onPressed: _opening || _checking ? null : _openCheckout,
                    child: Text(
                      _opening ? 'Opening checkout…' : 'Open secure checkout',
                    ),
                  ),
                if (!confirmed)
                  OutlinedButton(
                    onPressed: _checking
                        ? null
                        : () => _load(verify: payment != null),
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
    );
  }
}
