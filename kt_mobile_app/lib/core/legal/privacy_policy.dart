/// Website policy adapted for the mobile app, with disclosures grounded in the
/// implemented authentication, booking, payment, notification and deletion flows.
/// Keep the public policy and Play Console declarations synchronized at release.
abstract final class PrivacyPolicy {
  static const updatedOn = '26 October 2026';
  static const sourceUrl = 'https://www.kainattravels.com/privacy';

  static const sections = <PrivacyPolicySection>[
    PrivacyPolicySection(
      title: 'About this policy',
      paragraphs: [
        'Kainat Travels provides this passenger app and its travel services. '
            'This policy explains the information used to run your account, '
            'arrange journeys and communicate with you, and the choices you '
            'have about that information.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Details you give us',
      paragraphs: [
        'Depending on the service you use, you provide your name, mobile '
            'number, CNIC (Computerized National Identity Card), gender and '
            'email address. We also receive details you enter for other '
            'travellers, saved passengers and support enquiries. Only provide '
            'another person’s details when you have permission to do so.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Account and journey records',
      paragraphs: [
        'Account registration and sign-in use your password, verification '
            'codes and account identifiers. The app uses access credentials '
            'to keep your session authenticated.',
        'Booking records include travellers, routes, travel dates, selected '
            'seats, fares, ticket references and booking status. Payment '
            'records include the selected method, transaction references and '
            'payment status. Travel Points balances and activity are used '
            'where the loyalty service is available.',
      ],
    ),
    PrivacyPolicySection(
      title: 'How these details are used',
      paragraphs: [
        'We use your information to verify and manage your account, issue '
            'tickets, handle payments and enquiries, maintain administrative '
            'and legal records, and improve our services. We communicate '
            'about bookings and may send service updates and promotional '
            'emails related to Kainat Travels services.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Service providers and payments',
      paragraphs: [
        'Kainat Travels does not sell or trade your personal details. '
            'Information may be shared when required by law or with service '
            'providers helping deliver our services, subject to confidentiality '
            'arrangements. This includes payment processing and message delivery.',
        'When you choose card payment, Bank Alfalah’s payment service handles '
            'the payment details through its checkout page inside the app. '
            'The passenger app does not store your card number or security '
            'code. It uses the booking and payment references and the status '
            'confirmed by our server.',
        'Account verification and password-reset codes may be delivered to '
            'your mobile number through WhatsApp and our messaging provider. '
            'Those services process the recipient number and message to '
            'deliver the code.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Phone alerts',
      paragraphs: [
        'Where phone alerts are available and you allow them, the app uses '
            'Google Firebase Cloud Messaging to deliver notifications. '
            'An app-installation identifier and your device platform are '
            'registered with your signed-in account for delivery. Firebase '
            'processes installation and delivery information to provide '
            'this service.',
        'You can change notification permission in your phone’s settings. '
            'Phone alerts are optional; the in-app inbox is separate.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Biometric sign-in and security',
      paragraphs: [
        'Optional fingerprint or face sign-in is checked by your phone. '
            'Kainat Travels does not receive or store fingerprint images, '
            'face images or biometric templates. A protected sign-in '
            'credential is kept on your device and used with our server '
            'after successful device authentication.',
        'The app uses HTTPS for production connections and platform secure '
            'storage for persistent sign-in credentials. Access controls '
            'help protect account information. No online service or storage '
            'method can guarantee complete security.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Your choices',
      paragraphs: [
        'Registration asks you to accept the terms and this policy. '
            'Optional device permissions are requested separately. You can '
            'edit supported profile details, remove saved passengers, '
            'disable biometric sign-in and manage phone-notification '
            'permission. Contact us about corrections, communication '
            'preferences or other privacy questions.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Deleting your account',
      paragraphs: [
        'In the app, open Account → Delete account. Review the information, '
            'enter your current password, acknowledge the deletion details '
            'and confirm. Once the request succeeds, your mobile account, '
            'saved passengers and booking drafts are deleted immediately and '
            'your sign-in sessions are revoked.',
        'If you cannot access the app, request account deletion by calling '
            'our call centre on 03-111-777-333 or emailing '
            'info@kainattravels.com. The call centre or email support can '
            'process your account-deletion request without requiring you '
            'to reinstall the app.',
        'Deleting the app from your phone or signing out does not delete '
            'your account. Account deletion does not cancel existing trips '
            'or issue refunds. Save your ticket references before deleting '
            'your account and contact support for help with existing trips.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Records kept after deletion',
      paragraphs: [
        'Ticket and payment records are retained for the full financial '
            'year for tax reporting, even when your mobile account is '
            'deleted immediately.',
        'Customer details and loyalty records held in Kainat Travels’ '
            'underlying booking system are not automatically erased by '
            'mobile-account deletion. Contact us about these records '
            'and their retention or deletion. '
            'A limited account-deletion audit record is also kept.',
        'Contact Kainat Travels using the details below to ask about the '
            'records retained for you, their retention periods or a request '
            'concerning those records. Do not send your password, '
            'verification codes or card security details in a support message.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Policy updates',
      paragraphs: [
        'This policy may change as our services and privacy practices '
            'change. The date above identifies this version. Updated '
            'in-app text is included with app updates; our public privacy '
            'policy is also available on the Kainat Travels website.',
      ],
    ),
    PrivacyPolicySection(
      title: 'Contact Kainat Travels',
      paragraphs: [
        'For privacy questions and help with your information:\n'
            'Email: info@kainattravels.com\n'
            'Phone: 03-111-777-333',
        'Head office: Office 61, Peshawar Road near Peer Wadhai Mor, '
            'Rawalpindi, Pakistan.',
      ],
    ),
  ];
}

class PrivacyPolicySection {
  const PrivacyPolicySection({required this.title, required this.paragraphs});

  final String title;
  final List<String> paragraphs;
}
