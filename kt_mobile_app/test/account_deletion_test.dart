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
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/profile/delete_account_screen.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

class DeletionTokens implements TokenStorage {
  String? token = 'existing-token';
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

class DeletionApi implements ApiClient {
  int calls = 0;
  Object? submitted;
  String? error;
  Completer<void>? pending;
  @override
  Future<dynamic> delete(
    String path, {
    Object? data,
    CancelToken? cancelToken,
  }) async {
    expect(path, '/account');
    submitted = data;
    calls++;
    if (pending != null) await pending!.future;
    if (error != null) throw ApiException(error!);
    return null;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

ProviderContainer session(DeletionApi api, DeletionTokens tokens) {
  final container = ProviderContainer(
    overrides: [
      authRepositoryProvider.overrideWithValue(AuthRepository(api, tokens)),
    ],
  );
  container
      .read(authControllerProvider.notifier)
      .replacePassenger(
        PassengerAccount.fromJson({
          'id': 1,
          'full_name': 'Traveller',
          'mobile': '03001234567',
          'mobile_verified': true,
        }),
      );
  return container;
}

Future<void> showScreen(
  WidgetTester tester,
  ProviderContainer container,
) async {
  final router = GoRouter(
    initialLocation: '/delete',
    routes: [
      GoRoute(path: '/delete', builder: (_, _) => const DeleteAccountScreen()),
      GoRoute(
        path: '/login',
        builder: (_, _) => const Scaffold(body: Text('Login page')),
      ),
      GoRoute(
        path: '/profile',
        builder: (_, _) => const Scaffold(body: Text('Account page')),
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
  await tester.pumpAndSettle();
}

Future<void> reviewAndContinue(WidgetTester tester) async {
  await tester.enterText(find.byType(TextFormField), 'current-password');
  await tester.ensureVisible(find.byType(CheckboxListTile));
  await tester.tap(find.byType(CheckboxListTile));
  await tester.pumpAndSettle();
  await tester.ensureVisible(
    find.widgetWithText(FilledButton, 'Delete account'),
  );
  await tester.tap(find.widgetWithText(FilledButton, 'Delete account'));
  await tester.pumpAndSettle();
}

void main() {
  test('API failure preserves the token and signed-in passenger', () async {
    final api = DeletionApi()..error = 'The password is incorrect.';
    final tokens = DeletionTokens();
    final container = session(api, tokens);
    addTearDown(container.dispose);
    await expectLater(
      container.read(authControllerProvider.notifier).deleteAccount('wrong'),
      throwsA(isA<ApiException>()),
    );
    expect(tokens.token, 'existing-token');
    expect(container.read(authControllerProvider).isAuthenticated, isTrue);
  });

  test(
    'confirmed deletion clears authentication even when local storage fails',
    () async {
      final tokens = DeletionTokens()..failClear = true;
      final container = session(DeletionApi(), tokens);
      addTearDown(container.dispose);
      await container
          .read(authControllerProvider.notifier)
          .deleteAccount('current-password');
      expect(container.read(authControllerProvider).isAuthenticated, isFalse);
    },
  );

  testWidgets(
    'requires acknowledgement and password, cancellation sends nothing',
    (tester) async {
      final api = DeletionApi();
      final tokens = DeletionTokens();
      final container = session(api, tokens);
      addTearDown(container.dispose);
      await showScreen(tester, container);
      expect(
        tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
        isNull,
      );
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.byType(FilledButton));
      await tester.tap(find.byType(FilledButton));
      await tester.pumpAndSettle();
      expect(find.text('Enter your current password.'), findsOneWidget);
      expect(api.calls, 0);
      await tester.enterText(find.byType(TextFormField), 'current-password');
      await tester.tap(find.byType(FilledButton));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Keep account'));
      await tester.pumpAndSettle();
      expect(api.calls, 0);
      expect(tokens.token, 'existing-token');
    },
  );

  testWidgets(
    'confirmation sends one request then clears session and booking draft',
    (tester) async {
      final api = DeletionApi()..pending = Completer<void>();
      final tokens = DeletionTokens();
      final container = session(api, tokens);
      addTearDown(container.dispose);
      container.read(bookingFlowProvider.notifier).setPassengers([
        const PassengerDetails(
          seatNumber: '1',
          fullName: 'Private Name',
          mobile: '03001234567',
          cnic: '3520212345671',
          gender: 'male',
        ),
      ]);
      await showScreen(tester, container);
      await reviewAndContinue(tester);
      expect(api.calls, 0);
      await tester.tap(find.text('Permanently delete'));
      await tester.pumpAndSettle();
      expect(api.calls, 1);
      expect(api.submitted, {
        'password': 'current-password',
        'confirmation': 'DELETE',
      });
      expect(tokens.token, 'existing-token');
      expect(
        tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
        isNull,
      );
      expect(
        tester.widget<PopScope>(find.byType(PopScope).first).canPop,
        isFalse,
      );
      api.pending!.complete();
      await tester.pumpAndSettle();
      expect(tokens.token, isNull);
      expect(container.read(authControllerProvider).isAuthenticated, isFalse);
      expect(container.read(bookingFlowProvider).passengers, isEmpty);
      expect(find.text('Login page'), findsOneWidget);
      expect(
        find.text('Your mobile account has been deleted.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets(
    'failed request displays error and permits retry without claiming success',
    (tester) async {
      final api = DeletionApi()
        ..error =
            'Unable to connect. Check your internet connection and try again.';
      final tokens = DeletionTokens();
      final container = session(api, tokens);
      addTearDown(container.dispose);
      await showScreen(tester, container);
      await reviewAndContinue(tester);
      await tester.tap(find.text('Permanently delete'));
      await tester.pumpAndSettle();
      expect(find.text(api.error!), findsOneWidget);
      expect(find.text('Login page'), findsNothing);
      expect(tokens.token, 'existing-token');
      expect(
        tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
        isNotNull,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
