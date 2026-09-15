import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../../core/widgets/legal_links.dart';
import '../application/auth_controller.dart';
import '../domain/passenger.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _mobile = TextEditingController();
  final _password = TextEditingController();
  bool _showPassword = false;

  @override
  void dispose() {
    _mobile.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    return _AuthScaffold(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Center(child: AppLogo(height: 58)),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Book intercity buses across Pakistan',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: AppSpacing.xl),
            Text(
              'Welcome back',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: AppSpacing.xxs),
            Text(
              'Sign in to book buses and manage your trips.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: AppSpacing.lg),
            TextFormField(
              controller: _mobile,
              keyboardType: TextInputType.phone,
              autofillHints: const [AutofillHints.telephoneNumber],
              decoration: const InputDecoration(
                labelText: 'Mobile number',
                hintText: '03XX XXXXXXX',
                prefixIcon: Icon(Icons.phone_rounded),
              ),
              validator: (value) =>
                  _required(value, 'Enter your mobile number.'),
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _password,
              obscureText: !_showPassword,
              autofillHints: const [AutofillHints.password],
              decoration: InputDecoration(
                labelText: 'Password',
                prefixIcon: const Icon(Icons.lock_rounded),
                suffixIcon: IconButton(
                  onPressed: () =>
                      setState(() => _showPassword = !_showPassword),
                  icon: Icon(
                    _showPassword
                        ? Icons.visibility_off_rounded
                        : Icons.visibility_rounded,
                  ),
                ),
              ),
              validator: (value) => _required(value, 'Enter your password.'),
            ),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: () => context.push('/forgot-password'),
                child: const Text('Forgot password?'),
              ),
            ),
            if (auth.error != null) ...[
              _InlineError(auth.error!),
              const SizedBox(height: AppSpacing.sm),
            ],
            FilledButton(
              onPressed: auth.isLoading ? null : _submit,
              child: Text(auth.isLoading ? 'Signing in…' : 'Sign in'),
            ),
            const SizedBox(height: AppSpacing.lg),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  'New to Kainat Travels?',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                TextButton(
                  onPressed: () => context.push('/register'),
                  child: const Text('Create account'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final success = await ref
        .read(authControllerProvider.notifier)
        .login(_mobile.text.trim(), _password.text);
    if (success && mounted) {
      final verified =
          ref.read(authControllerProvider).passenger?.mobileVerified == true;
      context.go(verified ? '/home' : '/verify-otp');
    }
  }
}

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _mobile = TextEditingController();
  final _email = TextEditingController();
  final _cnic = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _accepted = false;

  @override
  void dispose() {
    for (final controller in [
      _name,
      _mobile,
      _email,
      _cnic,
      _password,
      _confirmation,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    return _AuthScaffold(
      appBar: AppBar(),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Create account',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Sign up to book buses and manage your trips.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: AppSpacing.lg),
            _field(_name, 'Full name', Icons.person_rounded),
            _field(
              _mobile,
              'Mobile number',
              Icons.phone_rounded,
              type: TextInputType.phone,
            ),
            _field(
              _email,
              'Email (optional)',
              Icons.email_rounded,
              type: TextInputType.emailAddress,
              optional: true,
            ),
            _field(_cnic, 'CNIC', Icons.badge_rounded, hint: '#####-#######-#'),
            _field(_password, 'Password', Icons.lock_rounded, obscure: true),
            _field(
              _confirmation,
              'Confirm password',
              Icons.lock_outline_rounded,
              obscure: true,
            ),
            const LegalLinks(),
            CheckboxListTile(
              value: _accepted,
              contentPadding: EdgeInsets.zero,
              controlAffinity: ListTileControlAffinity.leading,
              onChanged: (value) => setState(() => _accepted = value == true),
              title: const Text('I accept the terms and privacy policy.'),
            ),
            if (auth.error != null) ...[
              _InlineError(auth.error!),
              const SizedBox(height: AppSpacing.sm),
            ],
            FilledButton(
              onPressed: auth.isLoading ? null : _submit,
              child: Text(
                auth.isLoading ? 'Creating account…' : 'Create account',
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String label,
    IconData icon, {
    TextInputType? type,
    bool obscure = false,
    bool optional = false,
    String? hint,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
    child: TextFormField(
      controller: controller,
      keyboardType: type,
      obscureText: obscure,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        prefixIcon: Icon(icon),
      ),
      validator: optional
          ? null
          : (value) => _required(value, '$label is required.'),
    ),
  );

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_accepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Accept the terms and privacy policy to continue.'),
        ),
      );
      return;
    }
    if (_password.text != _confirmation.text) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Passwords do not match.')));
      return;
    }
    final success = await ref
        .read(authControllerProvider.notifier)
        .register(
          fullName: _name.text.trim(),
          mobile: _mobile.text.trim(),
          email: _email.text.trim().isEmpty ? null : _email.text.trim(),
          cnic: _cnic.text.trim(),
          password: _password.text,
          passwordConfirmation: _confirmation.text,
        );
    if (success && mounted) context.go('/verify-otp');
  }
}

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() =>
      _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _mobile = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) => _AuthScaffold(
    appBar: AppBar(),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Forgot password?',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(
          'Enter your registered mobile number to receive a verification code on WhatsApp.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        const SizedBox(height: AppSpacing.lg),
        TextField(
          controller: _mobile,
          keyboardType: TextInputType.phone,
          decoration: const InputDecoration(
            labelText: 'Mobile number',
            prefixIcon: Icon(Icons.phone_rounded),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        FilledButton(
          onPressed: _loading ? null : _submit,
          child: Text(_loading ? 'Sending…' : 'Send code'),
        ),
      ],
    ),
  );

  Future<void> _submit() async {
    if (_mobile.text.trim().isEmpty) return;
    setState(() => _loading = true);
    try {
      await ref
          .read(authRepositoryProvider)
          .forgotPassword(_mobile.text.trim());
      if (mounted) context.push('/reset-otp', extra: _mobile.text.trim());
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

class ResetOtpScreen extends ConsumerStatefulWidget {
  const ResetOtpScreen({required this.mobile, super.key});

  final String mobile;

  @override
  ConsumerState<ResetOtpScreen> createState() => _ResetOtpScreenState();
}

class _ResetOtpScreenState extends ConsumerState<ResetOtpScreen> {
  final _code = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => _AuthScaffold(
    appBar: AppBar(),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Verify reset code',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(
          'Check WhatsApp for the 6-digit code for ${widget.mobile}.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        const SizedBox(height: AppSpacing.lg),
        TextField(
          controller: _code,
          keyboardType: TextInputType.number,
          maxLength: 6,
          textAlign: TextAlign.center,
          decoration: const InputDecoration(
            counterText: '',
            hintText: '••••••',
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        FilledButton(
          onPressed: _loading ? null : _verify,
          child: Text(_loading ? 'Verifying…' : 'Continue'),
        ),
      ],
    ),
  );

  Future<void> _verify() async {
    if (_code.text.length != 6) return;
    setState(() => _loading = true);
    try {
      final session = await ref
          .read(authRepositoryProvider)
          .verifyPasswordResetOtp(mobile: widget.mobile, code: _code.text);
      if (mounted) context.push('/reset-password', extra: session);
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

class ResetPasswordScreen extends ConsumerStatefulWidget {
  const ResetPasswordScreen({required this.session, super.key});

  final PasswordResetSession session;

  @override
  ConsumerState<ResetPasswordScreen> createState() =>
      _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends ConsumerState<ResetPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => _AuthScaffold(
    appBar: AppBar(),
    child: Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Choose a new password',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: AppSpacing.lg),
          TextFormField(
            controller: _password,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'New password',
              prefixIcon: Icon(Icons.lock_rounded),
            ),
            validator: (value) => value == null || value.length < 8
                ? 'Use at least 8 characters.'
                : null,
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _confirmation,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'Confirm new password',
              prefixIcon: Icon(Icons.lock_outline_rounded),
            ),
            validator: (value) =>
                value != _password.text ? 'Passwords do not match.' : null,
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            onPressed: _loading ? null : _submit,
            child: Text(_loading ? 'Updating…' : 'Update password'),
          ),
        ],
      ),
    ),
  );

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      await ref
          .read(authRepositoryProvider)
          .resetPassword(
            session: widget.session,
            password: _password.text,
            passwordConfirmation: _confirmation.text,
          );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Password updated. Please sign in.')),
        );
        context.go('/login');
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

class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({super.key});

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final _code = TextEditingController();
  bool _loading = false;

  String? _deliveryMessage;

  @override
  void initState() {
    super.initState();
    _deliveryMessage = ref.read(authControllerProvider).otpDeliveryMessage;
  }

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _resend() async {
    setState(() => _loading = true);
    try {
      await ref.read(authRepositoryProvider).resendOtp();
      if (!mounted) return;
      setState(() => _deliveryMessage = null);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Code requested. Please check WhatsApp.')),
      );
    } catch (error) {
      if (mounted) setState(() => _deliveryMessage = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => _AuthScaffold(
    appBar: AppBar(),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Verify your mobile',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(
          'Check WhatsApp for your 6-digit verification code. If it hasn’t arrived, tap Resend code.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        if (_deliveryMessage != null) ...[
          const SizedBox(height: AppSpacing.sm),
          Text(
            _deliveryMessage!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        TextField(
          controller: _code,
          keyboardType: TextInputType.number,
          maxLength: 6,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 24,
            letterSpacing: 12,
            fontWeight: FontWeight.w700,
          ),
          decoration: const InputDecoration(
            counterText: '',
            hintText: '••••••',
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        FilledButton(
          onPressed: _loading ? null : _verify,
          child: Text(_loading ? 'Verifying…' : 'Verify'),
        ),
        TextButton(
          onPressed: _loading ? null : _resend,
          child: const Text('Resend code'),
        ),
      ],
    ),
  );

  Future<void> _verify() async {
    if (_code.text.length != 6) return;
    setState(() => _loading = true);
    try {
      final passenger = await ref
          .read(authRepositoryProvider)
          .verifyOtp(_code.text);
      if (!mounted) return;
      ref.read(authControllerProvider.notifier).replacePassenger(passenger);
      if (mounted) context.go('/home');
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

class _AuthScaffold extends StatelessWidget {
  const _AuthScaffold({required this.child, this.appBar});

  final Widget child;
  final PreferredSizeWidget? appBar;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: appBar,
    body: SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: ConstrainedBox(
          constraints: BoxConstraints(
            minHeight:
                MediaQuery.sizeOf(context).height -
                MediaQuery.paddingOf(context).vertical -
                (appBar == null ? 0 : kToolbarHeight) -
                AppSpacing.xxl,
          ),
          child: child,
        ),
      ),
    ),
  );
}

class _InlineError extends StatelessWidget {
  const _InlineError(this.message);

  final String message;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(AppSpacing.sm),
    decoration: BoxDecoration(
      color: AppColors.danger.withValues(alpha: 0.08),
      borderRadius: BorderRadius.circular(12),
    ),
    child: Row(
      children: [
        const Icon(Icons.error_outline_rounded, color: AppColors.danger),
        const SizedBox(width: AppSpacing.xs),
        Expanded(
          child: Text(message, style: Theme.of(context).textTheme.bodySmall),
        ),
      ],
    ),
  );
}

String? _required(String? value, String message) =>
    value == null || value.trim().isEmpty ? message : null;
