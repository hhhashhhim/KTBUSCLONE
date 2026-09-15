class BookingPayment {
  const BookingPayment({
    required this.id,
    required this.method,
    required this.status,
    required this.expiresAt,
    this.checkoutUrl,
    this.environment = 'live',
  });

  final String id;
  final String method;
  final String status;
  final DateTime expiresAt;
  final String? checkoutUrl;
  final String environment;

  factory BookingPayment.fromJson(Map<String, dynamic> json) => BookingPayment(
    id: json['id'] as String,
    method: json['method'] as String,
    status: json['status'] as String,
    expiresAt: DateTime.parse(json['expires_at'] as String).toLocal(),
    checkoutUrl: json['checkout_url'] as String?,
    environment: (json['environment'] as String?) ?? 'live',
  );
}

String paymentMethodLabel(String method) => switch (method) {
  'counter' || 'pay_at_branch' => 'Pay at Branch',
  'jazzcash' => 'JazzCash Mobile Account',
  'bank_alfalah' => 'Debit / Credit Card',
  'easypaisa' => 'Easypaisa',
  'card' => 'Credit / Debit Card',
  _ => method.replaceAll('_', ' '),
};

// The app opens only its own backend's signed checkout page.
Uri? trustedCheckoutUri(
  String? value,
  String apiBaseUrl, {
  bool allowLocalSandbox = false,
}) {
  final uri = value == null ? null : Uri.tryParse(value);
  final api = Uri.tryParse(apiBaseUrl);
  final localSandbox =
      allowLocalSandbox &&
      uri?.scheme == 'http' &&
      api?.scheme == 'http' &&
      const ['127.0.0.1', 'localhost', '10.0.2.2'].contains(uri?.host);
  if (uri == null ||
      api == null ||
      (uri.scheme != 'https' && !localSandbox) ||
      uri.host != api.host ||
      uri.port != api.port ||
      uri.userInfo.isNotEmpty ||
      uri.hasFragment ||
      !uri.path.startsWith(
        '${api.path.replaceFirst(RegExp(r'/+$'), '')}/payments/',
      ) ||
      !uri.path.endsWith('/checkout') ||
      !uri.queryParameters.containsKey('signature')) {
    return null;
  }
  return uri;
}
