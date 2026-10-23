import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/features/shell/app_shell.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';

class _SeatRepository implements TravelRepository {
  _SeatRepository(this.layout);
  final List<TravelSeat> layout;

  @override
  Future<SeatLayoutResult> seats(
    TravelSchedule schedule,
    SearchQuery query,
  ) async => SeatLayoutResult(seats: layout, maximumSelectable: 5);

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

// Three business rows, seven executive rows and a five-seat rear bench.
List<TravelSeat> _mixedLayout() {
  var number = 0;
  return [
    for (var row = 0; row < 11; row++)
      for (final column
          in row < 3
              ? [4, 3, 0]
              : row == 10
              ? [4, 3, 2, 1, 0]
              : [4, 3, 1, 0])
        TravelSeat(
          number: '${++number}',
          row: row,
          column: column,
          status: number < 20
              ? SeatAvailability.booked
              : SeatAvailability.available,
          bookedGender: number == 1
              ? 'male'
              : number == 2
              ? 'female'
              : null,
          price: 2500,
          classId: 1,
        ),
  ];
}

List<TravelSeat> _largeLayout(int rows) => [
  for (var row = 0; row < rows; row++)
    for (final column in [0, 1, 3, 4])
      TravelSeat(
        number: '${row * 4 + [0, 1, 3, 4].indexOf(column) + 1}',
        row: row,
        column: column,
        status: SeatAvailability.available,
        price: 2500,
        classId: 1,
      ),
];

Finder _seat(String number) => find.byWidgetPredicate(
  (widget) =>
      widget is Semantics &&
      (widget.properties.label?.startsWith('Seat $number, ') ?? false),
);

ScrollPosition _mapPosition(WidgetTester tester, {bool horizontal = false}) =>
    tester
        .state<ScrollableState>(
          find
              .descendant(
                of: find.byKey(
                  ValueKey(
                    horizontal
                        ? 'seat-map-horizontal-scroll'
                        : 'seat-map-vertical-scroll',
                  ),
                ),
                matching: find.byType(Scrollable),
              )
              .first,
        )
        .position;

Future<ProviderContainer> _showSeats(
  WidgetTester tester, {
  required Size size,
  double topInset = 24,
  double bottomInset = 24,
  double textScale = 1,
  List<TravelSeat>? seats,
}) async {
  tester.view.devicePixelRatio = 1;
  tester.view.physicalSize = size;
  addTearDown(tester.view.resetDevicePixelRatio);
  addTearDown(tester.view.resetPhysicalSize);
  final layout = seats ?? _mixedLayout();
  final container = ProviderContainer(
    overrides: [
      travelRepositoryProvider.overrideWithValue(_SeatRepository(layout)),
    ],
  );
  addTearDown(container.dispose);
  const origin = CityOption(id: 1, name: 'Lahore');
  const destination = CityOption(id: 2, name: 'Islamabad');
  final date = DateTime(2099, 9, 21, 21);
  container
      .read(bookingFlowProvider.notifier)
      .begin(
        SearchQuery(origin: origin, destination: destination, date: date),
        TravelSchedule(
          id: 1,
          scheduleDetailId: 1,
          origin: origin,
          destination: destination,
          departureAt: date,
          busClassName: '2*1 Business & 2*2 Executive | ${layout.length} seats',
          availableSeats: layout.where((seat) => seat.selectable).length,
          totalSeats: layout.length,
          fares: const [],
        ),
      );
  final router = GoRouter(
    initialLocation: '/search/seats',
    routes: [
      GoRoute(
        path: '/search/seats',
        builder: (_, _) => const AppShell(child: SeatSelectionScreen()),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    UncontrolledProviderScope(
      container: container,
      child: MaterialApp.router(
        theme: AppTheme.light,
        routerConfig: router,
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(context).copyWith(
            padding: EdgeInsets.only(top: topInset, bottom: bottomInset),
            viewPadding: EdgeInsets.only(top: topInset, bottom: bottomInset),
            textScaler: TextScaler.linear(textScale),
          ),
          child: child!,
        ),
      ),
    ),
  );
  await tester.pumpAndSettle();
  return container;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
    // Use the SDK's real phone font: Ahem's square glyphs wrap the legend and
    // navigation labels differently, changing the height available to the map.
    final fontDirectory = Directory(
      Platform.environment['FLUTTER_ROOT']!,
    ).uri.resolve('bin/cache/artifacts/material_fonts/');
    final loader = FontLoader('Roboto');
    for (final weight in ['Regular', 'Bold', 'Black']) {
      loader.addFont(
        File.fromUri(
          fontDirectory.resolve('Roboto-$weight.ttf'),
        ).readAsBytes().then((bytes) => ByteData.sublistView(bytes)),
      );
    }
    await loader.load();
  });

  for (final phone in [
    (size: const Size(360, 800), top: 24.0, bottom: 24.0),
    (size: const Size(375, 812), top: 44.0, bottom: 34.0),
    (size: const Size(390, 844), top: 47.0, bottom: 34.0),
    (size: const Size(412, 915), top: 24.0, bottom: 24.0),
  ]) {
    testWidgets('all 42 seats fit on ${phone.size} with system insets', (
      tester,
    ) async {
      await _showSeats(
        tester,
        size: phone.size,
        topInset: phone.top,
        bottomInset: phone.bottom,
      );
      expect(_mapPosition(tester).maxScrollExtent, closeTo(0, .01));
      expect(_mapPosition(tester, horizontal: true).maxScrollExtent, 0);
      final mapRect = tester.getRect(
        find.byKey(const ValueKey('seat-map-vertical-scroll')),
      );
      for (final seat in _mixedLayout()) {
        final rect = tester.getRect(_seat(seat.number));
        expect(rect.top, greaterThanOrEqualTo(mapRect.top));
        expect(rect.bottom, lessThanOrEqualTo(mapRect.bottom + .01));
        expect(rect.left, greaterThanOrEqualTo(mapRect.left));
        expect(rect.right, lessThanOrEqualTo(mapRect.right));
        expect(rect.height, greaterThanOrEqualTo(36));
        expect(rect.width, greaterThanOrEqualTo(44));
      }
      expect(_seat('42').hitTestable(), findsOneWidget);
      expect(
        find.widgetWithText(FilledButton, 'Continue').hitTestable(),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    });
  }

  testWidgets('48 seats fit on a tall phone', (tester) async {
    await _showSeats(
      tester,
      size: const Size(412, 915),
      seats: _largeLayout(12),
    );
    expect(_mapPosition(tester).maxScrollExtent, closeTo(0, .01));
    expect(_seat('48').hitTestable(), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  for (final scenario in [
    (name: 'short screen', size: const Size(320, 568), rows: 11, scale: 1.0),
    (name: 'landscape', size: const Size(800, 400), rows: 11, scale: 1.0),
    (name: 'oversized map', size: const Size(412, 915), rows: 20, scale: 1.0),
    (name: 'large text', size: const Size(375, 812), rows: 11, scale: 1.6),
  ]) {
    testWidgets(
      '${scenario.name} scrolls to the last row with a fixed footer',
      (tester) async {
        await _showSeats(
          tester,
          size: scenario.size,
          textScale: scenario.scale,
          seats: _largeLayout(scenario.rows),
        );
        final footer = find.widgetWithText(FilledButton, 'Continue');
        final footerRect = tester.getRect(footer);
        final position = _mapPosition(tester);
        expect(position.maxScrollExtent, greaterThan(0));
        expect(tester.getSize(_seat('1')).height, greaterThanOrEqualTo(36));
        await tester.drag(
          find.byKey(const ValueKey('seat-map-vertical-scroll')),
          Offset(0, -position.maxScrollExtent - 100),
        );
        await tester.pumpAndSettle();
        expect(_seat('${scenario.rows * 4}').hitTestable(), findsOneWidget);
        expect(tester.getRect(footer), footerRect);
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets('seat positions and selection survive a viewport resize', (
    tester,
  ) async {
    final container = await _showSeats(tester, size: const Size(412, 915));
    expect(find.text('Male'), findsOneWidget);
    expect(find.text('Female'), findsOneWidget);
    expect(find.text('(booked)'), findsNWidgets(2));
    expect(find.text('Booked'), findsNothing);
    for (final entry in {
      '1': const Color(0xFFD7E8FF),
      '2': const Color(0xFFFFD8E8),
      '3': const Color(0xFFD7E8FF),
    }.entries) {
      final cell = tester.widget<AnimatedContainer>(
        find.descendant(
          of: _seat(entry.key),
          matching: find.byType(AnimatedContainer),
        ),
      );
      expect((cell.decoration! as BoxDecoration).color, entry.value);
      await tester.tap(_seat(entry.key));
      expect(container.read(bookingFlowProvider).selectedSeats, isEmpty);
    }
    expect(
      tester.widget<Semantics>(_seat('1')).properties.label,
      'Seat 1, male booked',
    );
    expect(
      tester.widget<Semantics>(_seat('2')).properties.label,
      'Seat 2, female booked',
    );
    expect(
      tester.getCenter(_seat('3')).dx,
      lessThan(tester.getCenter(_seat('2')).dx),
    );
    expect(
      tester.getCenter(_seat('2')).dx,
      lessThan(tester.getCenter(_seat('1')).dx),
    );
    expect(
      tester.getCenter(_seat('1')).dy,
      lessThan(tester.getCenter(_seat('4')).dy),
    );
    await tester.tap(_seat('1'));
    expect(container.read(bookingFlowProvider).selectedSeats, isEmpty);
    await tester.tap(_seat('42'));
    await tester.pumpAndSettle();
    expect(
      container.read(bookingFlowProvider).selectedSeats.single.number,
      '42',
    );
    tester.view.physicalSize = const Size(360, 800);
    await tester.pumpAndSettle();
    expect(_mapPosition(tester).maxScrollExtent, closeTo(0, .01));
    expect(_seat('42').hitTestable(), findsOneWidget);
    expect(
      container.read(bookingFlowProvider).selectedSeats.single.number,
      '42',
    );
    await tester.tap(_seat('42'));
    await tester.pumpAndSettle();
    expect(container.read(bookingFlowProvider).selectedSeats, isEmpty);
    expect(tester.takeException(), isNull);
  });
}
