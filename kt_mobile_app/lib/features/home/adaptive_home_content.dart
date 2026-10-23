import 'package:flutter/material.dart';

/// Gives the trip card up first, then the banner, when Home exceeds its viewport.
/// An imminent confirmed trip keeps its place even when scrolling is necessary.
class AdaptiveHomeContent extends StatefulWidget {
  const AdaptiveHomeContent({
    super.key,
    required this.header,
    required this.banner,
    required this.search,
    required this.nextTrip,
    required this.content,
    required this.keepNextTripVisible,
  });

  final Widget header;
  final Widget banner;
  final Widget search;
  final Widget? nextTrip;
  final Widget content;
  final bool keepNextTripVisible;

  @override
  State<AdaptiveHomeContent> createState() => _AdaptiveHomeContentState();
}

class _AdaptiveHomeContentState extends State<AdaptiveHomeContent> {
  static const _padding = EdgeInsets.fromLTRB(16, 12, 16, 24);
  final _contentKey = GlobalKey();
  final _bannerKey = GlobalKey();
  final _tripKey = GlobalKey();
  bool _showBanner = true;
  bool _showNextTrip = true;
  bool _measurementScheduled = false;
  double _viewportHeight = 0;

  void _scheduleMeasurement() {
    if (_measurementScheduled) return;
    _measurementScheduled = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _measurementScheduled = false;
      if (!mounted) return;
      final contentHeight = _contentKey.currentContext?.size?.height;
      final bannerHeight = _bannerKey.currentContext?.size?.height;
      final tripHeight = _tripKey.currentContext?.size?.height;
      if (contentHeight == null || bannerHeight == null || tripHeight == null) {
        return;
      }

      // Offstage children retain their natural sizes. Restore those heights to
      // the budget so hiding a section cannot make it toggle on the next frame.
      var requiredHeight = contentHeight + _padding.vertical;
      if (!_showBanner) requiredHeight += bannerHeight;
      if (!_showNextTrip) requiredHeight += tripHeight;
      final showNextTrip =
          widget.keepNextTripVisible || requiredHeight <= _viewportHeight + .5;
      if (!showNextTrip) requiredHeight -= tripHeight;
      final showBanner = requiredHeight <= _viewportHeight + .5;
      if (_showNextTrip != showNextTrip || _showBanner != showBanner) {
        setState(() {
          _showNextTrip = showNextTrip;
          _showBanner = showBanner;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      _viewportHeight = constraints.maxHeight;
      _scheduleMeasurement();
      return NotificationListener<SizeChangedLayoutNotification>(
        onNotification: (_) {
          _scheduleMeasurement();
          return false;
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: _padding,
          child: SizeChangedLayoutNotifier(
            child: Column(
              key: _contentKey,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                widget.header,
                Offstage(
                  offstage: !_showBanner,
                  child: SizeChangedLayoutNotifier(
                    child: SizedBox(key: _bannerKey, child: widget.banner),
                  ),
                ),
                widget.search,
                Offstage(
                  offstage: !_showNextTrip,
                  child: ExcludeFocus(
                    excluding: !_showNextTrip,
                    child: SizeChangedLayoutNotifier(
                      child: SizedBox(key: _tripKey, child: widget.nextTrip),
                    ),
                  ),
                ),
                widget.content,
              ],
            ),
          ),
        ),
      );
    },
  );
}
