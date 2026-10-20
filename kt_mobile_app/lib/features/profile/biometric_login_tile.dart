import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/widgets/app_widgets.dart';
import '../auth/application/auth_controller.dart';

class BiometricLoginTile extends ConsumerStatefulWidget {
  const BiometricLoginTile({super.key});

  @override
  ConsumerState<BiometricLoginTile> createState() => _BiometricLoginTileState();
}

class _BiometricLoginTileState extends ConsumerState<BiometricLoginTile> {
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final status = ref.watch(biometricStatusProvider);
    final value = status.value;
    final verified =
        ref.watch(authControllerProvider).passenger?.mobileVerified == true;
    return AppCard(
      child: SwitchListTile(
        contentPadding: EdgeInsets.zero,
        secondary: const Icon(Icons.fingerprint_rounded),
        title: Text(value?.label ?? 'Face ID / fingerprint'),
        subtitle: Text(
          _busy
              ? 'Updating quick login…'
              : value?.enabled == true
              ? 'Quick login is on for this phone.'
              : !verified
              ? 'Verify your mobile number to enable quick login.'
              : value?.available == true
                ? 'Use this phone’s biometrics for faster sign-in.'
              : 'Set up supported biometrics in your phone settings. Password sign-in remains available.',
        ),
        value: value?.enabled == true,
        onChanged:
            _busy ||
                value == null ||
                (!value.enabled && (!value.available || !verified))
            ? null
            : _change,
      ),
    );
  }

  Future<void> _change(bool enable) async {
    setState(() => _busy = true);
    try {
      final repository = ref.read(biometricLoginRepositoryProvider);
      if (enable) {
        final password = await showDialog<String>(
          context: context,
          builder: (_) => const _ConfirmPasswordDialog(),
        );
        if (password == null || !mounted) return;
        await repository.enable(password);
      } else {
        await repository.disable();
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              enable
                  ? 'Quick login enabled on this phone.'
                  : 'Quick login disabled.',
            ),
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      ref.invalidate(biometricStatusProvider);
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _ConfirmPasswordDialog extends StatefulWidget {
  const _ConfirmPasswordDialog();
  @override
  State<_ConfirmPasswordDialog> createState() => _ConfirmPasswordDialogState();
}

class _ConfirmPasswordDialogState extends State<_ConfirmPasswordDialog> {
  final _password = TextEditingController();
  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: const Text('Enable quick login'),
    content: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Confirm your account password, then use the biometrics enrolled on this phone.',
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _password,
          obscureText: true,
          autocorrect: false,
          enableSuggestions: false,
          autofocus: true,
          decoration: const InputDecoration(labelText: 'Password'),
          onChanged: (_) => setState(() {}),
          onSubmitted: (text) {
            if (text.isNotEmpty) Navigator.pop(context, text);
          },
        ),
      ],
    ),
    actions: [
      TextButton(
        onPressed: () => Navigator.pop(context),
        child: const Text('Cancel'),
      ),
      FilledButton(
        onPressed: _password.text.isEmpty
            ? null
            : () => Navigator.pop(context, _password.text),
        child: const Text('Continue'),
      ),
    ],
  );
}
