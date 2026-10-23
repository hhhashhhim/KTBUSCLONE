import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class LegalDocumentScreen extends StatefulWidget {
  const LegalDocumentScreen({
    required this.title,
    required this.uri,
    super.key,
  });

  final String title;
  final Uri uri;

  @override
  State<LegalDocumentScreen> createState() => _LegalDocumentScreenState();
}

class _LegalDocumentScreenState extends State<LegalDocumentScreen> {
  WebViewController? _controller;
  int _progress = 0;
  bool _failed = false;
  late Uri _pageUri;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _failed = false;
      _progress = 0;
      _pageUri = widget.uri;
    });
    try {
      final controller = _controller ?? WebViewController();
      if (_controller == null) {
        await controller.setJavaScriptMode(JavaScriptMode.unrestricted);
        await controller.setNavigationDelegate(
          NavigationDelegate(
            onPageStarted: (url) {
              if (!mounted) return;
              setState(() {
                _pageUri = Uri.tryParse(url) ?? widget.uri;
                _progress = 0;
              });
            },
            onProgress: (progress) {
              if (mounted) setState(() => _progress = progress);
            },
            onPageFinished: (_) {
              if (mounted) setState(() => _progress = 100);
            },
            onWebResourceError: (error) {
              if (error.isForMainFrame != false) _showError();
            },
            onHttpError: (error) {
              // Ignore failed images and other resources within the document.
              if (error.request?.uri == _pageUri ||
                  error.response?.uri == _pageUri) {
                _showError();
              }
            },
            onNavigationRequest: (request) {
              final uri = Uri.tryParse(request.url);
              return uri != null &&
                      uri.scheme == 'https' &&
                      uri.host.isNotEmpty &&
                      uri.userInfo.isEmpty
                  ? NavigationDecision.navigate
                  : NavigationDecision.prevent;
            },
          ),
        );
        if (!mounted) return;
        setState(() => _controller = controller);
      }
      await controller.loadRequest(widget.uri);
    } catch (_) {
      _showError();
    }
  }

  void _showError() {
    if (mounted) setState(() => _failed = true);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.title)),
    body: SafeArea(
      child: _failed
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Unable to load ${widget.title}. Check your connection and try again.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    FilledButton(onPressed: _load, child: const Text('Retry')),
                  ],
                ),
              ),
            )
          : Column(
              children: [
                if (_progress < 100)
                  LinearProgressIndicator(
                    value: _progress == 0 ? null : _progress / 100,
                    semanticsLabel: 'Loading ${widget.title}',
                  ),
                if (_controller != null)
                  Expanded(child: WebViewWidget(controller: _controller!)),
              ],
            ),
    ),
  );
}
