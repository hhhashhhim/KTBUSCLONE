import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../application/travel_providers.dart';

const checkoutBackground = Color(0xFFEFF5F0);

class CheckoutHeader extends StatelessWidget implements PreferredSizeWidget {
  const CheckoutHeader({super.key});

  @override
  Size get preferredSize => const Size.fromHeight(92);

  @override
  Widget build(BuildContext context) => AppBar(
    toolbarHeight: preferredSize.height,
    automaticallyImplyLeading: false,
    systemOverlayStyle: SystemUiOverlayStyle.light,
    backgroundColor: const Color(0xFF075C35),
    foregroundColor: Colors.white,
    flexibleSpace: ClipRect(
      child: Stack(
        fit: StackFit.expand,
        children: [
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF16854B), Color(0xFF064B2D)],
              ),
            ),
          ),
          Positioned(
            right: -36,
            top: -75,
            child: Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white.withValues(alpha: .08),
                  width: 26,
                ),
              ),
            ),
          ),
        ],
      ),
    ),
    titleSpacing: 18,
    title: Row(
      children: [
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFF338B5B),
            borderRadius: BorderRadius.circular(15),
            border: Border.all(color: const Color(0xFF66A780)),
            boxShadow: const [
              BoxShadow(color: Color(0xFF064A2A), offset: Offset(0, 3)),
            ],
          ),
          child: IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.maybePop(context),
            icon: const Icon(Icons.arrow_back_rounded, size: 21),
          ),
        ),
        const SizedBox(width: 14),
        const Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Checkout',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 23,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -.6,
                ),
              ),
              SizedBox(height: 4),
              Text(
                'Review your journey',
                style: TextStyle(
                  color: Color(0xFFCAE3D2),
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        const ExcludeSemantics(
          child: Icon(
            Icons.receipt_long_rounded,
            color: Color(0xFFB9D9C6),
            size: 28,
          ),
        ),
      ],
    ),
  );
}

/// Lit paper, a visible lower edge, and a perforated tear line give the
/// booking depth without distorting the text or its touch targets.
class CheckoutBookingSummary extends StatelessWidget {
  const CheckoutBookingSummary({
    required this.flow,
    required this.totalLabel,
    super.key,
  });

  final BookingFlowState flow;
  final String totalLabel;

  @override
  Widget build(BuildContext context) {
    final schedule = flow.schedule!;
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1C254F36),
            blurRadius: 24,
            offset: Offset(0, 12),
          ),
          BoxShadow(color: Color(0xFFCEDDD2), offset: Offset(0, 5)),
        ],
      ),
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Colors.white, Color(0xFFF9FCFA)],
          ),
          border: Border.all(color: Colors.white, width: 1.5),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 18, 20, 18),
              child: Row(
                children: [
                  Icon(
                    Icons.confirmation_number_outlined,
                    color: AppColors.primaryDark,
                    size: 19,
                  ),
                  SizedBox(width: 9),
                  Expanded(
                    child: Text(
                      'Booking summary',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: LayoutBuilder(
                builder: (context, constraints) {
                  final stacked =
                      MediaQuery.textScalerOf(context).scale(19) > 26 ||
                      constraints.maxWidth < 240;
                  if (stacked) {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _City(
                          label: 'Departure city',
                          name: schedule.origin.name,
                        ),
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Icon(
                            Icons.south_rounded,
                            color: AppColors.primaryDark,
                            size: 20,
                          ),
                        ),
                        _City(
                          label: 'Arrival city',
                          name: schedule.destination.name,
                        ),
                      ],
                    );
                  }
                  return Row(
                    children: [
                      Expanded(
                        child: _City(
                          label: 'Departure city',
                          name: schedule.origin.name,
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 10),
                        child: _JourneyEmblem(),
                      ),
                      Expanded(
                        child: _City(
                          label: 'Arrival city',
                          name: schedule.destination.name,
                          alignEnd: true,
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: Divider(height: 32, color: Color(0xFFE4ECE6)),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                children: [
                  _Detail(
                    icon: Icons.calendar_today_outlined,
                    label: 'Date & time',
                    value: DateFormat(
                      'EEE, d MMM yyyy · h:mm a',
                    ).format(schedule.departureAt),
                  ),
                  const SizedBox(height: 13),
                  _Detail(
                    icon: Icons.event_seat_outlined,
                    label: flow.selectedSeats.length == 1
                        ? 'Seat number'
                        : 'Seat numbers',
                    value: flow.selectedSeats
                        .map((seat) => seat.number)
                        .join(', '),
                  ),
                  const SizedBox(height: 13),
                  _Detail(
                    icon: Icons.directions_bus_outlined,
                    label: 'Bus type',
                    value: schedule.busClassName,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            SizedBox(
              height: 20,
              child: Stack(
                children: [
                  const Positioned.fill(
                    left: 20,
                    right: 20,
                    child: CustomPaint(painter: _Perforation()),
                  ),
                  for (final left in [true, false])
                    Positioned(
                      left: left ? -10 : null,
                      right: left ? null : -10,
                      child: Container(
                        width: 20,
                        height: 20,
                        decoration: const BoxDecoration(
                          color: checkoutBackground,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 9, 20, 20),
              child: Row(
                children: [
                  const Expanded(
                    flex: 2,
                    child: Text(
                      'Total amount',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 3,
                    child: Text(
                      totalLabel,
                      textAlign: TextAlign.end,
                      style: const TextStyle(
                        color: AppColors.primaryDark,
                        fontSize: 25,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -.7,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _City extends StatelessWidget {
  const _City({required this.label, required this.name, this.alignEnd = false});
  final String label;
  final String name;
  final bool alignEnd;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: alignEnd
        ? CrossAxisAlignment.end
        : CrossAxisAlignment.start,
    children: [
      Text(
        label,
        textAlign: alignEnd ? TextAlign.end : TextAlign.start,
        style: const TextStyle(
          color: AppColors.muted,
          fontSize: 10,
          fontWeight: FontWeight.w500,
        ),
      ),
      const SizedBox(height: 5),
      Text(
        name,
        textAlign: alignEnd ? TextAlign.end : TextAlign.start,
        style: const TextStyle(
          fontSize: 19,
          fontWeight: FontWeight.w800,
          letterSpacing: -.5,
          height: 1.2,
        ),
      ),
    ],
  );
}

class _JourneyEmblem extends StatelessWidget {
  const _JourneyEmblem();

  @override
  Widget build(BuildContext context) => Container(
    width: 42,
    height: 42,
    decoration: BoxDecoration(
      shape: BoxShape.circle,
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Colors.white, Color(0xFFDAEADD)],
      ),
      border: Border.all(color: Colors.white, width: 2),
      boxShadow: const [
        BoxShadow(
          color: Color(0x1F244F33),
          blurRadius: 8,
          offset: Offset(2, 4),
        ),
      ],
    ),
    child: const Icon(
      Icons.east_rounded,
      size: 20,
      color: AppColors.primaryDark,
    ),
  );
}

class _Detail extends StatelessWidget {
  const _Detail({required this.icon, required this.label, required this.value});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Padding(
        padding: const EdgeInsets.only(top: 1),
        child: Icon(icon, size: 16, color: Color(0xFF7C9686)),
      ),
      const SizedBox(width: 8),
      Expanded(
        flex: 2,
        child: Text(
          label,
          style: const TextStyle(
            color: AppColors.muted,
            fontSize: 11,
            height: 1.5,
          ),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(
        flex: 3,
        child: Text(
          value,
          textAlign: TextAlign.end,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            height: 1.5,
          ),
        ),
      ),
    ],
  );
}

class _Perforation extends CustomPainter {
  const _Perforation();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFFCEDDD2)
      ..strokeWidth = 1;
    for (double x = 0; x < size.width; x += 8) {
      canvas.drawLine(
        Offset(x, size.height / 2),
        Offset((x + 4).clamp(0, size.width), size.height / 2),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _Perforation oldDelegate) => false;
}

class CheckoutAction extends StatelessWidget {
  const CheckoutAction({
    required this.label,
    required this.onPressed,
    this.submitting = false,
    super.key,
  });
  final String label;
  final VoidCallback? onPressed;
  final bool submitting;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
    decoration: const BoxDecoration(
      color: checkoutBackground,
      border: Border(top: BorderSide(color: Colors.white)),
    ),
    child: SafeArea(
      top: false,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18),
          gradient: onPressed == null
              ? null
              : const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF23A14B), Color(0xFF087936)],
                ),
          color: onPressed == null ? const Color(0xFFDCE6DF) : null,
          border: Border.all(
            color: onPressed == null
                ? const Color(0xFFDCE6DF)
                : const Color(0xFF59B976),
          ),
          boxShadow: onPressed == null
              ? null
              : const [
                  BoxShadow(color: Color(0xFF055B29), offset: Offset(0, 4)),
                  BoxShadow(
                    color: Color(0x2921683D),
                    blurRadius: 16,
                    offset: Offset(0, 8),
                  ),
                ],
        ),
        child: FilledButton(
          onPressed: onPressed,
          style: FilledButton.styleFrom(
            backgroundColor: Colors.transparent,
            disabledBackgroundColor: Colors.transparent,
            foregroundColor: Colors.white,
            disabledForegroundColor: AppColors.muted,
            shadowColor: Colors.transparent,
            minimumSize: const Size.fromHeight(54),
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 15),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(18),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (submitting) ...[
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
                const SizedBox(width: 10),
              ],
              Flexible(
                child: Text(
                  label,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              if (onPressed != null) ...[
                const SizedBox(width: 12),
                const Icon(Icons.arrow_forward_rounded, size: 19),
              ],
            ],
          ),
        ),
      ),
    ),
  );
}
