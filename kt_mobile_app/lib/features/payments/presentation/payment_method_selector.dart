import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_radius.dart';
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
    final methods = <String>{...availableMethods, 'jazzcash', 'bank_alfalah'};
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
              selected:
                  availableMethods.contains(method) && selectedMethod == method,
              onTap: availableMethods.contains(method)
                  ? () => onSelected(method)
                  : null,
            ),
          ),
        if (availableMethods.isEmpty)
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
      child: AppCard(
        padding: EdgeInsets.zero,
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: AppRadius.card,
            border: selected
                ? Border.all(color: AppColors.primary, width: 2)
                : null,
          ),
          child: Row(
            children: [
              CircleAvatar(
                backgroundColor: selected
                    ? AppColors.primary
                    : enabled
                    ? AppColors.primarySoft
                    : AppColors.surfaceAlt,
                foregroundColor: selected
                    ? Colors.white
                    : enabled
                    ? AppColors.primary
                    : AppColors.muted,
                child: Icon(switch (method) {
                  'bank_alfalah' => Icons.credit_card_rounded,
                  'jazzcash' => Icons.phone_android_rounded,
                  _ => Icons.payments_outlined,
                }),
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
                        fontWeight: FontWeight.w700,
                        color: enabled ? AppColors.foreground : AppColors.muted,
                      ),
                    ),
                    if (method == 'jazzcash' || method == 'bank_alfalah') ...[
                      const SizedBox(height: 4),
                      Text(
                        method == 'jazzcash'
                            ? 'Pay from your JazzCash mobile account.'
                            : 'Pay securely through Bank Alfalah checkout.',
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.muted,
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
                  width: 18,
                  height: 18,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: selected ? AppColors.primary : Colors.transparent,
                    border: Border.all(
                      color: selected ? AppColors.primary : AppColors.border,
                      width: 2,
                    ),
                  ),
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
    );
  }
}
