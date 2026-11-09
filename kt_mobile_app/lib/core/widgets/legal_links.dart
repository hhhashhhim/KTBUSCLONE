import 'package:flutter/material.dart';

import '../legal/privacy_policy.dart';
import '../legal/terms_and_conditions.dart';
import 'privacy_policy_screen.dart';
import 'terms_and_conditions_screen.dart';

enum LegalDocument {
  terms('Terms & conditions', TermsAndConditions.sourceUrl),
  privacy('Privacy policy', PrivacyPolicy.sourceUrl);

  const LegalDocument(this.label, this.url);

  final String label;
  final String url;

  Future<void> open(BuildContext context) async {
    await Navigator.of(context, rootNavigator: true).push<void>(
      MaterialPageRoute(
        builder: (_) => switch (this) {
          LegalDocument.terms => const TermsAndConditionsScreen(),
          LegalDocument.privacy => const PrivacyPolicyScreen(),
        },
      ),
    );
  }
}

class LegalLinks extends StatelessWidget {
  const LegalLinks({super.key});

  @override
  Widget build(BuildContext context) => Wrap(
    spacing: 8,
    children: [
      for (final document in LegalDocument.values)
        TextButton(
          onPressed: () => document.open(context),
          child: Text(document.label),
        ),
    ],
  );
}
