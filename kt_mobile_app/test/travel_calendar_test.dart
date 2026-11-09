import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:kt_mobile_app/features/startup/application/startup_controller.dart';
import 'package:kt_mobile_app/features/startup/data/app_config_repository.dart';
import 'package:kt_mobile_app/features/startup/domain/app_config.dart';
import 'package:kt_mobile_app/features/travel/application/travel_calendar.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart'
    as lovable;
import 'package:kt_mobile_app/features/travel/presentation/search_flow.dart'
    as classic;

class _Config extends Fake implements AppConfigRepository {
  String? date = '2040-05-04';
  bool fail = false;
  Completer<void>? pending;
  int reads = 0;
  @override
  Future<MobileAppConfig> fetch() async {
    reads++;
    await pending?.future;
    if (fail) throw StateError('Offline');
    return MobileAppConfig.fromJson({'server_date': date});
  }
}

class _Travel extends Fake implements TravelRepository {
  @override
  Future<List<CityOption>> cities() async => [
    const CityOption(id: 1, name: 'Rawalpindi'),
  ];
  @override
  Future<List<CityOption>> destinations(int originId) async => [
    const CityOption(id: 2, name: 'Faisalabad'),
  ];
}

void main() {
  test(
    'strict server dates never fall back to device date or normalize invalid days',
    () {
      for (final date in [
        null,
        '',
        '2026-02-30',
        '2026-13-01',
        '2026-00-01',
        '0000-01-01',
        '2026-11-07T00:00:00Z',
      ]) {
        expect(
          () => TravelCalendar.fromServerDate(date),
          throwsFormatException,
        );
      }
      expect(
        TravelCalendar.fromServerDate('2028-02-29').today,
        DateTime(2028, 2, 29),
      );
    },
  );

  test(
    'midnight rolls old selections forward and retains future selections',
    () {
      final calendar = TravelCalendar.fromServerDate('2040-05-05');
      expect(calendar.selection(null), DateTime(2040, 5, 5));
      expect(calendar.selection(DateTime(2040, 5, 4)), DateTime(2040, 5, 5));
      expect(calendar.selection(DateTime(2040, 5, 20)), DateTime(2040, 5, 20));
      expect(calendar.selection(DateTime(2050)), calendar.lastDate);
    },
  );

  testWidgets(
    'search refreshes the server day after midnight before submitting',
    (tester) async {
      final config = _Config();
      SearchQuery? submitted;
      final router = GoRouter(
        routes: [
          GoRoute(path: '/', builder: (_, _) => const lovable.SearchScreen()),
          GoRoute(
            path: '/search/results',
            builder: (_, state) {
              submitted = state.extra! as SearchQuery;
              return const Scaffold(body: Text('Results'));
            },
          ),
        ],
      );
      addTearDown(router.dispose);
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            appConfigRepositoryProvider.overrideWithValue(config),
            travelRepositoryProvider.overrideWithValue(_Travel()),
          ],
          child: MaterialApp.router(routerConfig: router),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Choose city').first);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Rawalpindi').last);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Choose city'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Faisalabad').last);
      await tester.pumpAndSettle();
      config.fail = true;
      await tester.ensureVisible(find.text('Search buses').last);
      await tester.tap(find.text('Search buses').last);
      await tester.pumpAndSettle();
      expect(submitted, isNull);
      config.fail = false;
      config.date = '2040-05-05';
      await tester.tap(find.text('Search buses').last);
      await tester.pumpAndSettle();
      expect(submitted?.date, DateTime(2040, 5, 5));
      expect(find.text('Results'), findsOneWidget);
    },
  );

  for (final screen in {
    'Lovable': const lovable.SearchScreen(),
    'Classic': const classic.SearchScreen(),
  }.entries) {
    testWidgets(
      '${screen.key} calendar uses fresh server date for all picker dates',
      (tester) async {
        final config = _Config();
        final container = ProviderContainer(
          overrides: [
            appConfigRepositoryProvider.overrideWithValue(config),
            travelRepositoryProvider.overrideWithValue(_Travel()),
          ],
        );
        addTearDown(container.dispose);
        await tester.pumpWidget(
          UncontrolledProviderScope(
            container: container,
            child: MaterialApp(home: screen.value),
          ),
        );
        await tester.pumpAndSettle();
        expect(config.reads, 1);
        config.date =
            '2001-02-03'; // Deliberately years behind the phone's date.
        await tester.ensureVisible(find.byIcon(Icons.calendar_month_rounded));
        await tester.tap(find.byIcon(Icons.calendar_month_rounded));
        await tester.pumpAndSettle();
        final dialog = tester.widget<DatePickerDialog>(
          find.byType(DatePickerDialog),
        );
        expect(dialog.initialDate, DateTime(2001, 2, 3));
        expect(dialog.currentDate, DateTime(2001, 2, 3));
        expect(dialog.firstDate, DateTime(2001, 2, 3));
        expect(dialog.lastDate, DateTime(2001, 5, 4));
        expect(config.reads, 2);
        await tester.tap(find.text('5').last);
        await tester.tap(find.text('OK'));
        await tester.pumpAndSettle();
        config.date = '2001-02-04';
        container.invalidate(travelCalendarProvider);
        await tester.pumpAndSettle();
        await tester.tap(find.byIcon(Icons.calendar_month_rounded));
        await tester.pumpAndSettle();
        final after = tester.widget<DatePickerDialog>(
          find.byType(DatePickerDialog),
        );
        expect(after.initialDate, DateTime(2001, 2, 5));
        expect(after.currentDate, DateTime(2001, 2, 4));
        expect(after.firstDate, DateTime(2001, 2, 4));
        expect(tester.takeException(), isNull);
      },
    );

    testWidgets(
      '${screen.key} calendar shows loading and retry without guessing a date',
      (tester) async {
        final config = _Config()..pending = Completer<void>();
        await tester.pumpWidget(
          ProviderScope(
            overrides: [
              appConfigRepositoryProvider.overrideWithValue(config),
              travelRepositoryProvider.overrideWithValue(_Travel()),
            ],
            child: MaterialApp(home: screen.value),
          ),
        );
        await tester.pump();
        expect(find.byType(LinearProgressIndicator), findsWidgets);
        expect(find.byIcon(Icons.calendar_month_rounded), findsNothing);
        config.date = null;
        config.pending!.complete();
        await tester.pumpAndSettle();
        expect(find.text('Could not load travel date. Retry'), findsOneWidget);
        expect(find.byIcon(Icons.calendar_month_rounded), findsNothing);
        config.date = '2040-05-04';
        await tester.ensureVisible(
          find.text('Could not load travel date. Retry'),
        );
        await tester.tap(find.text('Could not load travel date. Retry'));
        await tester.pumpAndSettle();
        config.fail = true;
        await tester.tap(find.byIcon(Icons.calendar_month_rounded));
        await tester.pumpAndSettle();
        expect(find.byType(DatePickerDialog), findsNothing);
        expect(
          find.text('Could not load the travel date. Please retry.'),
          findsOneWidget,
        );
      },
    );
  }
}
