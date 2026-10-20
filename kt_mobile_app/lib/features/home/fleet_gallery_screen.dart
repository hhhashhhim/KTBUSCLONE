import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/theme/app_shadows.dart';
import '../../core/widgets/app_widgets.dart';
import '../travel/application/travel_providers.dart';
import 'fleet_photos.dart';

class FleetGalleryScreen extends ConsumerStatefulWidget {
  const FleetGalleryScreen({super.key});

  @override
  ConsumerState<FleetGalleryScreen> createState() => _FleetGalleryScreenState();
}

class _FleetGalleryScreenState extends ConsumerState<FleetGalleryScreen> {
  String _filter = 'all';

  @override
  Widget build(BuildContext context) {
    final gallery = ref.watch(fleetGalleryProvider);
    final photos = ref.watch(fleetPhotosProvider);
    final filtered = _filter == 'all'
        ? photos
        : photos
              .where(
                (photo) =>
                    photo.mediaType == _filter || photo.category == _filter,
              )
              .toList();
    return Scaffold(
      appBar: AppBar(title: const Text('Bus & seat guide')),
      body: RefreshIndicator(
        onRefresh: () async {
          try {
            final reload = ref.refresh(fleetGalleryProvider.future);
            await reload;
          } catch (_) {
            // Bundled photos remain usable; the retry control reflects errors.
          }
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            const Text(
              'Explore our executive buses, sleeper cabins and waiting lounge.',
              style: TextStyle(color: AppColors.muted),
            ),
            const SizedBox(height: AppSpacing.md),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                _filterChip('all', 'All'),
                _filterChip('Executive', 'Executive'),
                _filterChip('Sleeper', 'Sleeper'),
                _filterChip('lounge', 'Waiting Lounge'),
                _filterChip('exterior', 'Buses'),
                _filterChip('interior', 'Interiors'),
                _filterChip('seats', 'Seats'),
              ],
            ),
            if (gallery.hasError)
              TextButton.icon(
                onPressed: () => ref.invalidate(fleetGalleryProvider),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry online photos'),
              ),
            const SizedBox(height: AppSpacing.md),
            if (filtered.isEmpty)
              const AppStateView(
                icon: Icons.photo_library_outlined,
                title: 'No photos in this category',
                message: 'Choose another category to explore our photos.',
              )
            else
              ...filtered.map((photo) => _MediaCard(item: photo)),
          ],
        ),
      ),
    );
  }

  Widget _filterChip(String value, String label) => ChoiceChip(
    label: Text(label),
    selected: _filter == value,
    selectedColor: AppColors.primary,
    labelStyle: TextStyle(
      color: _filter == value ? Colors.white : AppColors.foreground,
      fontWeight: FontWeight.w700,
    ),
    onSelected: (_) => setState(() => _filter = value),
  );
}

class _MediaCard extends StatelessWidget {
  const _MediaCard({required this.item});

  final FleetPhoto item;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: AppSpacing.md),
    clipBehavior: Clip.antiAlias,
    decoration: const BoxDecoration(
      color: AppColors.surface,
      borderRadius: AppRadius.card,
      boxShadow: AppShadows.card,
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AspectRatio(
          aspectRatio: 16 / 9,
          child: FleetPhotoImage(photo: item),
        ),
        Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item.title,
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              if (item.category != null) ...[
                const SizedBox(height: 3),
                Text(
                  item.category!,
                  style: const TextStyle(color: AppColors.muted),
                ),
              ],
            ],
          ),
        ),
      ],
    ),
  );
}
