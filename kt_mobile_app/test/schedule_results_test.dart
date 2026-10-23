import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/core/theme/app_theme.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';
import 'package:kt_mobile_app/features/travel/presentation/schedule_result_card.dart';

const _origin = CityOption(id: 1, name: 'Karachi');
const _destination = CityOption(id: 2, name: 'Rawalpindi');

TravelSchedule _schedule({
  int id = 1,
  int hour = 11,
  double amount = 12950,
  double original = 13950,
  bool noFare = false,
  Duration? journey,
}) => TravelSchedule(
  id: id,
  scheduleDetailId: id,
  origin: _origin,
  destination: _destination,
  departureAt: DateTime(2099, 10, 30, hour),
  arrivalAt: journey == null ? null : DateTime(2099, 10, 30, hour).add(journey),
  busClassName: '2*1 Business & 2*2 Executive | 42 seats',
  availableSeats: 25,
  totalSeats: 42,
  fares: noFare
      ? []
      : [
          const FareOption(
            classId: 2,
            className: 'Business',
            amount: 15000,
            originalAmount: 16000,
          ),
          FareOption(
            classId: 1,
            className: 'Executive',
            amount: amount,
            originalAmount: original,
          ),
        ],
);

Future<void> _showCard(
  WidgetTester tester,
  TravelSchedule schedule, {
  double width = 390,
  double scale = 1,
}) async {
  tester.view.devicePixelRatio = 1;
  tester.view.physicalSize = Size(width, 1000);
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light,
      builder: (context, child) => MediaQuery(
        data: MediaQuery.of(
          context,
        ).copyWith(textScaler: TextScaler.linear(scale)),
        child: child!,
      ),
      home: Scaffold(
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: ScheduleResultCard(schedule: schedule, onTap: () {}),
        ),
      ),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() async {
    final fonts = Directory(
      Platform.environment['FLUTTER_ROOT']!,
    ).uri.resolve('bin/cache/artifacts/material_fonts/');
    final loader = FontLoader('Roboto');
    for (final weight in ['Regular', 'Bold', 'Black']) {
      loader.addFont(
        File.fromUri(
          fonts.resolve('Roboto-$weight.ttf'),
        ).readAsBytes().then((bytes) => ByteData.sublistView(bytes)),
      );
    }
    await loader.load();
  });

  testWidgets(
    'discount belongs to the displayed fare class, with amenity labels',
    (tester) async {
      await _showCard(tester, _schedule());
      expect(find.text('PKR 12,950'), findsOneWidget);
      expect(
        tester.widget<Text>(find.text('PKR 13,950')).style?.decoration,
        TextDecoration.lineThrough,
      );
      expect(find.text('PKR 16,000'), findsNothing);
      expect(find.text('per passenger'), findsOneWidget);
      for (final label in [
        'Wi-Fi',
        'Charging',
        'Meals',
        'Reclining seats',
        'Entertainment',
        'Luggage',
      ]) {
        expect(find.byTooltip(label), findsOneWidget);
      }
      expect(tester.takeException(), isNull);
    },
  );

  for (final original in [12950.0, 12000.0]) {
    testWidgets('equal or surcharged fare $original has no false discount', (
      tester,
    ) async {
      await _showCard(tester, _schedule(original: original));
      expect(find.text('PKR 12,950'), findsOneWidget);
      expect(
        find.byWidgetPredicate(
          (widget) =>
              widget is Text &&
              widget.style?.decoration == TextDecoration.lineThrough,
        ),
        findsNothing,
      );
    });
  }

  testWidgets('estimated arrival and rounded duration show overnight travel', (
    tester,
  ) async {
    await _showCard(
      tester,
      _schedule(hour: 18, journey: const Duration(hours: 18, minutes: 20)),
    );
    expect(find.text('6:00 PM', findRichText: true), findsOneWidget);
    expect(find.text('12:20 PM', findRichText: true), findsOneWidget);
    expect(find.text('Estimated · +1 day'), findsOneWidget);
    expect(find.text('18 hr 30 min'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('missing arrival does not invent an estimate or duration', (
    tester,
  ) async {
    await _showCard(tester, _schedule());
    expect(find.text('—', findRichText: true), findsOneWidget);
    expect(find.textContaining('Estimated'), findsNothing);
    expect(find.textContaining(' hr'), findsNothing);
  });

  testWidgets('missing fare does not offer a free booking', (tester) async {
    await _showCard(tester, _schedule(noFare: true));
    expect(find.text('Fare unavailable'), findsOneWidget);
    expect(
      tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
      isNull,
    );
  });

  for (final size in [(width: 360.0, scale: 1.0), (width: 320.0, scale: 1.8)]) {
    testWidgets(
      'fare and action fit at width ${size.width}, scale ${size.scale}',
      (tester) async {
        await _showCard(
          tester,
          _schedule(
            amount: 12950.75,
            journey: const Duration(hours: 18, minutes: 20),
          ),
          width: size.width,
          scale: size.scale,
        );
        expect(find.text('PKR 12,950.75'), findsOneWidget);
        await tester.ensureVisible(find.text('Select seats'));
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets('sorting and selection preserve the selected schedule', (
    tester,
  ) async {
    final query = SearchQuery(
      origin: _origin,
      destination: _destination,
      date: DateTime(2099, 10, 30),
    );
    final early = _schedule();
    final cheap = _schedule(id: 2, hour: 14, amount: 8500, original: 8500);
    final container = ProviderContainer(
      overrides: [
        schedulesProvider(query).overrideWith((ref) async => [early, cheap]),
      ],
    );
    addTearDown(container.dispose);
    final router = GoRouter(
      initialLocation: '/search/results',
      routes: [
        GoRoute(
          path: '/search/results',
          builder: (_, _) => ScheduleResultsScreen(query: query),
        ),
        GoRoute(
          path: '/search/seats',
          builder: (_, _) => const Scaffold(body: Text('Seat selection')),
        ),
      ],
    );
    addTearDown(router.dispose);
    await tester.pumpWidget(
      UncontrolledProviderScope(
        container: container,
        child: MaterialApp.router(theme: AppTheme.light, routerConfig: router),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      tester
          .widgetList<ScheduleResultCard>(find.byType(ScheduleResultCard))
          .first
          .schedule,
      same(early),
    );
    await tester.tap(find.text('Cheapest'));
    await tester.pumpAndSettle();
    expect(
      tester
          .widgetList<ScheduleResultCard>(find.byType(ScheduleResultCard))
          .first
          .schedule,
      same(cheap),
    );
    await tester.tap(find.text('Select seats').first);
    await tester.pumpAndSettle();
    expect(container.read(bookingFlowProvider).schedule, same(cheap));
    expect(find.text('Seat selection'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
