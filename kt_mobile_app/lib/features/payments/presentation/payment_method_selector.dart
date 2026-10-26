import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/app_widgets.dart';
import '../domain/booking_payment.dart';

class PaymentMethodSelector extends StatelessWidget {
  const PaymentMethodSelector({
    super.key,
    required this.availableMethods,
    required this.environment,
    required this.selectedMethod,
    required this.onSelected,
    required this.onRetry,
    this.previewOnly = false,
  });

  final List<String> availableMethods;
  final String environment;
  final String? selectedMethod;
  final ValueChanged<String> onSelected;
  final VoidCallback onRetry;
  final bool previewOnly;

  @override
  Widget build(BuildContext context) {
    final sandbox = environment == 'sandbox';
    // Show supported gateways even before setup, but only the API can
    // enable a method for this quote.
    final available = availableMethods.where(isSelectablePaymentMethod).toSet();
    final methods = <String>{...available, 'bank_alfalah'};
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (sandbox || previewOnly) ...[
          AppCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  previewOnly
                      ? '${sandbox ? 'Sandbox' : 'Live'} preview · No payments'
                      : 'Sandbox · Test payments',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
                if (previewOnly) ...[
                  const SizedBox(height: 6),
                  const Text(
                    'You can review the payment methods. Booking and payment submission are disabled.',
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 10),
        ],
        for (final method in methods)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: _PaymentMethodCard(
              method: method,
              sandbox: sandbox,
              selected: available.contains(method) && selectedMethod == method,
              onTap: available.contains(method)
                  ? () => onSelected(method)
                  : null,
            ),
          ),
        if (available.isEmpty)
          AppCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  sandbox
                      ? 'Sandbox checkout is not available yet.'
                      : 'No payment method is currently available.',
                ),
                TextButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh_rounded),
                  label: const Text('Check again'),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _PaymentMethodCard extends StatelessWidget {
  const _PaymentMethodCard({
    required this.method,
    required this.sandbox,
    required this.selected,
    required this.onTap,
  });

  final String method;
  final bool sandbox;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null;
    return Semantics(
      enabled: enabled,
      selected: selected,
      child: AnimatedContainer(
        duration: MediaQuery.disableAnimationsOf(context)
            ? Duration.zero
            : const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          boxShadow: [
            BoxShadow(
              color: selected
                  ? const Color(0x1C1A6137)
                  : const Color(0x0D244F33),
              blurRadius: selected ? 20 : 10,
              offset: const Offset(0, 8),
            ),
            BoxShadow(
              color: selected
                  ? const Color(0xFFBBD5C3)
                  : const Color(0xFFDCE5DE),
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Material(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(22),
          clipBehavior: Clip.antiAlias,
          child: Ink(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: selected
                    ? [Colors.white, const Color(0xFFEDF7EF)]
                    : [Colors.white, const Color(0xFFF7FAF8)],
              ),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(
                color: selected
                    ? const Color(0xFF4A9C65)
                    : const Color(0xFFE1E9E3),
                width: 1.4,
              ),
            ),
            child: InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(22),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 18,
                ),
                child: Row(
                  children: [
                    _PaymentEmblem(
                      card: method == 'bank_alfalah',
                      enabled: enabled,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            paymentMethodLabel(method),
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: enabled
                                  ? AppColors.foreground
                                  : AppColors.muted,
                            ),
                          ),
                          if (method == 'bank_alfalah') ...[
                            const SizedBox(height: 4),
                            Text(
                              'Pay securely through Bank Alfalah checkout.',
                              style: const TextStyle(
                                fontSize: 12,
                                color: Color(0xFF60766A),
                                height: 1.5,
                              ),
                            ),
                          ],
                          if (!enabled) ...[
                            const SizedBox(height: 4),
                            Text(
                              sandbox
                                  ? 'Currently unavailable in sandbox'
                                  : 'Currently unavailable',
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.muted,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    if (enabled)
                      Container(
                        width: 23,
                        height: 23,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: selected
                              ? AppColors.primaryDark
                              : Colors.transparent,
                          border: Border.all(
                            color: selected
                                ? AppColors.primaryDark
                                : AppColors.border,
                            width: 2,
                          ),
                        ),
                        child: selected
                            ? const Icon(
                                Icons.check_rounded,
                                color: Colors.white,
                                size: 15,
                              )
                            : null,
                      )
                    else
                      const Icon(
                        Icons.lock_outline_rounded,
                        size: 18,
                        color: AppColors.muted,
                      ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Decorative perspective stays separate from the readable method details.
class _PaymentEmblem extends StatelessWidget {
  const _PaymentEmblem({required this.card, required this.enabled});
  final bool card;
  final bool enabled;

  @override
  Widget build(BuildContext context) => ExcludeSemantics(
    child: SizedBox(
      width: 64,
      height: 60,
      child: card
          ? Stack(
              alignment: Alignment.center,
              children: [
                Transform.translate(
                  offset: const Offset(4, -5),
                  child: Transform.rotate(
                    angle: .18,
                    child: Container(
                      width: 51,
                      height: 35,
                      decoration: BoxDecoration(
                        color: enabled
                            ? const Color(0xFFD7BB71)
                            : const Color(0xFFCAD2CC),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFF4E7BE)),
                      ),
                    ),
                  ),
                ),
                Transform(
                  alignment: Alignment.center,
                  transform: Matrix4.identity()
                    ..setEntry(3, 2, .001)
                    ..rotateX(-.16)
                    ..rotateY(-.2)
                    ..rotateZ(-.13),
                  child: Container(
                    width: 55,
                    height: 38,
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: enabled
                            ? [const Color(0xFF46A76D), const Color(0xFF075C34)]
                            : [
                                const Color(0xFF9EAFA3),
                                const Color(0xFF62776A),
                              ],
                      ),
                      border: Border.all(color: const Color(0xFF91C6A2)),
                      boxShadow: const [
                        BoxShadow(
                          color: Color(0xFF064427),
                          offset: Offset(0, 3),
                        ),
                        BoxShadow(
                          color: Color(0x40224B31),
                          blurRadius: 9,
                          offset: Offset(2, 7),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          width: 12,
                          height: 9,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(2),
                            gradient: const LinearGradient(
                              colors: [Color(0xFFF3DDA0), Color(0xFFBE963F)],
                            ),
                          ),
                        ),
                        Row(
                          children: [
                            for (var i = 0; i < 4; i++)
                              Container(
                                width: 6,
                                height: 2,
                                margin: const EdgeInsets.only(right: 3),
                                color: const Color(0xFFBEE1C9),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            )
          : Container(
              margin: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(15),
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFF4FBF5), Color(0xFFD4E7DA)],
                ),
                border: Border.all(color: Colors.white, width: 2),
                boxShadow: const [
                  BoxShadow(color: Color(0xFFD0DDD4), offset: Offset(0, 3)),
                ],
              ),
              child: Icon(
                Icons.payments_outlined,
                color: enabled ? AppColors.primaryDark : AppColors.muted,
              ),
            ),
    ),
  );
}
