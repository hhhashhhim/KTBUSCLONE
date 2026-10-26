import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_colors.dart';

class JourneySearchHeader extends StatelessWidget {
  const JourneySearchHeader({
    required this.onBack,
    required this.tripTypes,
    super.key,
  });

  final VoidCallback onBack;
  final Widget tripTypes;

  @override
  Widget build(BuildContext context) => AnnotatedRegion<SystemUiOverlayStyle>(
    value: SystemUiOverlayStyle.light,
    child: DecoratedBox(
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(36)),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF219B56), Color(0xFF08723D), Color(0xFF03492D)],
          stops: [0, .5, 1],
        ),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(36)),
        child: Stack(
          children: [
            Positioned(
              right: -65,
              top: 30,
              child: ExcludeSemantics(
                child: Container(
                  width: 260,
                  height: 260,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: const Color(0x187EEDAB),
                      width: 36,
                    ),
                  ),
                ),
              ),
            ),
            SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 48),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(15),
                            border: Border.all(color: const Color(0x408DE8AE)),
                            gradient: const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [Color(0xFF41AB6C), Color(0xFF187C49)],
                            ),
                            boxShadow: const [
                              BoxShadow(
                                color: Color(0x35002D19),
                                offset: Offset(0, 3),
                                blurRadius: 6,
                              ),
                            ],
                          ),
                          child: IconButton(
                            tooltip: 'Back to home',
                            onPressed: onBack,
                            icon: const Icon(
                              Icons.arrow_back_rounded,
                              size: 21,
                            ),
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Text(
                            'Search buses',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 17,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 22),
                    Row(
                      children: [
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Where will you\ngo next?',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 29,
                                  height: 1.12,
                                  letterSpacing: -.9,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              SizedBox(height: 9),
                              Text(
                                'A great journey starts here.',
                                style: TextStyle(
                                  color: Color(0xFFD4ECDD),
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        const SizedBox(
                          width: 106,
                          height: 104,
                          child: JourneyCoachIllustration(),
                        ),
                      ],
                    ),
                    const SizedBox(height: 22),
                    tripTypes,
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

/// A solid lower edge, a lit face and a soft shadow give the form its depth.
class JourneySearchCard extends StatelessWidget {
  const JourneySearchCard({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(19),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(28),
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Colors.white, Color(0xFFF9FCFA)],
      ),
      border: Border.all(color: Colors.white, width: 1.5),
      boxShadow: const [
        BoxShadow(
          color: Color(0x23244D35),
          blurRadius: 28,
          offset: Offset(0, 16),
        ),
        BoxShadow(color: Color(0xFFD5E4DA), offset: Offset(0, 5)),
      ],
    ),
    child: child,
  );
}

class JourneyField extends StatelessWidget {
  const JourneyField({
    required this.label,
    required this.value,
    required this.icon,
    required this.onTap,
    this.gold = false,
    this.enabled = true,
    this.isDate = false,
    super.key,
  });

  final String label;
  final String value;
  final IconData icon;
  final VoidCallback? onTap;
  final bool gold;
  final bool enabled;
  final bool isDate;

  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    enabled: enabled,
    child: Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(19),
        boxShadow: const [BoxShadow(color: Colors.white, offset: Offset(0, 2))],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(19),
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(19),
            border: Border.all(color: const Color(0xFFDDE8E0)),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFFEAF1EC), Color(0xFFF4F8F5)],
            ),
          ),
          child: InkWell(
            onTap: enabled ? onTap : null,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 15),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 46,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(14),
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: gold
                            ? const [Color(0xFFF8DE9C), Color(0xFFBA8731)]
                            : const [Color(0xFF5BC888), Color(0xFF158049)],
                      ),
                      border: Border.all(
                        color: gold
                            ? const Color(0xFFFFEBC2)
                            : const Color(0xFF9AE2B4),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: gold
                              ? const Color(0xFF9A702E)
                              : const Color(0xFF14633C),
                          offset: const Offset(0, 2),
                        ),
                        const BoxShadow(
                          color: Color(0x20225033),
                          offset: Offset(0, 5),
                          blurRadius: 7,
                        ),
                      ],
                    ),
                    child: Icon(
                      icon,
                      color: gold ? const Color(0xFF66481C) : Colors.white,
                      size: 23,
                    ),
                  ),
                  const SizedBox(width: 13),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          label,
                          style: const TextStyle(
                            color: Color(0xFF607668),
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 1.2,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          value,
                          style: TextStyle(
                            color: enabled
                                ? AppColors.foreground
                                : AppColors.muted,
                            fontSize: isDate ? 14 : 16,
                            fontWeight: FontWeight.w800,
                            height: 1.2,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(
                    isDate
                        ? Icons.chevron_right_rounded
                        : Icons.expand_more_rounded,
                    color: const Color(0xFF71897A),
                    size: 20,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

class JourneySearchButton extends StatefulWidget {
  const JourneySearchButton({required this.onPressed, super.key});

  final VoidCallback onPressed;

  @override
  State<JourneySearchButton> createState() => _JourneySearchButtonState();
}

class _JourneySearchButtonState extends State<JourneySearchButton> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) => AnimatedContainer(
    duration: MediaQuery.disableAnimationsOf(context)
        ? Duration.zero
        : const Duration(milliseconds: 110),
    transform: Matrix4.translationValues(0, _pressed ? 3 : 0, 0),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(18),
      boxShadow: [
        BoxShadow(
          color: const Color(0x30087B3E),
          blurRadius: _pressed ? 5 : 14,
          offset: Offset(0, _pressed ? 3 : 8),
        ),
        BoxShadow(
          color: const Color(0xFF04582C),
          offset: Offset(0, _pressed ? 1 : 4),
        ),
      ],
    ),
    child: Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(18),
      clipBehavior: Clip.antiAlias,
      child: Ink(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFF6BCA8B)),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF2CAF61), Color(0xFF09843E), Color(0xFF076D35)],
          ),
        ),
        child: InkWell(
          onTap: widget.onPressed,
          onHighlightChanged: (value) => setState(() => _pressed = value),
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 18, vertical: 17),
            child: Row(
              children: [
                Icon(Icons.search_rounded, color: Colors.white, size: 22),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Search buses',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Icon(
                  Icons.arrow_forward_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

/// Shared, decorative perspective coach for the search and results screens.
class JourneyCoachIllustration extends StatelessWidget {
  const JourneyCoachIllustration({super.key});

  @override
  Widget build(BuildContext context) =>
      const ExcludeSemantics(child: CustomPaint(painter: _CoachPainter()));
}

/// A decorative coach drawn as lit roof, side and front planes.
class _CoachPainter extends CustomPainter {
  const _CoachPainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.save();
    canvas.scale(size.width / 120, size.height / 112);
    final shadow = Paint()
      ..color = const Color(0x65002013)
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 7);
    canvas.drawOval(const Rect.fromLTWH(7, 84, 108, 16), shadow);

    void plane(List<Offset> points, List<Color> colors) {
      final path = Path()..addPolygon(points, true);
      canvas.drawPath(
        path,
        Paint()
          ..shader = LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: colors,
          ).createShader(path.getBounds()),
      );
    }

    // Roof, passenger side and front face share their edges in perspective.
    plane(
      const [Offset(9, 30), Offset(41, 12), Offset(110, 43), Offset(79, 62)],
      const [Color(0xFFFFFFFF), Color(0xFFCBDDD3)],
    );
    plane(
      const [Offset(9, 30), Offset(79, 62), Offset(79, 95), Offset(9, 62)],
      const [Color(0xFFE6F4EA), Color(0xFF8EBAA3)],
    );
    plane(
      const [Offset(79, 62), Offset(110, 43), Offset(110, 77), Offset(79, 95)],
      const [Color(0xFFF4FFF7), Color(0xFFB9D4C5)],
    );

    // Continuous dark glazing with individual window dividers.
    plane(
      const [Offset(13, 35), Offset(74, 63), Offset(74, 78), Offset(13, 50)],
      const [Color(0xFF346E67), Color(0xFF092F35)],
    );
    plane(
      const [Offset(83, 64), Offset(106, 50), Offset(106, 66), Offset(83, 80)],
      const [Color(0xFF4B8781), Color(0xFF0E3940)],
    );
    final divider = Paint()
      ..color = const Color(0xFFA7C9B7)
      ..strokeWidth = 1.5;
    for (var x = 25.0; x < 72; x += 12) {
      final y = 35 + (x - 13) * 28 / 61;
      canvas.drawLine(Offset(x, y), Offset(x, y + 15), divider);
    }
    plane(
      const [Offset(10, 53), Offset(78, 84), Offset(78, 92), Offset(10, 61)],
      const [Color(0xFF26A865), Color(0xFF096D3E)],
    );
    plane(
      const [Offset(80, 85), Offset(109, 68), Offset(109, 76), Offset(80, 93)],
      const [Color(0xFF168451), Color(0xFF045831)],
    );
    canvas.drawLine(
      const Offset(13, 53),
      const Offset(74, 81),
      Paint()
        ..color = const Color(0xFFE4BE64)
        ..strokeWidth = 2,
    );
    for (final wheel in const [Offset(25, 67), Offset(66, 86)]) {
      canvas.drawOval(
        Rect.fromCenter(center: wheel, width: 11, height: 16),
        Paint()..color = const Color(0xFF132E28),
      );
      canvas.drawOval(
        Rect.fromCenter(center: wheel, width: 5, height: 9),
        Paint()..color = const Color(0xFFB8CEC1),
      );
    }
    final light = Paint()
      ..color = const Color(0xFFFFE6A2)
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round;
    canvas.drawLine(const Offset(84, 84), const Offset(89, 81), light);
    canvas.drawLine(const Offset(100, 75), const Offset(105, 72), light);
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _CoachPainter oldDelegate) => false;
}
