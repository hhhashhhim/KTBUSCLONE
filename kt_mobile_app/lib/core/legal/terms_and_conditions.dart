/// Travel terms and booking-help content copied from [sourceUrl] on 8 November
/// 2026, with HTML whitespace normalized. The mobile account-deletion section
/// describes the implemented deletion behavior. Keep this bundled copy
/// synchronized with the public terms and account behavior when releasing updates.
abstract final class TermsAndConditions {
  static const sourceUrl = 'https://www.kainattravels.com/Terms';

  static const introduction =
      'Welcome to Kainat Travels. By using our services, you agree to comply '
      'with and be bound by the following terms and conditions. Please read '
      'them carefully before using our services.';

  static const sections = <TermsAndConditionsSection>[
    TermsAndConditionsSection(
      title: 'Arrival Time',
      paragraphs: [
        'Passengers are required to arrive at the departure point at least '
            '30 minutes before the scheduled departure time. The bus will '
            'not wait for late passengers.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Luggage Policy',
      paragraphs: [
        'Each passenger is allowed a maximum of 30kg of luggage. Any '
            'additional luggage will be subject to extra charges.',
        'The company reserves the right to refuse excess luggage that '
            'exceeds safety regulations or capacity limits',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Complimentary Services',
      paragraphs: [
        'We provide free Wi-Fi (up to 350MB per passenger), food, and '
            'entertainment as complimentary services. These services are '
            'offered as a courtesy and are not guaranteed. No claims can '
            'be made for the unavailability or malfunctioning of these services.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Safety Regulations',
      paragraphs: [
        'For safety reasons, the company does not permit roadside pick-up '
            'or drop-off of passengers. All boarding and alighting must '
            'occur at designated stops.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Personal Belongings',
      paragraphs: [
        'Passengers are responsible for keeping their personal belongings '
            'safe at all times. The company is not liable for any loss, '
            'theft, or damage to personal items.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Operational Changes',
      paragraphs: [
        'The company reserves the right to cancel a ticket or alter the '
            'schedule, including dropping a bus from service, at any time '
            'due to operational needs or unforeseen circumstances. In such '
            'cases, passengers may be offered alternative arrangements or '
            'a refund at the company’s discretion.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Liability',
      paragraphs: [
        'In the event of an accident or if the bus is late, the company is '
            'not liable for any inconvenience, loss, or damage caused. '
            'Passengers are encouraged to plan accordingly and allow for '
            'potential delays.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Account Deletion and Customer Details',
      paragraphs: [
        'After your account-deletion request succeeds, your mobile account '
            'and its personal details, saved passengers and unused booking '
            'drafts that are not linked to an invoice are permanently removed. '
            'Your sign-in sessions are revoked.',
        'Customer details held in the company’s booking system, tickets, '
            'payment history, booking passenger records and loyalty records '
            'are retained. A limited deletion audit and records used to '
            'enforce existing fraud restrictions are also retained. '
            'Contact customer support about these records or their deletion.',
        'Deleting your account does not cancel existing trips or issue '
            'refunds. Save your ticket references before deleting your '
            'account. Deleting the app or signing out does not delete '
            'your account.',
      ],
    ),
    TermsAndConditionsSection(
      title: 'Amendments to Terms and Conditions',
      paragraphs: [
        'The company reserves the right to amend these terms and conditions '
            'at any time without prior notice. Any changes will be posted '
            'on this page, and it is the responsibility of the passengers '
            'to review these terms regularly.',
      ],
    ),
  ];

  static const acknowledgement =
      'By using our services, you acknowledge that you have read, understood, '
      'and agree to be bound by these terms and conditions.';

  static const bookingHelp = TermsAndConditionsSection(
    title: 'Need help booking?',
    paragraphs: [
      'Call our customer services team on the number below to speak to one '
          'of our advisors who will help you with all of your needs.',
      '03-111-777-333',
    ],
  );
}

class TermsAndConditionsSection {
  const TermsAndConditionsSection({
    required this.title,
    required this.paragraphs,
  });

  final String title;
  final List<String> paragraphs;
}
