import 'package:flutter/material.dart';

import '../legal/terms_and_conditions.dart';
import '../theme/app_spacing.dart';

/// Native, selectable terms available before login and without a network.
class TermsAndConditionsScreen extends StatelessWidget {
  const TermsAndConditionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final paragraphStyle = theme.textTheme.bodyMedium?.copyWith(height: 1.6);
    return Scaffold(
      appBar: AppBar(title: const Text('Terms & conditions')),
      body: SafeArea(
        child: Scrollbar(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 720),
                child: SelectionArea(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Icon(
                        Icons.description_outlined,
                        size: 36,
                        color: theme.colorScheme.primary,
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      Text(
                        'Kainat Travels',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.headlineSmall,
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      Text(
                        TermsAndConditions.introduction,
                        style: paragraphStyle,
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      for (final section in TermsAndConditions.sections)
                        _TermsSection(section: section),
                      Text(
                        TermsAndConditions.acknowledgement,
                        style: paragraphStyle,
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      const _TermsSection(
                        section: TermsAndConditions.bookingHelp,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _TermsSection extends StatelessWidget {
  const _TermsSection({required this.section});

  final TermsAndConditionsSection section;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Semantics(
          header: true,
          child: Text(section.title, style: theme.textTheme.titleMedium),
        ),
        for (final paragraph in section.paragraphs) ...[
          const SizedBox(height: AppSpacing.sm),
          Text(
            paragraph,
            style: theme.textTheme.bodyMedium?.copyWith(height: 1.6),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
      ],
    );
  }
}
