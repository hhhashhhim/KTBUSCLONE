import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/legal_links.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passenger = ref.watch(authControllerProvider).passenger;
    final config = ref.watch(mobileAppConfigProvider);
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 70,
        title: const Text('Account'),
        bottom: const PreferredSize(
          preferredSize: Size.fromHeight(1),
          child: Divider(height: 1, color: AppColors.border),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.md),
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: const BoxDecoration(
              gradient: AppGradients.ticket,
              borderRadius: AppRadius.card,
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(2),
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.fromBorderSide(
                      BorderSide(color: AppColors.gold, width: 1.5),
                    ),
                  ),
                  child: CircleAvatar(
                    radius: 30,
                    backgroundColor: Colors.white.withValues(alpha: 0.22),
                    child: Text(
                      (passenger?.fullName ?? 'T').characters.first
                          .toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        passenger?.fullName ?? 'Traveller',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      Text(
                        passenger?.mobile ?? '',
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          AppCard(
            child: Column(
              children: [
                _tile(
                  Icons.person_outline_rounded,
                  'Profile',
                  'Personal info & CNIC',
                  onTap: passenger == null
                      ? null
                      : () => context.push('/profile/edit'),
                ),
                const Divider(height: 1),
                _tile(
                  Icons.people_outline_rounded,
                  'Saved passengers',
                  'Add details for faster checkout',
                  onTap: () => context.push('/profile/saved-passengers'),
                ),
                const Divider(height: 1),
                _tile(
                  Icons.account_balance_wallet_outlined,
                  'Wallet',
                  'Kainat Points, balance & rewards',
                  onTap: () => context.push('/wallet'),
                ),
                const Divider(height: 1),
                _tile(
                  Icons.confirmation_number_outlined,
                  'My trips',
                  'Upcoming and past journeys',
                  onTap: () => context.go('/tickets'),
                ),
                if (config?.features.notifications == true) ...[
                  const Divider(height: 1),
                  _tile(
                    Icons.notifications_none_rounded,
                    'Alerts',
                    'Booking and service updates',
                    onTap: () => context.go('/notifications'),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          AppCard(
            child: Column(
              children: [
                _tile(
                  Icons.support_agent_rounded,
                  'Help & support',
                  config?.supportPhone ?? 'Contact details not configured',
                  onTap: config?.supportPhone == null
                      ? null
                      : () =>
                            launchUrl(Uri.parse('tel:${config!.supportPhone}')),
                ),
                if (config?.supportWhatsapp != null) ...[
                  const Divider(),
                  _tile(
                    Icons.chat_outlined,
                    'WhatsApp support',
                    config!.supportWhatsapp!,
                    onTap: () => launchUrl(
                      Uri.parse(
                        'https://wa.me/${config.supportWhatsapp!.replaceAll(RegExp(r'\D'), '')}',
                      ),
                      mode: LaunchMode.externalApplication,
                    ),
                  ),
                ],
                if (config?.supportEmail != null) ...[
                  const Divider(),
                  _tile(
                    Icons.email_outlined,
                    'Email support',
                    config!.supportEmail!,
                    onTap: () =>
                        launchUrl(Uri.parse('mailto:${config.supportEmail}')),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          AppCard(
            child: Column(
              children: [
                _tile(
                  Icons.privacy_tip_outlined,
                  LegalDocument.privacy.label,
                  'How we collect and use your information',
                  onTap: () => LegalDocument.privacy.open(context),
                ),
                const Divider(height: 1),
                _tile(
                  Icons.description_outlined,
                  LegalDocument.terms.label,
                  'Terms for travelling with Kainat Travels',
                  onTap: () => LegalDocument.terms.open(context),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          OutlinedButton.icon(
            onPressed: () async {
              await ref.read(authControllerProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
            icon: const Icon(Icons.logout_rounded),
            label: const Text('Sign out'),
          ),
          if (passenger != null)
            TextButton.icon(
              onPressed: () => context.push('/profile/delete-account'),
              style: TextButton.styleFrom(
                foregroundColor: Theme.of(context).colorScheme.error,
              ),
              icon: const Icon(Icons.person_remove_outlined),
              label: const Text('Delete account'),
            ),
        ],
      ),
    );
  }

  Widget _tile(
    IconData icon,
    String title,
    String subtitle, {
    VoidCallback? onTap,
  }) => ListTile(
    contentPadding: EdgeInsets.zero,
    leading: CircleAvatar(
      backgroundColor: AppColors.primarySoft,
      foregroundColor: AppColors.primary,
      child: Icon(icon),
    ),
    title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
    subtitle: Text(subtitle),
    trailing: onTap == null ? null : const Icon(Icons.chevron_right_rounded),
    onTap: onTap,
  );
}

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  late final TextEditingController _cnic;
  String? _gender;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    final passenger = ref.read(authControllerProvider).passenger;
    _name = TextEditingController(text: passenger?.fullName);
    _email = TextEditingController(text: passenger?.email);
    _cnic = TextEditingController(text: passenger?.cnic);
    _gender = passenger?.gender;
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _cnic.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Personal details')),
    body: SafeArea(
      child: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            TextFormField(
              controller: _name,
              decoration: const InputDecoration(
                labelText: 'Full name',
                prefixIcon: Icon(Icons.person_outline_rounded),
              ),
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Full name is required.'
                  : null,
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                labelText: 'Email (optional)',
                prefixIcon: Icon(Icons.email_outlined),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _cnic,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'CNIC',
                prefixIcon: Icon(Icons.badge_outlined),
              ),
              validator: (value) =>
                  value == null ||
                      value.replaceAll(RegExp(r'\D'), '').length != 13
                  ? 'Enter a valid 13-digit CNIC.'
                  : null,
            ),
            const SizedBox(height: AppSpacing.md),
            DropdownButtonFormField<String>(
              initialValue: _gender,
              decoration: const InputDecoration(
                labelText: 'Gender (optional)',
                prefixIcon: Icon(Icons.person_pin_outlined),
              ),
              items: const [
                DropdownMenuItem(value: 'male', child: Text('Male')),
                DropdownMenuItem(value: 'female', child: Text('Female')),
                DropdownMenuItem(value: 'other', child: Text('Other')),
              ],
              onChanged: (value) => _gender = value,
            ),
            const SizedBox(height: AppSpacing.lg),
            FilledButton(
              onPressed: _loading ? null : _save,
              child: Text(_loading ? 'Saving…' : 'Save changes'),
            ),
          ],
        ),
      ),
    ),
  );

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final passenger = await ref.read(authRepositoryProvider).updateProfile({
        'full_name': _name.text.trim(),
        'email': _email.text.trim().isEmpty ? null : _email.text.trim(),
        'cnic': _cnic.text.trim(),
        'gender': _gender,
      });
      ref.read(authControllerProvider.notifier).replacePassenger(passenger);
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Profile updated.')));
        context.pop();
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}
