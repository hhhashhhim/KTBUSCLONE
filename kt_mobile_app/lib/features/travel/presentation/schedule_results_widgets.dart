import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../domain/travel_models.dart';

class ScheduleResultsHeader extends StatelessWidget {
  const ScheduleResultsHeader({required this.query, super.key});

  final SearchQuery query;

  @override
  Widget build(BuildContext context) => Container(
    decoration: const BoxDecoration(
      borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
      gradient: LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF258F55), Color(0xFF0B6239), Color(0xFF03432B)],
        stops: [0, .5, 1],
      ),
      boxShadow: [
        BoxShadow(color: Color(0xFFB4CEC0), offset: Offset(0, 4)),
        BoxShadow(
          color: Color(0x24214E35),
          offset: Offset(0, 10),
          blurRadius: 22,
        ),
      ],
    ),
    child: ClipRRect(
      borderRadius: const BorderRadius.vertical(bottom: Radius.circular(28)),
      child: Stack(
        children: [
          Positioned(
            right: -48,
            top: -104,
            child: ExcludeSemantics(
              child: Container(
                width: 240,
                height: 240,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0x0DFFFFFF), width: 35),
                ),
              ),
            ),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(18, 12, 18, 21),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(15),
                          gradient: const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [Color(0xFF439D68), Color(0xFF1B6C46)],
                          ),
                          border: Border.all(color: const Color(0x667ED49B)),
                          boxShadow: const [
                            BoxShadow(
                              color: Color(0x55002D19),
                              offset: Offset(0, 3),
                              blurRadius: 5,
                            ),
                          ],
                        ),
                        child: IconButton(
                          tooltip: 'Back to search',
                          onPressed: () => Navigator.maybePop(context),
                          color: Colors.white,
                          icon: const Icon(Icons.arrow_back_rounded, size: 21),
                        ),
                      ),
                      const SizedBox(width: 13),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'YOUR JOURNEY',
                              style: TextStyle(
                                color: Color(0xFFBCE3C9),
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                letterSpacing: 1.6,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${query.origin.name} → ${query.destination.name}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 19,
                                height: 1.25,
                                letterSpacing: -.35,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 17),
                  Wrap(
                    spacing: 9,
                    runSpacing: 8,
                    children: [
                      _JourneyDetail(
                        icon: Icons.calendar_month_rounded,
                        label: DateFormat('EEE, d MMM yyyy').format(query.date),
                      ),
                      const _JourneyDetail(
                        icon: Icons.person_outline_rounded,
                        label: '1 passenger',
                      ),
                    ],
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

class _JourneyDetail extends StatelessWidget {
  const _JourneyDetail({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
    decoration: BoxDecoration(
      color: const Color(0x40033320),
      borderRadius: BorderRadius.circular(10),
      border: Border.all(color: const Color(0x304CA778)),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: const Color(0xFFBDE5CD), size: 14),
        const SizedBox(width: 6),
        Flexible(
          child: Text(
            label,
            style: const TextStyle(
              color: Color(0xFFECF7EF),
              fontSize: 11,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    ),
  );
}

class ScheduleSortBar extends StatelessWidget {
  const ScheduleSortBar({
    required this.sortByPrice,
    required this.onChanged,
    super.key,
  });

  final bool sortByPrice;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) => SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    padding: const EdgeInsets.fromLTRB(18, 19, 18, 8),
    child: Row(
      children: [
        const Icon(Icons.tune_rounded, size: 16, color: AppColors.muted),
        const SizedBox(width: 6),
        const Text(
          'Sort by',
          style: TextStyle(color: AppColors.muted, fontSize: 11),
        ),
        const SizedBox(width: 14),
        _SortChoice(
          label: 'Earliest',
          icon: Icons.schedule_rounded,
          selected: !sortByPrice,
          onTap: () => onChanged(false),
        ),
        const SizedBox(width: 10),
        _SortChoice(
          label: 'Cheapest',
          icon: Icons.payments_outlined,
          selected: sortByPrice,
          onTap: () => onChanged(true),
        ),
      ],
    ),
  );
}

class _SortChoice extends StatelessWidget {
  const _SortChoice({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Semantics(
    button: true,
    selected: selected,
    child: AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: selected ? const Color(0xFF07562E) : const Color(0xFFD3E0D6),
            offset: const Offset(0, 3),
          ),
          if (selected)
            const BoxShadow(
              color: Color(0x29296B3F),
              blurRadius: 9,
              offset: Offset(0, 5),
            ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        clipBehavior: Clip.antiAlias,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: selected
                  ? const [Color(0xFF28A454), Color(0xFF0B7B36)]
                  : const [Colors.white, Color(0xFFEDF4EE)],
            ),
            border: Border.all(
              color: selected ? const Color(0xFF65C583) : Colors.white,
            ),
          ),
          child: InkWell(
            onTap: onTap,
            child: ConstrainedBox(
              constraints: const BoxConstraints(minHeight: 44),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 13,
                  vertical: 10,
                ),
                child: Row(
                  children: [
                    Icon(
                      icon,
                      size: 16,
                      color: selected ? Colors.white : AppColors.primaryDeep,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      label,
                      style: TextStyle(
                        color: selected ? Colors.white : AppColors.foreground,
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
}
