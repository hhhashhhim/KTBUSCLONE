import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import 'notification_repository.dart';

/// The inset highlight and solid lower edge give alerts a raised surface.
class AlertSurface extends StatelessWidget {
  const AlertSurface({
    required this.child,
    super.key,
    this.onTap,
    this.padding = const EdgeInsets.all(20),
    this.highlighted = false,
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final bool highlighted;

  @override
  Widget build(BuildContext context) {
    const radius = BorderRadius.all(Radius.circular(26));
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: radius,
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryDeep.withValues(alpha: 0.07),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
          BoxShadow(
            color: highlighted
                ? const Color(0xFFBEDCC7)
                : const Color(0xFFDCE9DF),
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: radius,
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: radius,
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Colors.white,
                highlighted ? const Color(0xFFEDF8EF) : const Color(0xFFF7FAF7),
              ],
            ),
            border: Border.all(color: Colors.white, width: 1.5),
          ),
          child: InkWell(
            onTap: onTap,
            borderRadius: radius,
            child: Padding(padding: padding, child: child),
          ),
        ),
      ),
    );
  }
}

class AlertsInboxHeading extends StatelessWidget {
  const AlertsInboxHeading({required this.unread, super.key});

  final int unread;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      const Expanded(
        child: Text(
          'Your inbox',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.5,
            color: AppColors.foreground,
          ),
        ),
      ),
      const SizedBox(width: 12),
      ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 160),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFFE7F2E9),
            borderRadius: BorderRadius.circular(30),
            border: Border.all(color: const Color(0xFFD1E6D7)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 6,
                height: 6,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 7),
              Flexible(
                child: Text(
                  '$unread unread',
                  style: const TextStyle(
                    color: AppColors.primaryDeep,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    ],
  );
}

class AlertsEmptyState extends StatelessWidget {
  const AlertsEmptyState({required this.onViewTrips, super.key});

  final VoidCallback onViewTrips;

  @override
  Widget build(BuildContext context) => AlertSurface(
    padding: const EdgeInsets.fromLTRB(24, 12, 24, 28),
    child: Column(
      children: [
        const ExcludeSemantics(
          child: SizedBox(
            height: 228,
            width: 280,
            child: CustomPaint(painter: _SculptedBellPainter()),
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
          decoration: BoxDecoration(
            color: const Color(0xFFE7F3E9),
            borderRadius: BorderRadius.circular(20),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.check_circle_rounded,
                size: 14,
                color: AppColors.primaryDark,
              ),
              SizedBox(width: 6),
              Flexible(
                child: Text(
                  'UP TO DATE',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: AppColors.primaryDeep,
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 1.1,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        const Text(
          'You’re all caught up',
          textAlign: TextAlign.center,
          style: TextStyle(
            color: AppColors.foreground,
            fontSize: 25,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.8,
            height: 1.2,
          ),
        ),
        const SizedBox(height: 12),
        const Text(
          'Booking updates and announcements will appear here.',
          textAlign: TextAlign.center,
          style: TextStyle(color: Color(0xFF607568), fontSize: 14, height: 1.6),
        ),
        const SizedBox(height: 26),
        Semantics(
          button: true,
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(17),
              boxShadow: const [
                BoxShadow(color: Color(0xFF034921), offset: Offset(0, 3)),
                BoxShadow(
                  color: Color(0x26277546),
                  blurRadius: 14,
                  offset: Offset(0, 7),
                ),
              ],
            ),
            child: Material(
              color: Colors.transparent,
              borderRadius: BorderRadius.circular(17),
              clipBehavior: Clip.antiAlias,
              child: Ink(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(17),
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF249C51), Color(0xFF07632D)],
                  ),
                  border: Border.all(color: const Color(0xFF4AA76A)),
                ),
                child: InkWell(
                  onTap: onViewTrips,
                  child: const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 18, vertical: 16),
                    child: Row(
                      children: [
                        Icon(
                          Icons.confirmation_number_outlined,
                          color: Colors.white,
                          size: 20,
                        ),
                        SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'View my trips',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        Icon(
                          Icons.arrow_forward_rounded,
                          color: Color(0xFFC9EED5),
                          size: 18,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    ),
  );
}

class AlertInboxCard extends StatelessWidget {
  const AlertInboxCard({
    required this.item,
    required this.timeLabel,
    required this.onTap,
    super.key,
  });

  final AppNotification item;
  final String timeLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final unread = item.readAt == null;
    return Semantics(
      button: true,
      child: AlertSurface(
        onTap: onTap,
        highlighted: unread,
        padding: const EdgeInsets.all(18),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44,
              height: 48,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(15),
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: unread
                      ? [const Color(0xFF4EB873), const Color(0xFF08713A)]
                      : [const Color(0xFFEAF3EC), const Color(0xFFD5E5DA)],
                ),
                border: Border.all(
                  color: unread
                      ? const Color(0xFF8ED1A4)
                      : const Color(0xFFF5FAF6),
                ),
                boxShadow: [
                  BoxShadow(
                    color: unread
                        ? const Color(0xFF0F6635)
                        : const Color(0xFFC6D8CB),
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Icon(
                item.bookingId != null
                    ? Icons.confirmation_number_outlined
                    : Icons.notifications_none_rounded,
                color: unread ? Colors.white : AppColors.primaryDeep,
                size: 23,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (unread) ...[
                    const Text(
                      'NEW UPDATE',
                      style: TextStyle(
                        color: AppColors.primaryDark,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.8,
                      ),
                    ),
                    const SizedBox(height: 5),
                  ],
                  Text(
                    item.title,
                    style: const TextStyle(
                      color: AppColors.foreground,
                      fontSize: 15,
                      height: 1.35,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item.body,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Color(0xFF607568),
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    timeLabel,
                    style: const TextStyle(
                      color: Color(0xFF607568),
                      fontSize: 11,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            const Padding(
              padding: EdgeInsets.only(top: 12),
              child: Icon(
                Icons.chevron_right_rounded,
                size: 20,
                color: AppColors.primaryDark,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// A vector illustration keeps the sculpted bell sharp at every screen density.
/// Gradients, reflected light and cast shadows create depth without a 3D engine.
class _SculptedBellPainter extends CustomPainter {
  const _SculptedBellPainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.save();
    final scale = size.width / 280;
    canvas.translate(0, (size.height - 228 * scale) / 2);
    canvas.scale(scale);
    const halo = Rect.fromLTWH(24, 0, 232, 210);
    canvas.drawOval(
      halo,
      Paint()
        ..shader = const RadialGradient(
          colors: [Color(0xFFE2F4E6), Color(0x00E2F4E6)],
        ).createShader(halo),
    );
    canvas.drawOval(
      const Rect.fromLTWH(34, 28, 212, 158),
      Paint()
        ..color = const Color(0xFFDCEDE1)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1,
    );
    canvas.drawOval(
      const Rect.fromLTWH(63, 201, 158, 13),
      Paint()
        ..color = const Color(0x30205C35)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 9),
    );
    // The pedestal has a visible side wall and a lit elliptical top face.
    const pedestal = Rect.fromLTWH(68, 177, 146, 29);
    canvas.drawRRect(
      RRect.fromRectAndRadius(pedestal, const Radius.circular(70)),
      Paint()
        ..shader = const LinearGradient(
          colors: [Color(0xFFD0E7D6), Color(0xFFAACDB6), Color(0xFFE5F2E7)],
        ).createShader(pedestal),
    );
    const top = Rect.fromLTWH(68, 169, 146, 27);
    canvas.drawOval(
      top,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFFFAFFFB), Color(0xFFDCEFE1)],
        ).createShader(top),
    );
    canvas.drawOval(
      top.deflate(0.6),
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.2,
    );
    canvas.drawOval(
      const Rect.fromLTWH(107, 178, 67, 8),
      Paint()
        ..color = const Color(0x3D176D36)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 6),
    );

    canvas.save();
    canvas.translate(140, 103);
    canvas.rotate(-0.18);
    const loop = Rect.fromLTWH(-10, -75, 20, 25);
    canvas.drawOval(
      loop,
      Paint()
        ..shader = const LinearGradient(
          colors: [Color(0xFF97D9A7), Color(0xFF15763B)],
        ).createShader(loop)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 7,
    );
    final bell = Path()
      ..moveTo(-49, 30)
      ..cubicTo(-36, 16, -40, -7, -32, -27)
      ..cubicTo(-22, -54, 20, -55, 32, -28)
      ..cubicTo(41, -8, 35, 15, 49, 30)
      ..quadraticBezierTo(55, 40, 40, 42)
      ..lineTo(-40, 42)
      ..quadraticBezierTo(-55, 40, -49, 30)
      ..close();
    canvas.drawShadow(
      bell.shift(const Offset(3, 6)),
      const Color(0x55204A2B),
      12,
      false,
    );
    canvas.drawPath(
      bell.shift(const Offset(4, 4)),
      Paint()..color = const Color(0xFF09632C),
    );
    canvas.drawPath(
      bell,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFFB2E8AA),
            Color(0xFF64C57B),
            Color(0xFF1F9A4B),
            Color(0xFF08622F),
          ],
          stops: [0, 0.3, 0.65, 1],
        ).createShader(const Rect.fromLTWH(-50, -50, 100, 100)),
    );
    canvas.save();
    canvas.clipPath(bell);
    canvas.drawOval(
      const Rect.fromLTWH(-31, -42, 29, 61),
      Paint()
        ..color = const Color(0x8AEEFFE7)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 13),
    );
    canvas.drawPath(
      Path()
        ..moveTo(-29, 12)
        ..cubicTo(-29, -8, -29, -24, -16, -32),
      Paint()
        ..color = const Color(0xA6E3F9D5)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 4
        ..strokeCap = StrokeCap.round,
    );
    canvas.restore();
    const clapper = Rect.fromLTWH(-12, 37, 25, 25);
    canvas.drawOval(
      clapper,
      Paint()
        ..shader = const RadialGradient(
          center: Alignment(-0.4, -0.5),
          colors: [Color(0xFFFFE6A0), Color(0xFFE7B344), Color(0xFFAE7C1C)],
        ).createShader(clapper),
    );
    const rim = Rect.fromLTWH(-54, 26, 108, 21);
    canvas.drawRRect(
      RRect.fromRectAndRadius(rim, const Radius.circular(12)),
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF89D794), Color(0xFF289D50), Color(0xFF075A2A)],
          stops: [0, 0.4, 1],
        ).createShader(rim),
    );
    canvas.drawPath(
      Path()
        ..moveTo(-43, 30)
        ..quadraticBezierTo(-3, 28, 40, 30),
      Paint()
        ..color = const Color(0x8AC4EFBA)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.6
        ..strokeCap = StrokeCap.round,
    );
    canvas.restore();

    // A small floating confirmation tile reinforces the empty inbox state.
    canvas.save();
    canvas.translate(218, 64);
    canvas.rotate(0.15);
    const tile = Rect.fromLTWH(-21, -21, 42, 42);
    final tileShape = RRect.fromRectAndRadius(tile, const Radius.circular(13));
    canvas.drawShadow(
      Path()..addRRect(tileShape),
      const Color(0x33265D36),
      8,
      false,
    );
    canvas.drawRRect(
      tileShape.shift(const Offset(0, 3)),
      Paint()..color = const Color(0xFFB7D6C0),
    );
    canvas.drawRRect(
      tileShape,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.white, Color(0xFFE6F4E9)],
        ).createShader(tile),
    );
    canvas.drawRRect(
      tileShape.deflate(0.7),
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.3,
    );
    canvas.drawPath(
      Path()
        ..moveTo(-8, 0)
        ..lineTo(-2, 6)
        ..lineTo(9, -6),
      Paint()
        ..color = const Color(0xFF26894B)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 4
        ..strokeJoin = StrokeJoin.round
        ..strokeCap = StrokeCap.round,
    );
    canvas.restore();
    for (final sphere in [
      (
        center: const Offset(47, 78),
        radius: 7.0,
        color: const Color(0xFF65B980),
      ),
      (
        center: const Offset(230, 151),
        radius: 5.0,
        color: const Color(0xFFE9BC5C),
      ),
    ]) {
      final rect = Rect.fromCircle(
        center: sphere.center,
        radius: sphere.radius,
      );
      canvas.drawCircle(
        sphere.center,
        sphere.radius,
        Paint()
          ..shader = RadialGradient(
            center: const Alignment(-0.45, -0.5),
            colors: [Colors.white, sphere.color],
          ).createShader(rect),
      );
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(_SculptedBellPainter oldDelegate) => false;
}
