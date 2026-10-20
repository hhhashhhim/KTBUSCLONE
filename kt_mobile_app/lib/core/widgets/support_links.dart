import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

abstract final class SupportLinks {
  static final website = Uri.parse('https://www.kainattravels.com/contact');

  static Future<void> open(BuildContext context, [Uri? uri]) async {
    try {
      if (await launchUrl(
        uri ?? website,
        mode: LaunchMode.externalApplication,
      )) {
        return;
      }
    } catch (_) {
      // Devices may have no handler for a phone, email or browser link.
    }
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Unable to open support. Please visit www.kainattravels.com/contact.',
        ),
      ),
    );
  }
}
