import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/core/storage/token_storage.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/data/auth_repository.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';

class SessionTokens implements TokenStorage {
  String? token = 'saved-session';
  bool failClear = false;
  @override
  Future<String?> read() async => token;
  @override
  Future<void> write(String value) async => token = value;
  @override
  Future<void> clear() async {
    if (failClear) throw StateError('Storage unavailable');
    token = null;
  }
}

class SessionApi implements ApiClient {
  Object? error;
  dynamic profile = {
    'id': 1,
    'full_name': 'Passenger',
    'mobile': '03001234567',
    'mobile_verified': true,
  };
  int profileReads = 0;
  int posts = 0;
  @override
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
  }) async {
    profileReads++;
    if (error != null) throw error!;
    return profile;
  }

  @override
  Future<dynamic> post(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    posts++;
    if (error != null) throw error!;
    return null;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  test('a stale JazzCash selection never sends a new booking request', () async {
    // SessionApi.post would succeed; the repository must reject before sending.
    final api = SessionApi();
    await expectLater(
      TravelRepository(
        api,
      ).createBooking(quoteToken: 'old-quote', paymentMethod: 'jazzcash'),
      throwsA(isA<ApiException>()),
    );
    expect(api.posts, 0);
  });

  for (final status in [null, 403, 429, 500, 503]) {
    test(
      'profile failure $status preserves login and can be retried',
      () async {
        final tokens = SessionTokens();
        final api = SessionApi()
          ..error = ApiException('Please try again.', statusCode: status);
        final container = ProviderContainer(
          overrides: [
            authRepositoryProvider.overrideWithValue(
              AuthRepository(api, tokens),
            ),
          ],
        );
        addTearDown(container.dispose);
        final controller = container.read(authControllerProvider.notifier);
        await expectLater(
          controller.restoreSession(),
          throwsA(isA<ApiException>()),
        );
        expect(tokens.token, 'saved-session');
        expect(container.read(authControllerProvider).isAuthenticated, isFalse);
        api.error = null;
        expect(await controller.restoreSession(), isTrue);
        expect(container.read(authControllerProvider).passenger!.id, 1);
        expect(tokens.token, 'saved-session');
      },
    );
  }

  test('malformed profile keeps the token without granting access', () async {
    final api = SessionApi()..profile = 'invalid response';
    final tokens = SessionTokens();
    await expectLater(
      AuthRepository(api, tokens).restore(),
      throwsA(isA<FormatException>()),
    );
    expect(tokens.token, 'saved-session');
  });

  test('a server-rejected session is cleared and requires login', () async {
    final api = SessionApi()
      ..error = const ApiException('Unauthenticated.', statusCode: 401);
    final tokens = SessionTokens();
    expect(await AuthRepository(api, tokens).restore(), isNull);
    expect(tokens.token, isNull);
  });

  test('missing token requires login without a profile request', () async {
    final api = SessionApi();
    final tokens = SessionTokens()..token = null;
    expect(await AuthRepository(api, tokens).restore(), isNull);
    expect(api.profileReads, 0);
  });

  test('offline sign out clears the local session', () async {
    final api = SessionApi();
    final tokens = SessionTokens();
    final container = ProviderContainer(
      overrides: [
        authRepositoryProvider.overrideWithValue(AuthRepository(api, tokens)),
      ],
    );
    addTearDown(container.dispose);
    final controller = container.read(authControllerProvider.notifier);
    await controller.restoreSession();
    api.error = const ApiException('Unable to connect.');
    await controller.logout();
    expect(tokens.token, isNull);
    expect(container.read(authControllerProvider).isAuthenticated, isFalse);
    expect(container.read(authControllerProvider).isLoading, isFalse);
  });

  test(
    'storage failure during sign out ends loading and permits retry',
    () async {
      final tokens = SessionTokens()..failClear = true;
      final container = ProviderContainer(
        overrides: [
          authRepositoryProvider.overrideWithValue(
            AuthRepository(SessionApi(), tokens),
          ),
        ],
      );
      addTearDown(container.dispose);
      final controller = container.read(authControllerProvider.notifier);
      await controller.restoreSession();
      await expectLater(controller.logout(), throwsStateError);
      expect(container.read(authControllerProvider).isLoading, isFalse);
      expect(container.read(authControllerProvider).isAuthenticated, isTrue);
      tokens.failClear = false;
      await controller.logout();
      expect(tokens.token, isNull);
      expect(container.read(authControllerProvider).isAuthenticated, isFalse);
    },
  );
}
