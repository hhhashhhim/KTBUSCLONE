import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/auth/application/auth_controller.dart';
import 'package:kt_mobile_app/features/auth/domain/passenger.dart';
import 'package:kt_mobile_app/features/home/app_media_strip.dart';
import 'package:kt_mobile_app/features/home/home_screen.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';

const _imageUrl = 'https://example.test/banner.png';
const _passenger = PassengerAccount(
  id: 1,
  fullName: 'Test passenger',
  mobile: '03000000000',
);

List<FleetMedia> _banner(String title) => [
  FleetMedia(
    id: 1,
    title: title,
    mediaType: 'exterior',
    imageUrl: _imageUrl,
    placements: const ['home_banner'],
  ),
];

class _SignedIn extends AuthController {
  @override
  AuthState build() => const AuthState(passenger: _passenger);
}

class _MediaRepository extends Fake implements TravelRepository {
  List<FleetMedia> items = _banner('Original banner');
  int reads = 0;
  bool failTrips = false;
  bool failMedia = false;
  Completer<List<FleetMedia>>? pending;

  @override
  Future<List<FleetMedia>> fleet() async {
    reads++;
    if (failMedia) throw StateError('Media unavailable');
    return pending == null ? items : pending!.future;
  }

  @override
  Future<List<BookingSummary>> bookings() async {
    if (failTrips) throw StateError('Trips unavailable');
    return const [];
  }
}

class _ImageClient extends Fake implements HttpClient {
  late Uint8List bytes;
  final urls = <Uri>[];
  final headers = <_Headers>[];

  @override
  Future<HttpClientRequest> getUrl(Uri url) async {
    urls.add(url);
    final request = _ImageRequest(bytes);
    headers.add(request.headers);
    return request;
  }
}

class _Headers extends Fake implements HttpHeaders {
  final values = <String, Object>{};

  @override
  void add(String name, Object value, {bool preserveHeaderCase = false}) {
    values[name] = value;
  }
}

class _ImageRequest extends Fake implements HttpClientRequest {
  _ImageRequest(this.bytes);
  final Uint8List bytes;
  @override
  final _Headers headers = _Headers();

  @override
  Future<HttpClientResponse> close() async => _ImageResponse(bytes);
}

class _ImageResponse extends Fake implements HttpClientResponse {
  _ImageResponse(this.bytes);
  final Uint8List bytes;

  @override
  int get statusCode => HttpStatus.ok;
  @override
  int get contentLength => bytes.length;
  @override
  HttpClientResponseCompressionState get compressionState =>
      HttpClientResponseCompressionState.notCompressed;

  @override
  StreamSubscription<List<int>> listen(
    void Function(List<int>)? onData, {
    Function? onError,
    void Function()? onDone,
    bool? cancelOnError,
  }) => Stream<List<int>>.value(bytes).listen(
    onData,
    onError: onError,
    onDone: onDone,
    cancelOnError: cancelOnError,
  );
}

Future<Uint8List> _png(int width) async {
  final recorder = ui.PictureRecorder();
  Canvas(recorder).drawColor(Colors.green, BlendMode.src);
  final picture = recorder.endRecording();
  final image = await picture.toImage(width, 1);
  final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
  image.dispose();
  picture.dispose();
  return bytes!.buffer.asUint8List();
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() {
    debugNetworkImageHttpClientProvider = null;
    PaintingBinding.instance.imageCache.clear();
    PaintingBinding.instance.imageCache.clearLiveImages();
  });

  test(
    'signing out clears media and signing back in fetches new content',
    () async {
      final repository = _MediaRepository();
      final container = ProviderContainer(
        overrides: [
          authControllerProvider.overrideWith(_SignedIn.new),
          travelRepositoryProvider.overrideWithValue(repository),
        ],
      );
      addTearDown(container.dispose);
      container.listen(fleetGalleryProvider, (_, _) {});
      expect(
        (await container.read(fleetGalleryProvider.future)).first.title,
        'Original banner',
      );

      container.read(authControllerProvider.notifier).expireSession();
      expect(await container.read(fleetGalleryProvider.future), isEmpty);
      expect(repository.reads, 1);

      repository.items = _banner('Replacement banner');
      container
          .read(authControllerProvider.notifier)
          .replacePassenger(_passenger);
      expect(
        (await container.read(fleetGalleryProvider.future)).first.title,
        'Replacement banner',
      );
      expect(repository.reads, 2);
    },
  );

  test(
    'a media response arriving after sign-out cannot restore old content',
    () async {
      final response = Completer<List<FleetMedia>>();
      final repository = _MediaRepository()..pending = response;
      final container = ProviderContainer(
        overrides: [
          authControllerProvider.overrideWith(_SignedIn.new),
          travelRepositoryProvider.overrideWithValue(repository),
        ],
      );
      addTearDown(container.dispose);
      container.listen(fleetGalleryProvider, (_, _) {});
      container.read(authControllerProvider.notifier).expireSession();
      expect(await container.read(fleetGalleryProvider.future), isEmpty);
      response.complete(_banner('Late banner'));
      await Future<void>.delayed(Duration.zero);
      expect(container.read(fleetGalleryProvider).value, isEmpty);
    },
  );

  testWidgets(
    'Home pull refresh replaces same-URL image bytes despite a trips failure, then removes unpublished banners',
    (tester) async {
      final semantics = tester.ensureSemantics();
      await tester.binding.setSurfaceSize(const Size(430, 1600));
      addTearDown(() => tester.binding.setSurfaceSize(null));
      final client = _ImageClient();
      client.bytes = (await tester.runAsync(() => _png(1)))!;
      debugNetworkImageHttpClientProvider = () => client;
      try {
        final repository = _MediaRepository();
        await tester.pumpWidget(
          ProviderScope(
            overrides: [
              authControllerProvider.overrideWith(_SignedIn.new),
              travelRepositoryProvider.overrideWithValue(repository),
              mobileAppConfigProvider.overrideWithValue(null),
              homeClockProvider.overrideWith(
                (ref) => Stream.value(DateTime(2026)),
              ),
            ],
            child: const MaterialApp(home: HomeScreen()),
          ),
        );
        await tester.pumpAndSettle();
        final banner = find.byType(AppMediaStrip).first;
        await tester.runAsync(
          () => precacheImage(
            const NetworkImage(_imageUrl),
            tester.element(banner),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.bySemanticsLabel('Original banner'), findsOneWidget);
        expect(client.urls, [Uri.parse(_imageUrl)]);

        client.bytes = (await tester.runAsync(() => _png(2)))!;
        repository.items = _banner('Replacement banner');
        repository.failTrips = true;
        await tester.drag(find.byType(RefreshIndicator), const Offset(0, 400));
        await tester.pumpAndSettle();
        expect(repository.reads, 2);
        expect(find.bySemanticsLabel('Replacement banner'), findsOneWidget);
        expect(find.bySemanticsLabel('Original banner'), findsNothing);
        // No changed URL or manually resolved provider may trigger this download.
        expect(client.urls, [Uri.parse(_imageUrl), Uri.parse(_imageUrl)]);
        expect(client.headers.last.values['Cache-Control'], 'no-cache');
        await tester.runAsync(
          () => precacheImage(
            const NetworkImage(_imageUrl),
            tester.element(banner),
          ),
        );
        await tester.pumpAndSettle();
        final rendered = tester.widget<RawImage>(
          find.descendant(of: banner, matching: find.byType(RawImage)),
        );
        expect(rendered.image!.width, 2);

        repository.items = [];
        await tester.drag(find.byType(RefreshIndicator), const Offset(0, 400));
        await tester.pumpAndSettle();
        expect(repository.reads, 3);
        expect(find.bySemanticsLabel('Replacement banner'), findsNothing);

        repository.failMedia = true;
        await tester.drag(find.byType(RefreshIndicator), const Offset(0, 400));
        await tester.pumpAndSettle();
        expect(
          find.text('Could not refresh app images. Pull down to try again.'),
          findsOneWidget,
        );
        expect(find.byType(RefreshProgressIndicator), findsNothing);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
      } finally {
        semantics.dispose();
        debugNetworkImageHttpClientProvider = null;
      }
    },
  );
}
