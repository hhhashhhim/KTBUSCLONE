import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

enum LegalDocument {
  terms('Terms & conditions', 'https://www.kainattravels.com/Terms'),
  privacy('Privacy policy', 'https://www.kainattravels.com/privacy');

  const LegalDocument(this.label, this.url);

  final String label;
  final String url;

  Future<void> open(BuildContext context) async {
    try {
      if (await launchUrl(
        Uri.parse(url),
        mode: LaunchMode.externalApplication,
      )) {
        return;
      }
    } catch (_) {
      // Show the same recovery message if the browser cannot be launched.
    }
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Unable to open $label. Please try again.'),
        action: SnackBarAction(label: 'Retry', onPressed: () => open(context)),
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
