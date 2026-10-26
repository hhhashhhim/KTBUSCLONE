import 'package:flutter/material.dart';

import '../legal/privacy_policy.dart';
import 'legal_document_screen.dart';
import 'privacy_policy_screen.dart';

enum LegalDocument {
  terms('Terms & conditions', 'https://www.kainattravels.com/Terms'),
  privacy('Privacy policy', PrivacyPolicy.sourceUrl);

  const LegalDocument(this.label, this.url);

  final String label;
  final String url;

  Future<void> open(BuildContext context) async {
    await Navigator.of(context, rootNavigator: true).push<void>(
      MaterialPageRoute(
        builder: (_) => this == LegalDocument.privacy
            ? const PrivacyPolicyScreen()
            : LegalDocumentScreen(title: label, uri: Uri.parse(url)),
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
