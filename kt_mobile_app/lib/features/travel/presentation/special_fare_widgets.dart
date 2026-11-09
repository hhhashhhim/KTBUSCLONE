import 'package:flutter/material.dart';

/// An outline keeps the usual availability and selection fills recognizable.
const specialFareColor = Color(0xFF087F5B);

class SpecialSeatCount extends StatelessWidget {
  const SpecialSeatCount({required this.count, super.key});

  final int count;

  @override
  Widget build(BuildContext context) {
    if (count <= 0) return const SizedBox.shrink();
    final label = '$count special discount ${count == 1 ? 'seat' : 'seats'}';
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        const ExcludeSemantics(
          child: Icon(Icons.percent_rounded, size: 13, color: specialFareColor),
        ),
        const SizedBox(width: 5),
        Flexible(
          child: Text(
            label,
            semanticsLabel: '$label available',
            style: const TextStyle(
              color: specialFareColor,
              fontSize: 10,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    );
  }
}

class SpecialFareMark extends StatelessWidget {
  const SpecialFareMark({super.key, this.selected = false});

  final bool selected;

  @override
  Widget build(BuildContext context) => ExcludeSemantics(
    child: Icon(
      Icons.percent_rounded,
      size: 11,
      color: selected ? Colors.white : specialFareColor,
    ),
  );
}

class SpecialFareLegend extends StatelessWidget {
  const SpecialFareLegend({super.key});

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        ExcludeSemantics(
          child: Container(
            width: 19,
            height: 19,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              border: Border.all(color: specialFareColor, width: 2),
              borderRadius: BorderRadius.circular(5),
            ),
            child: const SpecialFareMark(),
          ),
        ),
        const SizedBox(width: 6),
        const Flexible(
          child: Text(
            'Special fare',
            style: TextStyle(
              color: specialFareColor,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    ),
  );
}
