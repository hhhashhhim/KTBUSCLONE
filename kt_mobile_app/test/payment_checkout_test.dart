import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/core/config/app_environment.dart';
import 'package:kt_mobile_app/features/payments/presentation/payment_checkout_screen.dart';
import 'package:webview_flutter_platform_interface/webview_flutter_platform_interface.dart';

import 'payment_test.dart' show booking, FakeTravelRepository, pumpPayment;

Uri checkoutUri() {
  final api = Uri.parse(AppEnvironmentConfig.apiBaseUrl);
  return api.replace(
    scheme: 'https',
    port: api.port,
    path: '${api.path}/payments/payment-test/checkout',
    query: 'signature=test',
  );
}

void main() {
  late FakeCheckoutPlatform platform;
  setUp(() {
    platform = FakeCheckoutPlatform();
    WebViewPlatform.instance = platform;
  });

  testWidgets('checkout stays in app; only the backend can confirm payment', (
    tester,
  ) async {
    final uri = checkoutUri();
    final repository = FakeTravelRepository(
      booking(checkoutUrl: uri.toString()),
    );
    await pumpPayment(tester, repository);
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    expect(find.text('Secure payment'), findsOneWidget);
    expect(find.text('Native payment WebView'), findsOneWidget);
    expect(platform.controller.request!.uri, uri);
    expect(platform.controller.request!.headers, isEmpty);
    expect(platform.controller.javaScript, JavaScriptMode.unrestricted);

    final returned = uri.replace(
      path: uri.path.replaceFirst('/checkout', '/return'),
      query: 'success=true',
    );
    // Foreign origins and other payment IDs cannot close this checkout.
    platform.delegate.finished!(
      returned.replace(host: 'untrusted.example').toString(),
    );
    platform.delegate.finished!(
      returned.toString().replaceFirst('payment-test', 'another-payment'),
    );
    await tester.pumpAndSettle();
    expect(find.text('Secure payment'), findsOneWidget);
    expect(
      await platform.delegate.navigate!(
        NavigationRequest(url: returned.toString(), isMainFrame: true),
      ),
      NavigationDecision.navigate,
    );
    // Let the backend callback load before closing; a success query is not proof.
    expect(find.text('Secure payment'), findsOneWidget);
    platform.delegate.finished!(returned.toString());
    await tester.pumpAndSettle();
    expect(find.text('Secure payment'), findsNothing);
    expect(find.text('Complete your payment'), findsOneWidget);
    expect(find.text('View ticket'), findsNothing);
    expect(repository.refreshes, 3);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('closing checkout refreshes and displays backend confirmation', (
    tester,
  ) async {
    final repository = FakeTravelRepository(
      booking(checkoutUrl: checkoutUri().toString()),
    );
    await pumpPayment(tester, repository);
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    repository.result = booking(state: 'paid');
    await tester.tap(find.byTooltip('Close payment'));
    await tester.pumpAndSettle();
    expect(find.text('Payment received'), findsOneWidget);
    expect(find.text('View ticket'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('main-page network failures allow returning to payment status', (
    tester,
  ) async {
    await pumpPayment(
      tester,
      FakeTravelRepository(booking(checkoutUrl: checkoutUri().toString())),
    );
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    platform.delegate.error!(
      const WebResourceError(
        errorCode: -2,
        description: 'subresource',
        isForMainFrame: false,
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Native payment WebView'), findsOneWidget);
    platform.delegate.error!(
      const WebResourceError(
        errorCode: -2,
        description: 'offline',
        isForMainFrame: true,
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('could not load'), findsOneWidget);
    await tester.tap(find.text('Return to payment status'));
    await tester.pumpAndSettle();
    expect(find.text('Complete your payment'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('checkout blocks insecure and external-app navigation', (
    tester,
  ) async {
    await pumpPayment(
      tester,
      FakeTravelRepository(booking(checkoutUrl: checkoutUri().toString())),
    );
    await tester.tap(find.text('Complete payment'));
    await tester.pumpAndSettle();
    expect(
      await platform.delegate.navigate!(
        const NavigationRequest(
          url: 'https://onlinepayments.jazzcash.com.pk/checkout',
          isMainFrame: true,
        ),
      ),
      NavigationDecision.navigate,
    );
    for (final url in [
      'http://insecure.example',
      'intent://payment',
      'file:///private/data',
    ]) {
      expect(
        await platform.delegate.navigate!(
          NavigationRequest(url: url, isMainFrame: true),
        ),
        NavigationDecision.prevent,
      );
    }
    await tester.pumpAndSettle();
    expect(find.textContaining('cannot open here'), findsOneWidget);
    await tester.tap(find.text('Return to payment status'));
    await tester.pumpAndSettle();
    await tester.pumpWidget(const SizedBox());
  });

  testWidgets('untrusted initial checkout never creates a WebView', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: PaymentCheckoutScreen(
          checkoutUri: Uri.parse(
            'https://untrusted.example/payments/test/checkout?signature=fake',
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('not valid'), findsOneWidget);
    expect(platform.createdController, isFalse);
  });
}

class FakeCheckoutPlatform extends WebViewPlatform {
  late FakeCheckoutController controller;
  late FakeCheckoutDelegate delegate;
  bool createdController = false;
  int controllerCount = 0;

  @override
  PlatformWebViewController createPlatformWebViewController(
    PlatformWebViewControllerCreationParams params,
  ) {
    createdController = true;
    controllerCount++;
    return controller = FakeCheckoutController(params);
  }

  @override
  PlatformNavigationDelegate createPlatformNavigationDelegate(
    PlatformNavigationDelegateCreationParams params,
  ) => delegate = FakeCheckoutDelegate(params);

  @override
  PlatformWebViewWidget createPlatformWebViewWidget(
    PlatformWebViewWidgetCreationParams params,
  ) => FakeCheckoutWidget(params);
}

class FakeCheckoutController extends PlatformWebViewController {
  FakeCheckoutController(super.params) : super.implementation();
  LoadRequestParams? request;
  JavaScriptMode? javaScript;
  @override
  Future<void> setJavaScriptMode(JavaScriptMode mode) async {
    javaScript = mode;
  }

  @override
  Future<void> setPlatformNavigationDelegate(
    PlatformNavigationDelegate handler,
  ) async {}
  @override
  Future<void> loadRequest(LoadRequestParams params) async {
    request = params;
  }
}

class FakeCheckoutDelegate extends PlatformNavigationDelegate {
  FakeCheckoutDelegate(super.params) : super.implementation();
  PageEventCallback? finished;
  NavigationRequestCallback? navigate;
  WebResourceErrorCallback? error;
  @override
  Future<void> setOnPageFinished(PageEventCallback callback) async {
    finished = callback;
  }

  @override
  Future<void> setOnProgress(ProgressCallback callback) async {}
  @override
  Future<void> setOnNavigationRequest(
    NavigationRequestCallback callback,
  ) async {
    navigate = callback;
  }

  @override
  Future<void> setOnWebResourceError(WebResourceErrorCallback callback) async {
    error = callback;
  }
}

class FakeCheckoutWidget extends PlatformWebViewWidget {
  FakeCheckoutWidget(super.params) : super.implementation();
  @override
  Widget build(BuildContext context) =>
      const Center(child: Text('Native payment WebView'));
}
