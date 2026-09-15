import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_spacing.dart';
import '../../core/widgets/legal_links.dart';
import '../auth/application/auth_controller.dart';
import '../notifications/notifications_screen.dart';
import '../travel/application/travel_providers.dart';

class DeleteAccountScreen extends ConsumerStatefulWidget {
  const DeleteAccountScreen({super.key});

  @override
  ConsumerState<DeleteAccountScreen> createState() =>
      _DeleteAccountScreenState();
}

class _DeleteAccountScreenState extends ConsumerState<DeleteAccountScreen> {
  final _form = GlobalKey<FormState>();
  final _password = TextEditingController();
  bool _acknowledged = false;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final signedIn = ref.watch(authControllerProvider).isAuthenticated;
    final danger = Theme.of(context).colorScheme.error;
    return PopScope(
      canPop: !_busy,
      child: Scaffold(
        appBar: AppBar(title: const Text('Delete account')),
        body: SafeArea(
          child: Form(
            key: _form,
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.md),
              children: [
                Icon(Icons.person_remove_outlined, size: 48, color: danger),
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Permanently delete your mobile account?',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: AppSpacing.md),
                const Text(
                  'Your mobile login, saved passengers and booking drafts will be removed. '
                  'You will be signed out on all devices. This cannot be undone.',
                ),
                const SizedBox(height: AppSpacing.md),
                const Text(
                  'Existing trips are not cancelled and payments are not refunded. '
                  'Customer details, tickets, payment history and loyalty records '
                  'remain in the company’s booking system. Save your ticket references '
                  'and contact support for help with trips or retained data.',
                ),
                TextButton(
                  onPressed: _busy
                      ? null
                      : () => LegalDocument.privacy.open(context),
                  child: const Text('Privacy policy'),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _password,
                  enabled: !_busy && signedIn,
                  obscureText: true,
                  autocorrect: false,
                  enableSuggestions: false,
                  decoration: const InputDecoration(
                    labelText: 'Current password',
                  ),
                  validator: (value) => value == null || value.isEmpty
                      ? 'Enter your current password.'
                      : null,
                ),
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _acknowledged,
                  onChanged: _busy || !signedIn
                      ? null
                      : (value) =>
                            setState(() => _acknowledged = value ?? false),
                  title: const Text(
                    'I understand what will be deleted and retained.',
                  ),
                  controlAffinity: ListTileControlAffinity.leading,
                ),
                if (_error != null)
                  Semantics(
                    liveRegion: true,
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.md),
                      child: Text(_error!, style: TextStyle(color: danger)),
                    ),
                  ),
                if (!signedIn && !_busy)
                  const Text('Please sign in to delete your account.'),
                FilledButton(
                  style: FilledButton.styleFrom(backgroundColor: danger),
                  onPressed: _busy || !_acknowledged || !signedIn
                      ? null
                      : _confirm,
                  child: Text(_busy ? 'Deleting…' : 'Delete account'),
                ),
                TextButton(
                  onPressed: _busy ? null : () => context.go('/profile'),
                  child: const Text('Keep my account'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _confirm() async {
    if (_busy || !_form.currentState!.validate()) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete your account?'),
        content: const Text(
          'This permanently removes your mobile account. '
          'Your existing bookings and payments remain in the company’s system.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep account'),
          ),
          TextButton(
            style: TextButton.styleFrom(
              foregroundColor: Theme.of(context).colorScheme.error,
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Permanently delete'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted || _busy) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(authControllerProvider.notifier)
          .deleteAccount(_password.text);
      if (!mounted) return;
      _password.clear();
      ref.invalidate(savedPassengersProvider);
      ref.invalidate(bookingsProvider);
      ref.invalidate(loyaltyWalletProvider);
      ref.invalidate(notificationsProvider);
      ref.invalidate(bookingFlowProvider);
      context.go('/login');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Your mobile account has been deleted.')),
      );
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}
