import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kt_mobile_app/features/travel/application/travel_providers.dart';
import 'package:kt_mobile_app/features/travel/application/travel_calendar.dart';
import 'package:kt_mobile_app/features/travel/data/travel_repository.dart';
import 'package:kt_mobile_app/features/travel/domain/travel_models.dart';
import 'package:kt_mobile_app/features/travel/presentation/lovable_search_flow.dart';

const _rawalpindi = CityOption(id: 1, name: 'Rawalpindi');
const _faisalabad = CityOption(id: 3, name: 'Faisalabad');

class _CatalogRepository extends Fake implements TravelRepository {
  int cityReads = 0;
  int destinationReads = 0;
  int scheduleReads = 0;
  List<CityOption> availableDestinations = [];

  @override
  Future<List<CityOption>> cities() async {
    cityReads++;
    return [_rawalpindi];
  }

  @override
  Future<List<CityOption>> destinations(int originId) async {
    expect(originId, _rawalpindi.id);
    destinationReads++;
    return [...availableDestinations];
  }

  @override
  Future<List<TravelSchedule>> schedules(SearchQuery query) async {
    scheduleReads++;
    return [];
  }
}

void main() {
  test(
    'repeating a search refetches instead of retaining old departures',
    () async {
      final repository = _CatalogRepository();
      final container = ProviderContainer(
        overrides: [travelRepositoryProvider.overrideWithValue(repository)],
      );
      addTearDown(container.dispose);
      final query = SearchQuery(
        origin: _rawalpindi,
        destination: _faisalabad,
        date: DateTime(2099),
      );
      final first = container.listen(schedulesProvider(query), (_, _) {});
      await container.read(schedulesProvider(query).future);
      first.close();
      await container.pump();
      final next = container.listen(schedulesProvider(query), (_, _) {});
      addTearDown(next.close);
      await container.read(schedulesProvider(query).future);
      expect(repository.scheduleReads, 2);
    },
  );

  test('a new search visit reloads newly shared destinations', () async {
    final repository = _CatalogRepository();
    final container = ProviderContainer(
      overrides: [travelRepositoryProvider.overrideWithValue(repository)],
    );
    addTearDown(container.dispose);
    final firstCities = container.listen(citiesProvider, (_, _) {});
    final firstDestinations = container.listen(
      destinationsProvider(1),
      (_, _) {},
    );
    await container.read(citiesProvider.future);
    expect(await container.read(destinationsProvider(1).future), isEmpty);
    firstCities.close();
    firstDestinations.close();
    await container.pump();

    repository.availableDestinations = [_faisalabad];
    final nextCities = container.listen(citiesProvider, (_, _) {});
    final nextDestinations = container.listen(
      destinationsProvider(1),
      (_, _) {},
    );
    addTearDown(nextCities.close);
    addTearDown(nextDestinations.close);
    await container.read(citiesProvider.future);
    expect(
      (await container.read(destinationsProvider(1).future)).single.name,
      'Faisalabad',
    );
    expect(repository.cityReads, 2);
    expect(repository.destinationReads, 2);
  });

  testWidgets('refresh cities reloads the catalog without restarting the app', (
    tester,
  ) async {
    final repository = _CatalogRepository();
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          travelRepositoryProvider.overrideWithValue(repository),
          travelCalendarProvider.overrideWith(
            (ref) async => TravelCalendar.fromServerDate('2040-05-04'),
          ),
        ],
        child: const MaterialApp(home: SearchScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(repository.cityReads, 1);
    await tester.tap(find.byTooltip('Refresh cities'));
    await tester.pumpAndSettle();
    expect(repository.cityReads, 2);
    expect(tester.takeException(), isNull);
  });
}
