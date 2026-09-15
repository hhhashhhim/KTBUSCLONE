import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_radius.dart';
import '../../core/theme/app_spacing.dart';
import '../../core/theme/app_shadows.dart';
import '../../core/widgets/app_widgets.dart';
import '../travel/application/travel_providers.dart';
import '../travel/domain/travel_models.dart';

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
    return Scaffold(
      appBar: AppBar(title: const Text('Bus & seat guide')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(fleetGalleryProvider.future),
        child: gallery.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => AppStateView(
            icon: Icons.directions_bus_outlined,
            title: 'Could not load the guide',
            message: error.toString(),
            actionLabel: 'Try again',
            onAction: () => ref.invalidate(fleetGalleryProvider),
          ),
          data: (items) {
            final filtered = _filter == 'all'
                ? items
                : items.where((item) => item.mediaType == _filter).toList();
            return ListView(
              padding: const EdgeInsets.all(AppSpacing.md),
              children: [
                const Text(
                  'See the available bus types, cabin features and seating options before you book.',
                  style: TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: AppSpacing.md),
                Wrap(
                  spacing: 8,
                  children: [
                    _filterChip('all', 'All'),
                    _filterChip('exterior', 'Buses'),
                    _filterChip('interior', 'Interiors'),
                    _filterChip('seats', 'Seats'),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                if (filtered.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 90),
                    child: AppStateView(
                      icon: Icons.photo_library_outlined,
                      title: 'Guide coming soon',
                      message:
                          'Bus and seat photos will appear here when Kainat Travels adds them.',
                    ),
                  )
                else
                  ...filtered.map((item) => _MediaCard(item: item)),
              ],
            );
          },
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

  final FleetMedia item;

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
          child: Image.network(
            item.imageUrl,
            fit: BoxFit.cover,
            errorBuilder: (_, _, _) => const ColoredBox(
              color: AppColors.primarySoft,
              child: Center(
                child: Icon(
                  Icons.directions_bus_rounded,
                  color: AppColors.primary,
                  size: 48,
                ),
              ),
            ),
          ),
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
              if (item.busClassName != null) ...[
                const SizedBox(height: 3),
                Text(
                  item.busClassName!,
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
