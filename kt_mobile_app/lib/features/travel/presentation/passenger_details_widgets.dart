import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_colors.dart';

class PassengerDetailsHeader extends StatelessWidget {
  const PassengerDetailsHeader({super.key});

  @override
  Widget build(BuildContext context) => AnnotatedRegion<SystemUiOverlayStyle>(
    value: SystemUiOverlayStyle.light,
    child: Container(
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(30)),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF27995B), Color(0xFF0B663D), Color(0xFF03482E)],
          stops: [0, .5, 1],
        ),
        boxShadow: [
          BoxShadow(color: Color(0xFFB8CFC0), offset: Offset(0, 5)),
          BoxShadow(
            color: Color(0x22204432),
            offset: Offset(0, 12),
            blurRadius: 24,
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
        child: Stack(
          children: [
            Positioned(
              right: -42,
              top: -92,
              child: ExcludeSemantics(
                child: Container(
                  width: 220,
                  height: 220,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: const Color(0x0DFFFFFF),
                      width: 32,
                    ),
                  ),
                ),
              ),
            ),
            SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(18, 12, 18, 35),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(15),
                            gradient: const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [Color(0xFF4BA874), Color(0xFF1B7048)],
                            ),
                            border: Border.all(color: const Color(0x668BE0AD)),
                            boxShadow: const [
                              BoxShadow(
                                color: Color(0x66002D19),
                                offset: Offset(0, 3),
                                blurRadius: 4,
                              ),
                            ],
                          ),
                          child: IconButton(
                            tooltip: 'Back to seats',
                            onPressed: () => Navigator.maybePop(context),
                            icon: const Icon(
                              Icons.arrow_back_rounded,
                              size: 21,
                            ),
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(width: 13),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Passenger details',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 21,
                                  letterSpacing: -.5,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              SizedBox(height: 4),
                              Text(
                                'A little detail. A smooth journey.',
                                style: TextStyle(
                                  color: Color(0xFFD0E8D9),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 23),
                    const Row(
                      children: [
                        Expanded(
                          child: _BookingStep(label: 'Seats', complete: true),
                        ),
                        _StepConnector(complete: true),
                        Expanded(
                          child: _BookingStep(label: 'Details', active: true),
                        ),
                        _StepConnector(),
                        Expanded(child: _BookingStep(label: 'Payment')),
                      ],
                    ),
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

class _BookingStep extends StatelessWidget {
  const _BookingStep({
    required this.label,
    this.complete = false,
    this.active = false,
  });

  final String label;
  final bool complete;
  final bool active;

  @override
  Widget build(BuildContext context) => Semantics(
    label:
        '$label, ${complete
            ? 'completed'
            : active
            ? 'current step'
            : 'upcoming'}',
    excludeSemantics: true,
    child: Column(
      children: [
        Container(
          width: 28,
          height: 28,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: active
                  ? [Colors.white, const Color(0xFFD8EBDD)]
                  : complete
                  ? [const Color(0xFF70C28F), const Color(0xFF35945D)]
                  : [const Color(0xFF2F7954), const Color(0xFF185B3D)],
            ),
            border: Border.all(
              color: active ? Colors.white : const Color(0xFF6AAB83),
            ),
            boxShadow: const [
              BoxShadow(
                color: Color(0x55002F1C),
                offset: Offset(0, 3),
                blurRadius: 4,
              ),
            ],
          ),
          child: complete
              ? const Icon(Icons.check_rounded, size: 17, color: Colors.white)
              : Text(
                  active ? '2' : '3',
                  style: TextStyle(
                    color: active
                        ? AppColors.primaryDeep
                        : const Color(0xFFC4DDCE),
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                  ),
                ),
        ),
        const SizedBox(height: 7),
        Text(
          label,
          style: TextStyle(
            color: active ? Colors.white : const Color(0xFFCEE7D8),
            fontSize: 10,
            fontWeight: active ? FontWeight.w800 : FontWeight.w500,
          ),
        ),
      ],
    ),
  );
}

class _StepConnector extends StatelessWidget {
  const _StepConnector({this.complete = false});
  final bool complete;

  @override
  Widget build(BuildContext context) => Container(
    width: 35,
    height: 2,
    margin: const EdgeInsets.only(bottom: 20),
    color: complete ? const Color(0xFF8CD0A3) : const Color(0xFF4C8B65),
  );
}

class PassengerDetailsCard extends StatelessWidget {
  const PassengerDetailsCard({required this.child, super.key});
  final Widget child;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(26),
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Colors.white, Color(0xFFFAFCFA)],
      ),
      border: Border.all(color: Colors.white, width: 1.5),
      boxShadow: const [
        BoxShadow(color: Color(0xFFCFDED2), offset: Offset(0, 6)),
        BoxShadow(
          color: Color(0x1A1D4930),
          offset: Offset(0, 14),
          blurRadius: 26,
        ),
        BoxShadow(
          color: Color(0xB3FFFFFF),
          offset: Offset(-4, -4),
          blurRadius: 12,
        ),
      ],
    ),
    child: child,
  );
}

class PassengerCardHeading extends StatelessWidget {
  const PassengerCardHeading({
    required this.number,
    required this.seat,
    required this.onUseSaved,
    super.key,
  });

  final int number;
  final String seat;
  final VoidCallback onUseSaved;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Container(
        width: 48,
        height: 48,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF47AC70), Color(0xFF08703B)],
          ),
          border: Border.all(color: const Color(0xFF91D6A9)),
          boxShadow: const [
            BoxShadow(color: Color(0xFF04542F), offset: Offset(0, 3)),
            BoxShadow(
              color: Color(0x3321653D),
              offset: Offset(0, 6),
              blurRadius: 9,
            ),
          ],
        ),
        child: const Icon(Icons.person_rounded, color: Colors.white, size: 27),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Passenger $number',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                letterSpacing: -.3,
              ),
            ),
            const SizedBox(height: 5),
            Row(
              children: [
                const Icon(
                  Icons.event_seat_rounded,
                  size: 13,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 4),
                Flexible(
                  child: Text(
                    'Seat $seat',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
      const SizedBox(width: 6),
      TextButton(
        onPressed: onUseSaved,
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primaryDeep,
          backgroundColor: const Color(0xFFECF5EE),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: const BorderSide(color: Color(0xFFD7E8DC)),
          ),
        ),
        child: const Text(
          'Use saved',
          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
        ),
      ),
    ],
  );
}

class PassengerContinueAction extends StatefulWidget {
  const PassengerContinueAction({
    required this.submitting,
    required this.onPressed,
    super.key,
  });

  final bool submitting;
  final VoidCallback onPressed;

  @override
  State<PassengerContinueAction> createState() =>
      _PassengerContinueActionState();
}

class _PassengerContinueActionState extends State<PassengerContinueAction> {
  final _states = WidgetStatesController();
  bool _pressed = false;

  @override
  void initState() {
    super.initState();
    _states.addListener(_updatePress);
  }

  void _updatePress() {
    final pressed = _states.value.contains(WidgetState.pressed);
    if (pressed != _pressed) setState(() => _pressed = pressed);
  }

  @override
  void dispose() {
    _states.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 18),
      decoration: const BoxDecoration(
        color: Color(0xFFF5F9F5),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(top: BorderSide(color: Colors.white, width: 1.5)),
        boxShadow: [
          BoxShadow(
            color: Color(0x0D194B2D),
            offset: Offset(0, -6),
            blurRadius: 20,
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: AnimatedContainer(
          duration: MediaQuery.disableAnimationsOf(context)
              ? Duration.zero
              : const Duration(milliseconds: 100),
          transform: Matrix4.translationValues(0, _pressed ? 4 : 0, 0),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: widget.submitting
                  ? [const Color(0xFF769E81), const Color(0xFF557E61)]
                  : [const Color(0xFF25A350), const Color(0xFF087238)],
            ),
            border: Border.all(color: const Color(0xFF52B875)),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF044E28),
                offset: Offset(0, _pressed ? 1 : 5),
              ),
              BoxShadow(
                color: const Color(0x33327547),
                offset: Offset(0, _pressed ? 3 : 9),
                blurRadius: 13,
              ),
            ],
          ),
          child: FilledButton(
            statesController: _states,
            onPressed: widget.submitting ? null : widget.onPressed,
            style: FilledButton.styleFrom(
              backgroundColor: Colors.transparent,
              disabledBackgroundColor: Colors.transparent,
              disabledForegroundColor: Colors.white,
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
                if (widget.submitting) ...[
                  const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2,
                    ),
                  ),
                  const SizedBox(width: 10),
                ],
                Flexible(
                  child: Text(
                    widget.submitting
                        ? 'Saving passenger…'
                        : 'Continue to payment',
                  ),
                ),
                if (!widget.submitting) ...[
                  const SizedBox(width: 10),
                  const Icon(Icons.arrow_forward_rounded, size: 19),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
