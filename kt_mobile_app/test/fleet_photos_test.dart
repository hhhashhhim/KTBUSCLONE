import 'dart:async';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/home/fleet_gallery_screen.dart';
import 'package:kt_mobile_app/features/home/fleet_photos.dart';
import 'package:kt_mobile_app/features/home/app_media_strip.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test(
    'every bundled photo loads and decodes from the Flutter asset bundle',
    () async {
      for (final photo in bundledFleetPhotos) {
        final bytes = await rootBundle.load(photo.source);
        final codec = await ui.instantiateImageCodec(
          bytes.buffer.asUint8List(bytes.offsetInBytes, bytes.lengthInBytes),
        );
        final frame = await codec.getNextFrame();
        expect(frame.image.width, greaterThan(0));
        expect(frame.image.height, greaterThan(0));
        frame.image.dispose();
        codec.dispose();
      }
    },
  );

  test(
    'bundled photos remain visible while published photos lead their placement',
    () async {
      final response = Completer<List<FleetMedia>>();
      final container = ProviderContainer(
        overrides: [
          fleetGalleryProvider.overrideWith((ref) => response.future),
        ],
      );
      addTearDown(container.dispose);
      final subscription = container.listen(fleetPhotosProvider, (_, _) {});
      addTearDown(subscription.close);
      expect(container.read(fleetPhotosProvider), hasLength(10));
      response.complete(const [
        FleetMedia(
          id: 42,
          title: 'Published bus photo',
          mediaType: 'exterior',
          imageUrl: 'https://example.com/bus.jpg',
        ),
      ]);
      await container.read(fleetGalleryProvider.future);
      final photos = container.read(fleetPhotosProvider);
      expect(photos, hasLength(11));
      expect(photos.first.isAsset, isFalse);
      expect(photos.first.title, 'Published bus photo');
    },
  );

  testWidgets(
    'offline gallery remains browsable by executive, sleeper and lounge',
    (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          retry: (_, _) => null,
          overrides: [
            fleetGalleryProvider.overrideWith(
              (ref) async => throw Exception('Offline'),
            ),
          ],
          child: const MaterialApp(home: FleetGalleryScreen()),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Retry online photos'), findsOneWidget);
      expect(find.text('Executive bus'), findsOneWidget);

      for (final entry in {
        'Executive': 'Executive bus',
        'Sleeper': 'Sleeper bus',
        'Waiting Lounge': 'Waiting lounge',
      }.entries) {
        await tester.tap(find.widgetWithText(ChoiceChip, entry.key));
        await tester.pumpAndSettle();
        expect(find.text(entry.value), findsOneWidget);
        final visiblePhotos = tester.widgetList<FleetPhotoImage>(
          find.byType(FleetPhotoImage),
        );
        expect(visiblePhotos, isNotEmpty);
        expect(
          visiblePhotos.every((widget) => widget.photo.category == entry.key),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      }
    },
  );

  test(
    'placement parsing and filtering preserve legacy photos without leaking banners into guides',
    () {
      final legacy = FleetMedia.fromJson({
        'id': 1,
        'title': 'Legacy',
        'image_url': 'https://example.test/legacy.jpg',
      });
      final banner = FleetMedia.fromJson({
        'id': 2,
        'title': 'Banner',
        'placements': ['home_banner'],
        'image_url': 'https://example.test/banner.jpg',
      });
      final promotion = FleetMedia.fromJson({
        'id': 3,
        'title': 'Promotion',
        'placements': ['home_promotion'],
        'image_url': 'https://example.test/promo.jpg',
      });
      final guide = FleetMedia.fromJson({
        'id': 4,
        'title': 'Guide',
        'placements': ['bus_guide'],
        'image_url': 'https://example.test/guide.jpg',
      });
      final rows = [banner, promotion, guide, legacy];
      expect(photosForPlacement(rows, 'home_banner').map((p) => p.title), [
        'Banner',
      ]);
      expect(photosForPlacement(rows, 'home_promotion').map((p) => p.title), [
        'Promotion',
      ]);
      expect(
        photosForPlacement(
          rows,
          'bus_guide',
        ).where((p) => !p.isAsset).map((p) => p.title),
        ['Guide', 'Legacy'],
      );
      expect(
        photosForPlacement(
          rows,
          'home_carousel',
        ).where((p) => !p.isAsset).map((p) => p.title),
        ['Legacy'],
      );
      expect(photosForPlacement(null, 'home_banner'), isEmpty);
      expect(photosForPlacement(null, 'home_promotion'), isEmpty);
      expect(photosForPlacement(null, 'bus_guide'), hasLength(10));
      expect(
        FleetMedia.fromJson({
          'id': 5,
          'placements': ['future_slot'],
        }).placements,
        isEmpty,
      );
    },
  );

  testWidgets(
    'home media strip shows only its published placement and tolerates failed image downloads',
    (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            fleetGalleryProvider.overrideWith(
              (ref) async => const [
                FleetMedia(
                  id: 1,
                  title: 'Selected home banner',
                  mediaType: 'exterior',
                  imageUrl: 'https://example.test/banner.jpg',
                  placements: ['home_banner'],
                ),
                FleetMedia(
                  id: 2,
                  title: 'Other promotion',
                  mediaType: 'exterior',
                  imageUrl: 'https://example.test/promotion.jpg',
                  placements: ['home_promotion'],
                ),
              ],
            ),
          ],
          child: const MaterialApp(
            home: Scaffold(body: AppMediaStrip(placement: 'home_banner')),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Selected home banner'), findsOneWidget);
      expect(find.text('Other promotion'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
}
