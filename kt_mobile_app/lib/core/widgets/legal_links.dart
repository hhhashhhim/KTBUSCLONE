import 'package:flutter/material.dart';

import 'legal_document_screen.dart';

enum LegalDocument {
  terms('Terms & conditions', 'https://www.kainattravels.com/Terms'),
  privacy('Privacy policy', 'https://www.kainattravels.com/privacy');

  const LegalDocument(this.label, this.url);

  final String label;
  final String url;

  Future<void> open(BuildContext context) async {
    await Navigator.of(context, rootNavigator: true).push<void>(
      MaterialPageRoute(
        builder: (_) => LegalDocumentScreen(title: label, uri: Uri.parse(url)),
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
