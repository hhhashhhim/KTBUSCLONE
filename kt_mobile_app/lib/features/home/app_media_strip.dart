import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_radius.dart';
import 'fleet_photos.dart';

/// Informational images published by ERP staff. Images never imply a fare or discount.
class AppMediaStrip extends ConsumerWidget {
  const AppMediaStrip({super.key, required this.placement, this.heading});

  final String placement;
  final String? heading;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final photos = ref.watch(appPhotosProvider(placement));
    if (photos.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (heading != null) ...[
          Text(
            heading!,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
        ],
        LayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.maxWidth;
            final cardWidth = photos.length > 1 ? width * .92 : width;
            return SizedBox(
              height: cardWidth * 7 / 16 + 54,
              child: ListView.separated(
                key: ValueKey('app-media-$placement'),
                scrollDirection: Axis.horizontal,
                itemCount: photos.length,
                separatorBuilder: (_, _) => const SizedBox(width: 10),
                itemBuilder: (context, index) => Container(
                  width: cardWidth,
                  clipBehavior: Clip.antiAlias,
                  decoration: const BoxDecoration(
                    color: AppColors.surfaceAlt,
                    borderRadius: AppRadius.card,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: SizedBox(
                          width: double.infinity,
                          child: FleetPhotoImage(
                            photo: photos[index],
                            fit: BoxFit.contain,
                          ),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(
                          photos[index].title,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
        if (photos.length > 1)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(
              'Swipe to see all ${photos.length} images',
              style: const TextStyle(fontSize: 11, color: AppColors.muted),
            ),
          ),
        const SizedBox(height: 16),
      ],
    );
  }
}
