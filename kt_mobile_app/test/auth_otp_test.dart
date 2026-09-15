import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/network/api_client.dart';
import 'package:kt_mobile_app/core/network/api_exception.dart';
import 'package:kt_mobile_app/core/storage/token_storage.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/data/auth_repository.dart';
import 'package:kt_mobile_app/features/auth/presentation/auth_screens.dart';

class MemoryTokens implements TokenStorage {
  String? token;
  @override
  Future<String?> read() async => token;
  @override
  Future<void> write(String value) async => token = value;
  @override
  Future<void> clear() async => token = null;
}

class OtpApi implements ApiClient {
  bool deliverySent = false;
  bool failResend = false;
  Completer<void>? resendPending;
  int resends = 0;
  String? submittedCode;

  Map<String, dynamic> passenger(bool verified) => {
    'id': 1,
    'full_name': 'Passenger',
    'mobile': '03001234567',
    'mobile_verified': verified,
  };

  @override
  Future<dynamic> post(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    switch (path) {
      case '/auth/register':
        return {
          'passenger': passenger(false),
          'token': 'test-token',
          'verification_delivery_sent': deliverySent,
          'verification_message': deliverySent
              ? null
              : 'WhatsApp delivery failed. Try again.',
        };
      case '/auth/resend-otp':
        resends++;
        if (resendPending != null) await resendPending!.future;
        if (failResend) throw const ApiException('Please try again shortly.');
        return null;
      case '/auth/verify-otp':
        submittedCode = (data as Map)['code'] as String;
        return {'passenger': passenger(true)};
      default:
        throw StateError('Unexpected endpoint $path');
    }
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<bool> register(ProviderContainer container) => container
    .read(authControllerProvider.notifier)
    .register(
      fullName: 'Passenger',
      mobile: '03001234567',
      cnic: '3520212345671',
      password: 'password123',
      passwordConfirmation: 'password123',
    );

void main() {
  test(
    'signup keeps the session and surfaces WhatsApp delivery failure',
    () async {
      final tokens = MemoryTokens();
      final api = OtpApi();
      final container = ProviderContainer(
        overrides: [
          authRepositoryProvider.overrideWithValue(AuthRepository(api, tokens)),
        ],
      );
      addTearDown(container.dispose);
      expect(await register(container), isTrue);
      expect(tokens.token, 'test-token');
      expect(
        container.read(authControllerProvider).passenger!.mobileVerified,
        isFalse,
      );
      expect(
        container.read(authControllerProvider).otpDeliveryMessage,
        'WhatsApp delivery failed. Try again.',
      );
      api.deliverySent = true;
      expect(await register(container), isTrue);
      expect(container.read(authControllerProvider).otpDeliveryMessage, isNull);
    },
  );

  testWidgets(
    'resend shows failure, prevents duplicate requests, and clears warning on success',
    (tester) async {
      final api = OtpApi()..failResend = true;
      final container = ProviderContainer(
        overrides: [
          authRepositoryProvider.overrideWithValue(
            AuthRepository(api, MemoryTokens()),
          ),
        ],
      );
      addTearDown(container.dispose);
      await register(container);
      await tester.pumpWidget(
        UncontrolledProviderScope(
          container: container,
          child: const MaterialApp(home: OtpScreen()),
        ),
      );
      expect(find.text('WhatsApp delivery failed. Try again.'), findsOneWidget);
      await tester.tap(find.text('Resend code'));
      await tester.pumpAndSettle();
      expect(find.text('Please try again shortly.'), findsOneWidget);
      api.failResend = false;
      api.resendPending = Completer<void>();
      await tester.tap(find.text('Resend code'));
      await tester.pump();
      expect(
        tester
            .widget<TextButton>(find.widgetWithText(TextButton, 'Resend code'))
            .onPressed,
        isNull,
      );
      expect(api.resends, 2);
      api.resendPending!.complete();
      await tester.pumpAndSettle();
      expect(find.text('Please try again shortly.'), findsNothing);
      expect(
        find.text('Code requested. Please check WhatsApp.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets('verification updates passenger state before opening home', (
    tester,
  ) async {
    final api = OtpApi()..deliverySent = true;
    final container = ProviderContainer(
      overrides: [
        authRepositoryProvider.overrideWithValue(
          AuthRepository(api, MemoryTokens()),
        ),
      ],
    );
    addTearDown(container.dispose);
    await register(container);
    final router = GoRouter(
      initialLocation: '/verify-otp',
      routes: [
        GoRoute(path: '/verify-otp', builder: (_, _) => const OtpScreen()),
        GoRoute(
          path: '/home',
          builder: (_, _) => const Scaffold(body: Text('Verified home')),
        ),
      ],
    );
    addTearDown(router.dispose);
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp.router(routerConfig: router),
      ),
    );
    await tester.enterText(find.byType(TextField), '123456');
    await tester.tap(find.text('Verify'));
    await tester.pumpAndSettle();
    expect(api.submittedCode, '123456');
    expect(
      container.read(authControllerProvider).passenger!.mobileVerified,
      isTrue,
    );
    expect(find.text('Verified home'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
