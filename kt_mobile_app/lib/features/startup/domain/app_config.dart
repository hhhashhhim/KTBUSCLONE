class AppFeatureFlags {
  const AppFeatureFlags({
    this.wallet = false,
    this.onlinePayments = false,
    this.notifications = false,
    this.promotions = false,
  });

  final bool wallet;
  final bool onlinePayments;
  final bool notifications;
  final bool promotions;

  factory AppFeatureFlags.fromJson(Map<String, dynamic> json) =>
      AppFeatureFlags(
        wallet: json['wallet'] == true,
        onlinePayments: json['online_payments'] == true,
        notifications: json['notifications'] == true,
        promotions: json['promotions'] == true,
      );
}

class MobileAppConfig {
  const MobileAppConfig({
    required this.latestVersion,
    required this.minimumSupportedVersion,
    required this.forceUpdate,
    required this.maintenanceMode,
    required this.features,
    this.maintenanceMessage,
    this.androidStoreUrl,
    this.iosStoreUrl,
    this.supportPhone,
    this.supportWhatsapp,
    this.supportEmail,
  });

  final String latestVersion;
  final String minimumSupportedVersion;
  final bool forceUpdate;
  final bool maintenanceMode;
  final String? maintenanceMessage;
  final String? androidStoreUrl;
  final String? iosStoreUrl;
  final String? supportPhone;
  final String? supportWhatsapp;
  final String? supportEmail;
  final AppFeatureFlags features;

  factory MobileAppConfig.fromJson(Map<String, dynamic> json) =>
      MobileAppConfig(
        latestVersion: (json['latest_version'] ?? '1.0.0').toString(),
        minimumSupportedVersion: (json['minimum_supported_version'] ?? '1.0.0')
            .toString(),
        forceUpdate: json['force_update'] == true,
        maintenanceMode: json['maintenance_mode'] == true,
        maintenanceMessage: _nonEmpty(json['maintenance_message']),
        androidStoreUrl: _nonEmpty(json['android_store_url']),
        iosStoreUrl: _nonEmpty(json['ios_store_url']),
        supportPhone: _nonEmpty(json['support_phone']),
        supportWhatsapp: _nonEmpty(json['support_whatsapp']),
        supportEmail: _nonEmpty(json['support_email']),
        features: AppFeatureFlags.fromJson(
          (json['features'] as Map?)?.cast<String, dynamic>() ?? const {},
        ),
      );

  static String? _nonEmpty(dynamic value) {
    final text = value?.toString().trim();
    return text == null || text.isEmpty ? null : text;
  }
}
