import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

/// A highlighted face and a solid lower edge give account strips their depth.
class AccountSurface extends StatefulWidget {
  const AccountSurface({
    required this.child,
    super.key,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.gradient = const LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [Colors.white, Color(0xFFF5F9F6)],
    ),
    this.edgeColor = const Color(0xFFD3E1D7),
    this.borderColor = Colors.white,
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final Gradient gradient;
  final Color edgeColor;
  final Color borderColor;

  @override
  State<AccountSurface> createState() => _AccountSurfaceState();
}

class _AccountSurfaceState extends State<AccountSurface> {
  bool _pressed = false;
  static const _radius = BorderRadius.all(Radius.circular(20));

  @override
  Widget build(BuildContext context) => AnimatedContainer(
    duration: MediaQuery.disableAnimationsOf(context)
        ? Duration.zero
        : const Duration(milliseconds: 120),
    curve: Curves.easeOut,
    transform: Matrix4.translationValues(0, _pressed ? 2 : 0, 0),
    decoration: BoxDecoration(
      borderRadius: _radius,
      boxShadow: [
        BoxShadow(
          color: const Color(
            0xFF234E35,
          ).withValues(alpha: _pressed ? 0.07 : 0.10),
          blurRadius: _pressed ? 8 : 16,
          offset: Offset(0, _pressed ? 3 : 8),
        ),
        BoxShadow(color: widget.edgeColor, offset: Offset(0, _pressed ? 1 : 4)),
      ],
    ),
    child: Material(
      color: Colors.transparent,
      borderRadius: _radius,
      clipBehavior: Clip.antiAlias,
      child: Ink(
        decoration: BoxDecoration(
          gradient: widget.gradient,
          borderRadius: _radius,
          border: Border.all(color: widget.borderColor),
        ),
        child: InkWell(
          onTap: widget.onTap,
          onHighlightChanged: widget.onTap == null
              ? null
              : (pressed) => setState(() => _pressed = pressed),
          borderRadius: _radius,
          child: Padding(padding: widget.padding, child: widget.child),
        ),
      ),
    ),
  );
}

class AccountIconBadge extends StatelessWidget {
  const AccountIconBadge(this.icon, {super.key});

  final IconData icon;

  @override
  Widget build(BuildContext context) => Container(
    width: 46,
    height: 46,
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(14),
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF59BD7A), Color(0xFF218C4A), AppColors.primaryDeep],
        stops: [0, 0.5, 1],
      ),
      border: Border.all(color: const Color(0xFF9BD4AC)),
      boxShadow: const [
        BoxShadow(
          color: Color(0x24215B36),
          blurRadius: 8,
          offset: Offset(0, 5),
        ),
        BoxShadow(color: Color(0xFF176B37), offset: Offset(0, 2)),
      ],
    ),
    child: Icon(
      icon,
      size: 25,
      color: Colors.white,
      shadows: const [
        Shadow(color: Color(0x33204A2D), offset: Offset(0, 1), blurRadius: 2),
      ],
    ),
  );
}
