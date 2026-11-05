import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/config/app_environment.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/core/storage/token_storage.dart';

class _Tokens extends TokenStorage {
  int reads = 0;
  @override
  Future<String?> read() async {
    reads++;
    return 'test-passenger-token';
  }
}

class _Adapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  int status = 200;
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      '{"success":true,"data":null}',
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
        if (status == 302) 'location': ['https://untrusted.example/collect'],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  test('production and staging require valid HTTPS API configuration', () {
    for (final environment in ['production', 'staging']) {
      for (final url in [
        'http://api.example/api/mobile/v1',
        'https://localhost/api/mobile/v1',
        'https://127.0.0.1/api/mobile/v1',
        'https://user:password@api.example/api/mobile/v1',
        'https://api.example/api/web/v1',
        'https://api.example/api/mobile/v1?token=private',
        'https://api.example/api/mobile/v1#fragment',
      ]) {
        expect(
          () => validateApiConfiguration(
            environmentName: environment,
            apiBaseUrl: url,
            releaseBuild: false,
          ),
          throwsStateError,
          reason: '$environment: $url',
        );
      }
    }
    validateApiConfiguration(
      environmentName: 'production',
      apiBaseUrl: 'https://api.example/api/mobile/v1',
      releaseBuild: true,
    );
    validateApiConfiguration(
      environmentName: 'development',
      apiBaseUrl: 'http://10.0.2.2:8000/api/mobile/v1',
      releaseBuild: false,
    );
  });

  test('release builds reject development and unknown environment names', () {
    for (final environment in ['development', 'staging', 'prod', '']) {
      expect(
        () => validateApiConfiguration(
          environmentName: environment,
          apiBaseUrl: 'https://api.example/api/mobile/v1',
          releaseBuild: true,
        ),
        throwsStateError,
      );
    }
  });

  test('API destinations reject origin changes and encoded traversal', () {
    final base = Uri.parse('https://api.example/api/mobile/v1');
    expect(isTrustedPassengerApiUri(Uri.parse('$base/profile'), base), isTrue);
    for (final url in [
      'https://untrusted.example/api/mobile/v1/profile',
      'http://api.example/api/mobile/v1/profile',
      'https://api.example:8443/api/mobile/v1/profile',
      'https://user:password@api.example/api/mobile/v1/profile',
      'https://api.example/api/mobile/v10/profile',
      'https://api.example/api/web/v1/users',
      'https://api.example/api/mobile/v1/../users',
      'https://api.example/api/mobile/v1/%2e%2e%2fusers',
      'https://api.example/api/mobile/v1/%252e%252e/users',
    ]) {
      expect(
        isTrustedPassengerApiUri(Uri.parse(url), base),
        isFalse,
        reason: url,
      );
    }
  });

  test('trusted requests use tokens and disable redirects', () async {
    final tokens = _Tokens();
    final adapter = _Adapter();
    final api = ApiClient(tokens, httpClientAdapter: adapter);
    addTearDown(api.dispose);
    await api.get('/profile');
    expect(tokens.reads, 1);
    expect(
      adapter.requests.single.headers['Authorization'],
      'Bearer test-passenger-token',
    );
    expect(adapter.requests.single.followRedirects, isFalse);
  });

  test('untrusted requests never read tokens or reach HTTP', () async {
    final tokens = _Tokens();
    final adapter = _Adapter();
    final api = ApiClient(tokens, httpClientAdapter: adapter);
    addTearDown(api.dispose);
    final configured = Uri.parse(AppEnvironmentConfig.apiBaseUrl);
    for (final path in [
      'https://untrusted.example/collect',
      configured.replace(path: '/api/web/v1/users').toString(),
    ]) {
      await expectLater(
        api.post(path, data: {'password': 'test-only'}),
        throwsA(isA<ApiException>()),
      );
    }
    expect(tokens.reads, 0);
    expect(adapter.requests, isEmpty);
  });

  test('API redirects cannot forward credentials to another server', () async {
    final tokens = _Tokens();
    final adapter = _Adapter()..status = 302;
    final api = ApiClient(tokens, httpClientAdapter: adapter);
    addTearDown(api.dispose);
    await expectLater(api.get('/profile'), throwsA(isA<ApiException>()));
    expect(adapter.requests, hasLength(1));
    expect(adapter.requests.single.followRedirects, isFalse);
  });
}
