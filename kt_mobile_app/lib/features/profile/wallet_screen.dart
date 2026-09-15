import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/app_widgets.dart';
import '../travel/application/travel_providers.dart';

class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final wallet = ref.watch(loyaltyWalletProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Wallet')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(loyaltyWalletProvider.future),
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            wallet.when(
              loading: () => const Padding(
                padding: EdgeInsets.only(top: 100),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => AppStateView(
                icon: Icons.account_balance_wallet_outlined,
                title: 'Could not load points',
                message: error.toString(),
                actionLabel: 'Try again',
                onAction: () => ref.invalidate(loyaltyWalletProvider),
              ),
              data: (data) {
                if (!data.active) {
                  return AppStateView(
                    icon: Icons.stars_outlined,
                    title: 'No active loyalty card',
                    message:
                        data.message ??
                        'Ask a Kainat Travels branch to link your loyalty card.',
                  );
                }
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.lg),
                      decoration: const BoxDecoration(
                        gradient: AppGradients.ticket,
                        borderRadius: AppRadius.card,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            data.category ?? 'Kainat Loyalty',
                            style: const TextStyle(color: Colors.white70),
                          ),
                          const SizedBox(height: 14),
                          Text(
                            '${data.pointsAvailable}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 38,
                              height: 1,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                          const SizedBox(height: 5),
                          const Text(
                            'available points',
                            style: TextStyle(color: Colors.white70),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Use your points',
                            style: TextStyle(fontWeight: FontWeight.w800),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            data.redemptionLabel,
                            style: const TextStyle(color: AppColors.muted),
                          ),
                          if (data.expiresAt != null) ...[
                            const SizedBox(height: 10),
                            Text(
                              'Card expiry: ${data.expiresAt}',
                              style: const TextStyle(
                                color: AppColors.muted,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    const Text(
                      'Choose how many points to use at checkout. The server validates your balance and recalculates the discount before a booking is created.',
                      style: TextStyle(color: AppColors.muted, fontSize: 12),
                    ),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
