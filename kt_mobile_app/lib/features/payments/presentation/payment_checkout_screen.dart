import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../../core/config/app_environment.dart';
import '../domain/booking_payment.dart';

/// The provider form stays in a native WebView, without exposing its fields to
/// Flutter or passing passenger API authorization headers to payment providers.
class PaymentCheckoutScreen extends StatefulWidget {
  const PaymentCheckoutScreen({required this.checkoutUri, super.key});

  final Uri checkoutUri;

  @override
  State<PaymentCheckoutScreen> createState() => _PaymentCheckoutScreenState();
}

class _PaymentCheckoutScreenState extends State<PaymentCheckoutScreen> {
  WebViewController? _controller;
  int _progress = 0;
  String? _error;
  bool _closing = false;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    final trusted = trustedCheckoutUri(
      widget.checkoutUri.toString(),
      AppEnvironmentConfig.apiBaseUrl,
      allowLocalSandbox:
          AppEnvironmentConfig.environment == AppEnvironment.development,
    );
    if (trusted == null) {
      _fail('This checkout link is not valid. Close and check payment status.');
      return;
    }
    try {
      final controller = WebViewController();
      await controller.setJavaScriptMode(JavaScriptMode.unrestricted);
      await controller.setNavigationDelegate(
        NavigationDelegate(
          onProgress: (value) {
            if (mounted) setState(() => _progress = value);
          },
          onPageFinished: (url) {
            if (mounted && _isReturnPage(url) && _error == null) _close();
          },
          onWebResourceError: (error) {
            if (error.isForMainFrame == true) {
              _fail(
                'The payment page could not load. Close and check payment status.',
              );
            }
          },
          onNavigationRequest: (request) {
            final uri = Uri.tryParse(request.url);
            final localSandbox =
                trusted.scheme == 'http' &&
                uri?.scheme == 'http' &&
                uri?.host == trusted.host &&
                uri?.port == trusted.port;
            if (uri != null &&
                uri.userInfo.isEmpty &&
                (uri.scheme == 'https' || localSandbox)) {
              // Allow the return request to finish (including POST callbacks)
              // before closing, so the backend can perform its verification.
              return NavigationDecision.navigate;
            }
            if (request.isMainFrame) {
              _fail(
                'This payment link cannot open here. Close and check payment status.',
              );
            }
            return NavigationDecision.prevent;
          },
        ),
      );
      if (!mounted) return;
      setState(() => _controller = controller);
      await controller.loadRequest(trusted);
    } catch (_) {
      _fail('The payment page could not open. Close and check payment status.');
    }
  }

  bool _isReturnPage(String url) {
    final uri = Uri.tryParse(url);
    final checkout = widget.checkoutUri;
    return uri != null &&
        uri.scheme == checkout.scheme &&
        uri.host == checkout.host &&
        uri.port == checkout.port &&
        uri.userInfo.isEmpty &&
        uri.path ==
            checkout.path.replaceFirst(RegExp(r'/checkout$'), '/return');
  }

  void _fail(String message) {
    if (mounted && !_closing) setState(() => _error = message);
  }

  void _close() {
    if (!mounted || _closing) return;
    _closing = true;
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('Secure payment'),
      leading: IconButton(
        tooltip: 'Close payment',
        icon: const Icon(Icons.close),
        onPressed: _close,
      ),
    ),
    body: SafeArea(
      child: _error != null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(_error!, textAlign: TextAlign.center),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _close,
                      child: const Text('Return to payment status'),
                    ),
                  ],
                ),
              ),
            )
          : Column(
              children: [
                if (_progress < 100)
                  LinearProgressIndicator(value: _progress / 100),
                if (_controller != null)
                  Expanded(child: WebViewWidget(controller: _controller!)),
              ],
            ),
    ),
  );
}
