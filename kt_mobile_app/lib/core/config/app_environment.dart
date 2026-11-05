import 'dart:io';

import 'package:flutter/foundation.dart';

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
    validateApiConfiguration(
      environmentName: environmentName,
      apiBaseUrl: _configuredApiBaseUrl,
      releaseBuild: kReleaseMode,
    );
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
    'development' => AppEnvironment.development,
    _ => throw StateError('Unknown app environment.'),
  };

  static bool get enableNetworkLogs =>
      environment == AppEnvironment.development;
}

/// Validate release settings on both mobile platforms before using credentials.
void validateApiConfiguration({
  required String environmentName,
  required String apiBaseUrl,
  required bool releaseBuild,
}) {
  if (!const {
    'development',
    'staging',
    'production',
  }.contains(environmentName)) {
    throw StateError('Unknown app environment.');
  }
  if (releaseBuild && environmentName != 'production') {
    throw StateError('Release builds require the production environment.');
  }
  final uri = Uri.tryParse(apiBaseUrl);
  if (uri == null ||
      !uri.hasAuthority ||
      uri.host.isEmpty ||
      !const {'http', 'https'}.contains(uri.scheme) ||
      uri.userInfo.isNotEmpty ||
      uri.hasQuery ||
      uri.hasFragment ||
      uri.path.replaceFirst(RegExp(r'/+$'), '') != '/api/mobile/v1') {
    throw StateError('Invalid passenger API URL.');
  }
  if (environmentName != 'development' &&
      (uri.scheme != 'https' ||
          const {
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            '10.0.2.2',
          }.contains(uri.host) ||
          uri.host.endsWith('.localhost'))) {
    throw StateError('A public HTTPS passenger API is required.');
  }
}

/// Absolute URLs and redirects must never send passenger tokens off the API.
bool isTrustedPassengerApiUri(Uri request, Uri api) {
  final prefix = api.path.replaceFirst(RegExp(r'/+$'), '');
  return request.scheme == api.scheme &&
      request.host == api.host &&
      request.port == api.port &&
      request.userInfo.isEmpty &&
      !request.hasFragment &&
      (request.path == prefix || request.path.startsWith('$prefix/')) &&
      !request.pathSegments.any(
        (segment) =>
            segment == '.' ||
            segment == '..' ||
            segment.contains('/') ||
            segment.contains(r'\') ||
            segment.contains('%'),
      );
}
