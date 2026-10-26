import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../core/theme/app_colors.dart';
import '../travel/domain/travel_models.dart';
import '../travel/presentation/lovable_search_flow.dart';

/// A paper ticket with a raised lower edge and a tactile pressed state.
class TripTicketCard extends StatefulWidget {
  const TripTicketCard({required this.booking, required this.onTap, super.key});

  final BookingSummary booking;
  final VoidCallback onTap;

  @override
  State<TripTicketCard> createState() => _TripTicketCardState();
}

class _TripTicketCardState extends State<TripTicketCard> {
  bool _pressed = false;
  static const _radius = BorderRadius.all(Radius.circular(24));

  @override
  Widget build(BuildContext context) {
    final booking = widget.booking;
    final status = booking.status.replaceAll('_', ' ').trim();
    final statusLabel = status.isEmpty
        ? 'Status unavailable'
        : '${status[0].toUpperCase()}${status.substring(1)}';
    final statusColor = switch (booking.status.toLowerCase()) {
      'confirmed' || 'paid' || 'completed' => AppColors.primaryDark,
      'cancelled' || 'canceled' || 'failed' => const Color(0xFFA63E36),
      'pending' || 'reserved' => const Color(0xFF885C13),
      _ => const Color(0xFF52675D),
    };

    return AnimatedContainer(
      duration: MediaQuery.disableAnimationsOf(context)
          ? Duration.zero
          : const Duration(milliseconds: 120),
      curve: Curves.easeOut,
      transform: Matrix4.translationValues(0, _pressed ? 3 : 0, 0),
      decoration: BoxDecoration(
        borderRadius: _radius,
        boxShadow: [
          BoxShadow(
            color: const Color(
              0xFF244B33,
            ).withValues(alpha: _pressed ? .08 : .13),
            blurRadius: _pressed ? 9 : 20,
            offset: Offset(0, _pressed ? 4 : 11),
          ),
          BoxShadow(
            color: const Color(0xFFCEDBD1),
            offset: Offset(0, _pressed ? 2 : 5),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: _radius,
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: _radius,
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Colors.white, Color(0xFFF7FAF8)],
            ),
            border: Border.all(color: Colors.white, width: 1.5),
          ),
          child: InkWell(
            onTap: widget.onTap,
            onHighlightChanged: (pressed) => setState(() => _pressed = pressed),
            borderRadius: _radius,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 0),
                  child: Wrap(
                    alignment: WrapAlignment.spaceBetween,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    spacing: 10,
                    runSpacing: 8,
                    children: [
                      Text(
                        booking.reference,
                        style: const TextStyle(
                          color: AppColors.primaryDark,
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          letterSpacing: .8,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 5,
                        ),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: .09),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: statusColor.withValues(alpha: .13),
                          ),
                        ),
                        child: Text(
                          statusLabel,
                          style: TextStyle(
                            color: statusColor,
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                  child: Row(
                    children: [
                      Expanded(
                        child: _RouteCity(
                          label: 'FROM',
                          city: booking.originName,
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 12),
                        child: _RouteEmblem(),
                      ),
                      Expanded(
                        child: _RouteCity(
                          label: 'TO',
                          city: booking.destinationName,
                          alignEnd: true,
                        ),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 10),
                  child: Wrap(
                    spacing: 18,
                    runSpacing: 8,
                    children: [
                      _TicketMetadata(
                        icon: Icons.calendar_today_outlined,
                        text: DateFormat(
                          'EEE, d MMM yyyy',
                        ).format(booking.departureAt),
                      ),
                      _TicketMetadata(
                        icon: Icons.schedule_rounded,
                        text: DateFormat('h:mm a').format(booking.departureAt),
                      ),
                    ],
                  ),
                ),
                // The circles cut visually into the paper at the perforation.
                SizedBox(
                  height: 20,
                  child: Stack(
                    clipBehavior: Clip.none,
                    children: [
                      const Positioned.fill(
                        left: 22,
                        right: 22,
                        child: CustomPaint(painter: _PerforationPainter()),
                      ),
                      for (final left in [true, false])
                        Positioned(
                          left: left ? -10 : null,
                          right: left ? null : -10,
                          child: Container(
                            width: 20,
                            height: 20,
                            decoration: const BoxDecoration(
                              color: Color(0xFFF0F5F1),
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: Color(0x16244B33),
                                  blurRadius: 2,
                                  offset: Offset(0, 1),
                                ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 9, 20, 18),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              booking.seats.length == 1 ? 'SEAT' : 'SEATS',
                              style: _labelStyle,
                            ),
                            const SizedBox(height: 5),
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(
                                  Icons.event_seat_rounded,
                                  color: AppColors.primaryDark,
                                  size: 17,
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    booking.seats.isEmpty
                                        ? 'Not assigned'
                                        : booking.seats.join(', '),
                                    style: const TextStyle(
                                      color: AppColors.foreground,
                                      fontWeight: FontWeight.w800,
                                      fontSize: 14,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('TOTAL FARE', style: _labelStyle),
                            const SizedBox(height: 5),
                            Text(
                              formatPkr(booking.total),
                              textAlign: TextAlign.end,
                              style: const TextStyle(
                                color: AppColors.primaryDeep,
                                fontSize: 19,
                                fontWeight: FontWeight.w800,
                                letterSpacing: -.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 14),
                      Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: AppColors.primarySoft,
                          borderRadius: BorderRadius.circular(11),
                          border: Border.all(color: const Color(0xFFD4E7D8)),
                        ),
                        child: const Icon(
                          Icons.arrow_forward_rounded,
                          color: AppColors.primaryDark,
                          size: 17,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

const _labelStyle = TextStyle(
  color: Color(0xFF64776B),
  fontSize: 9,
  fontWeight: FontWeight.w700,
  letterSpacing: 1.3,
);

class _RouteCity extends StatelessWidget {
  const _RouteCity({
    required this.label,
    required this.city,
    this.alignEnd = false,
  });
  final String label;
  final String city;
  final bool alignEnd;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: alignEnd
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text(label, style: _labelStyle),
      const SizedBox(height: 6),
      Text(
        city,
        textAlign: alignEnd ? TextAlign.end : TextAlign.start,
        style: const TextStyle(
          color: AppColors.foreground,
          fontSize: 20,
          fontWeight: FontWeight.w800,
          letterSpacing: -.5,
          height: 1.2,
        ),
      ),
    ],
  );
}

class _RouteEmblem extends StatelessWidget {
  const _RouteEmblem();

  @override
  Widget build(BuildContext context) => ExcludeSemantics(
    child: Container(
      width: 42,
      height: 42,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF6AC48B), Color(0xFF21854B), Color(0xFF09552D)],
        ),
        border: Border.all(color: const Color(0xFF9CD5AD)),
        boxShadow: const [
          BoxShadow(color: Color(0xFF0D5D31), offset: Offset(0, 3)),
          BoxShadow(
            color: Color(0x24214B31),
            blurRadius: 8,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: const Icon(
        Icons.directions_bus_rounded,
        color: Colors.white,
        size: 23,
      ),
    ),
  );
}

class _TicketMetadata extends StatelessWidget {
  const _TicketMetadata({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 14, color: const Color(0xFF6E8576)),
      const SizedBox(width: 6),
      Flexible(
        child: Text(
          text,
          style: const TextStyle(
            color: Color(0xFF52675A),
            fontSize: 11,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
    ],
  );
}

class _PerforationPainter extends CustomPainter {
  const _PerforationPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFFCFDDD2)
      ..strokeWidth = 1;
    for (var x = 0.0; x < size.width; x += 9) {
      canvas.drawLine(
        Offset(x, size.height / 2),
        Offset((x + 4).clamp(0, size.width), size.height / 2),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(_PerforationPainter oldDelegate) => false;
}

/// Decorative stacked tickets, drawn with widgets so they stay sharp on tablets.
class TripTicketEmblem extends StatelessWidget {
  const TripTicketEmblem({super.key});

  @override
  Widget build(BuildContext context) => ExcludeSemantics(
    child: SizedBox(
      width: 64,
      height: 70,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Transform.rotate(
            angle: -.2,
            child: Container(
              width: 49,
              height: 60,
              decoration: BoxDecoration(
                color: const Color(0xFF428963),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFF70AF87)),
              ),
            ),
          ),
          Transform.rotate(
            angle: .12,
            child: Container(
              width: 49,
              height: 60,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFFFE8A4), Color(0xFFDBB85D)],
                ),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFFEDBE)),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x40002514),
                    blurRadius: 10,
                    offset: Offset(2, 6),
                  ),
                ],
              ),
              child: const Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.directions_bus_rounded,
                    color: Color(0xFF405B32),
                    size: 24,
                  ),
                  SizedBox(height: 5),
                  SizedBox(
                    width: 25,
                    child: Divider(
                      color: Color(0x99826930),
                      height: 1,
                      thickness: 1,
                    ),
                  ),
                  SizedBox(height: 5),
                  Icon(
                    Icons.more_horiz_rounded,
                    color: Color(0xFF826930),
                    size: 17,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    ),
  );
}
