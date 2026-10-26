import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/widgets/legal_links.dart';
import '../../core/widgets/support_links.dart';
import '../auth/application/auth_controller.dart';
import '../startup/application/startup_controller.dart';
import 'account_surface.dart';
import 'biometric_login_tile.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passenger = ref.watch(authControllerProvider).passenger;
    final config = ref.watch(mobileAppConfigProvider);
    final name = passenger?.fullName.trim();
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 70,
        titleSpacing: 20,
        title: const Text(
          'Account',
          style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800),
        ),
      ),
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [AppColors.background, Color(0xFFEDF4EF)],
          ),
        ),
        child: SafeArea(
          top: false,
          child: Align(
            alignment: Alignment.topCenter,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 760),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                children: [
                  _ProfileBanner(
                    name: name == null || name.isEmpty ? 'Traveller' : name,
                    mobile: passenger?.mobile ?? '',
                  ),
                  const SizedBox(height: 20),
                  const BiometricLoginTile(),
                  _AccountSection(
                    title: 'Your account',
                    children: [
                      _tile(
                        Icons.person_outline_rounded,
                        'Profile',
                        'Personal info & CNIC',
                        onTap: passenger == null
                            ? null
                            : () => context.push('/profile/edit'),
                      ),
                      _tile(
                        Icons.people_outline_rounded,
                        'Saved passengers',
                        'Add details for faster checkout',
                        onTap: () => context.push('/profile/saved-passengers'),
                      ),
                      _tile(
                        Icons.account_balance_wallet_outlined,
                        'Wallet',
                        'Travel Points, balance & rewards',
                        onTap: () => context.push('/wallet'),
                      ),
                      _tile(
                        Icons.confirmation_number_outlined,
                        'My trips',
                        'Upcoming and past journeys',
                        onTap: () => context.go('/tickets'),
                      ),
                      if (config?.features.notifications == true)
                        _tile(
                          Icons.notifications_none_rounded,
                          'Alerts',
                          'Booking and service updates',
                          onTap: () => context.go('/notifications'),
                        ),
                    ],
                  ),
                  _AccountSection(
                    title: 'Here to help',
                    children: [
                      _tile(
                        Icons.support_agent_rounded,
                        'Help & support',
                        config?.supportPhone ?? 'Contact Kainat Travels',
                        onTap: () => SupportLinks.open(
                          context,
                          config?.supportPhone == null
                              ? null
                              : Uri.parse('tel:${config!.supportPhone}'),
                        ),
                      ),
                      if (config?.supportWhatsapp != null)
                        _tile(
                          Icons.chat_outlined,
                          'WhatsApp support',
                          config!.supportWhatsapp!,
                          onTap: () => SupportLinks.open(
                            context,
                            Uri.parse(
                              'https://wa.me/${config.supportWhatsapp!.replaceAll(RegExp(r'\D'), '')}',
                            ),
                          ),
                        ),
                      if (config?.supportEmail != null)
                        _tile(
                          Icons.email_outlined,
                          'Email support',
                          config!.supportEmail!,
                          onTap: () => SupportLinks.open(
                            context,
                            Uri(scheme: 'mailto', path: config.supportEmail!),
                          ),
                        ),
                    ],
                  ),
                  _AccountSection(
                    title: 'Information',
                    children: [
                      _tile(
                        Icons.privacy_tip_outlined,
                        LegalDocument.privacy.label,
                        'How we collect and use your information',
                        onTap: () => LegalDocument.privacy.open(context),
                      ),
                      _tile(
                        Icons.description_outlined,
                        LegalDocument.terms.label,
                        'Terms for travelling with Kainat Travels',
                        onTap: () => LegalDocument.terms.open(context),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.primaryDeep,
                      backgroundColor: Colors.white.withValues(alpha: 0.65),
                      side: const BorderSide(color: Color(0xFFC7DACE)),
                      minimumSize: const Size.fromHeight(52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: ref.watch(authControllerProvider).isLoading
                        ? null
                        : () async {
                            try {
                              await ref
                                  .read(authControllerProvider.notifier)
                                  .logout();
                              if (context.mounted) context.go('/login');
                            } catch (_) {
                              if (!context.mounted) return;
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text(
                                    'Unable to sign out. Please try again.',
                                  ),
                                ),
                              );
                            }
                          },
                    icon: const Icon(Icons.logout_rounded, size: 20),
                    label: const Text('Sign out'),
                  ),
                  if (passenger != null)
                    TextButton.icon(
                      onPressed: () => context.push('/profile/delete-account'),
                      style: TextButton.styleFrom(
                        foregroundColor: Theme.of(context).colorScheme.error,
                      ),
                      icon: const Icon(Icons.person_remove_outlined, size: 20),
                      label: const Text('Delete account'),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _tile(
    IconData icon,
    String title,
    String subtitle, {
    VoidCallback? onTap,
  }) => AccountSurface(
    onTap: onTap,
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
    child: Row(
      children: [
        AccountIconBadge(icon),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  color: AppColors.foreground,
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  height: 1.3,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: const TextStyle(
                  color: Color(0xFF53685B),
                  fontSize: 12,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
        if (onTap != null) ...[
          const SizedBox(width: 10),
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: const Color(0xFFEBF2ED),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE1EBE4)),
            ),
            child: const Icon(
              Icons.chevron_right_rounded,
              size: 20,
              color: Color(0xFF62816D),
            ),
          ),
        ],
      ],
    ),
  );
}

class _AccountSection extends StatelessWidget {
  const _AccountSection({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(4, 28, 4, 12),
        child: Semantics(
          header: true,
          child: Text(
            title.toUpperCase(),
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.5,
            ),
          ),
        ),
      ),
      for (var index = 0; index < children.length; index++) ...[
        if (index > 0) const SizedBox(height: 14),
        children[index],
      ],
    ],
  );
}

class _ProfileBanner extends StatelessWidget {
  const _ProfileBanner({required this.name, required this.mobile});

  final String name;
  final String mobile;

  @override
  Widget build(BuildContext context) => AccountSurface(
    gradient: AppGradients.ticket,
    edgeColor: const Color(0xFF034126),
    borderColor: const Color(0xFF4AA365),
    padding: EdgeInsets.zero,
    child: Stack(
      children: [
        Positioned(right: -30, top: -65, child: _ring(180)),
        Positioned(right: 48, bottom: -100, child: _ring(180)),
        Padding(
          padding: const EdgeInsets.all(22),
          child: Row(
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      Colors.white.withValues(alpha: 0.28),
                      Colors.white.withValues(alpha: 0.06),
                    ],
                  ),
                  border: Border.all(color: AppColors.gold, width: 1.5),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x33002E19),
                      blurRadius: 8,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                alignment: Alignment.center,
                child: Text(
                  name.characters.first.toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'KAINAT TRAVELS',
                      style: TextStyle(
                        color: Color(0xFFD4E9D9),
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 1.6,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      name,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        height: 1.2,
                      ),
                    ),
                    if (mobile.isNotEmpty) ...[
                      const SizedBox(height: 5),
                      Text(
                        mobile,
                        style: const TextStyle(
                          color: Color(0xFFD4E9D9),
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    ),
  );

  Widget _ring(double size) => ExcludeSemantics(
    child: Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white.withValues(alpha: 0.10)),
      ),
    ),
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
    final session = ref.read(passengerSessionProvider);
    setState(() => _loading = true);
    try {
      final passenger = await ref.read(authRepositoryProvider).updateProfile({
        'full_name': _name.text.trim(),
        'email': _email.text.trim().isEmpty ? null : _email.text.trim(),
        'cnic': _cnic.text.trim(),
        'gender': _gender,
      });
      if (!mounted || ref.read(passengerSessionProvider) != session) return;
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
