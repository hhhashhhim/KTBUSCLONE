import 'dart:io';

enum AppEnvironment { development, staging, production }

abstract final class AppEnvironmentConfig {
  static const environmentName = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'development',
  );

  static const _configuredApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api/mobile/v1',
  );

  static String get apiBaseUrl {
    if (environment != AppEnvironment.development) {
      return _configuredApiBaseUrl;
    }

    final uri = Uri.tryParse(_configuredApiBaseUrl);
    if (uri == null) return _configuredApiBaseUrl;

    if (Platform.isIOS && uri.host == '10.0.2.2') {
      return uri.replace(host: '127.0.0.1').toString();
    }
    if (Platform.isAndroid &&
        (uri.host == '127.0.0.1' || uri.host == 'localhost')) {
      return uri.replace(host: '10.0.2.2').toString();
    }

    return _configuredApiBaseUrl;
  }

  static AppEnvironment get environment => switch (environmentName) {
    'production' => AppEnvironment.production,
    'staging' => AppEnvironment.staging,
    _ => AppEnvironment.development,
  };

  static bool get enableNetworkLogs =>
      environment == AppEnvironment.development;
}
