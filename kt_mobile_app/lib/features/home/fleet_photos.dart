import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';

/// Presentation photos, independent of server bus IDs and booking availability.
class FleetPhoto {
  const FleetPhoto({
    required this.title,
    required this.source,
    required this.mediaType,
    this.category,
    this.isAsset = true,
  });

  final String title;
  final String source;
  final String mediaType;
  final String? category;
  final bool isAsset;
}

const bundledFleetPhotos = [
  FleetPhoto(
    title: 'Executive bus',
    source: 'assets/images/executive-exterior.webp',
    mediaType: 'exterior',
    category: 'Executive',
  ),
  FleetPhoto(
    title: 'Sleeper bus',
    source: 'assets/images/sleeper-exterior.webp',
    mediaType: 'exterior',
    category: 'Sleeper',
  ),
  FleetPhoto(
    title: 'Waiting lounge',
    source: 'assets/images/lounge-1.webp',
    mediaType: 'lounge',
    category: 'Waiting Lounge',
  ),
  FleetPhoto(
    title: 'Executive bus — front view',
    source: 'assets/images/executive-front.webp',
    mediaType: 'exterior',
    category: 'Executive',
  ),
  FleetPhoto(
    title: 'Executive seating',
    source: 'assets/images/executive-seats.webp',
    mediaType: 'seats',
    category: 'Executive',
  ),
  FleetPhoto(
    title: 'Executive cabin & seatback screens',
    source: 'assets/images/executive-seatbacks.webp',
    mediaType: 'interior',
    category: 'Executive',
  ),
  FleetPhoto(
    title: 'Sleeper cabin',
    source: 'assets/images/sleeper-interior.webp',
    mediaType: 'interior',
    category: 'Sleeper',
  ),
  FleetPhoto(
    title: 'Sleeper bed',
    source: 'assets/images/sleeper-bed.webp',
    mediaType: 'seats',
    category: 'Sleeper',
  ),
  FleetPhoto(
    title: 'Waiting lounge — seating',
    source: 'assets/images/lounge-2.webp',
    mediaType: 'lounge',
    category: 'Waiting Lounge',
  ),
  FleetPhoto(
    title: 'Waiting lounge — interior',
    source: 'assets/images/lounge-3.webp',
    mediaType: 'lounge',
    category: 'Waiting Lounge',
  ),
];

// Published photos lead each chosen placement. Bundled fleet photos remain
// available in the carousel/guide while offline and after the online catalog.
List<FleetPhoto> photosForPlacement(
  List<FleetMedia>? online,
  String placement,
) => [
  if (online != null)
    ...online
        .where((item) => item.placements.contains(placement))
        .map(
          (item) => FleetPhoto(
            title: item.title,
            source: item.imageUrl,
            mediaType: item.mediaType,
            category: item.busClassName,
            isAsset: false,
          ),
        ),
  if (placement == 'home_carousel' || placement == 'bus_guide')
    ...bundledFleetPhotos,
];

final appPhotosProvider = Provider.family<List<FleetPhoto>, String>((
  ref,
  placement,
) {
  final online = ref.watch(fleetGalleryProvider).asData?.value;
  return photosForPlacement(online, placement);
});

final fleetPhotosProvider = Provider<List<FleetPhoto>>(
  (ref) => ref.watch(appPhotosProvider('bus_guide')),
);

class FleetPhotoImage extends StatelessWidget {
  const FleetPhotoImage({super.key, required this.photo, this.fit});

  final FleetPhoto photo;
  final BoxFit? fit;

  @override
  Widget build(BuildContext context) => ColoredBox(
    color: Colors.white,
    child: Image(
      // Published photos are new objects after a catalog fetch. Re-resolve
      // their image streams even when the refreshed URL has not changed.
      key: ObjectKey(photo),
      image: photo.isAsset
          ? AssetImage(photo.source)
          : NetworkImage(
                  photo.source,
                  headers: const {'Cache-Control': 'no-cache'},
                )
                as ImageProvider,
      // Keep the complete bus visible, including the tall front-facing photo.
      fit:
          fit ??
          (photo.mediaType == 'exterior' ? BoxFit.contain : BoxFit.cover),
      semanticLabel: photo.title,
      errorBuilder: (_, _, _) => const ColoredBox(
        color: AppColors.primarySoft,
        child: Center(
          child: Icon(
            Icons.image_not_supported_outlined,
            color: AppColors.primary,
            size: 42,
            semanticLabel: 'Photo unavailable',
          ),
        ),
      ),
    ),
  );
}
