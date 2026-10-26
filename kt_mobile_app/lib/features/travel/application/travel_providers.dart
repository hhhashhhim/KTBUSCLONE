import 'package:flutter/painting.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../../auth/application/auth_controller.dart';
import '../data/travel_repository.dart';
import '../domain/travel_models.dart';

final travelRepositoryProvider = Provider<TravelRepository>(
  (ref) => TravelRepository(ref.watch(apiClientProvider)),
);

final citiesProvider = FutureProvider<List<CityOption>>(
  (ref) => ref.watch(travelRepositoryProvider).cities(),
);

// Key private caches by login session. Separate provider instances also prevent
// AsyncValue's previous-data loading/error state from exposing another account.
final savedPassengersProvider = FutureProvider.autoDispose
    .family<List<SavedPassenger>, PassengerSession>((ref, session) {
      if (!_isCurrentSession(ref, session)) return const [];
      return ref.watch(travelRepositoryProvider).savedPassengers();
    });

final fleetGalleryProvider = FutureProvider<List<FleetMedia>>((ref) async {
  final passengerId = ref.watch(
    authControllerProvider.select((state) => state.passenger?.id),
  );
  if (passengerId == null) return const [];

  final items = await ref.watch(travelRepositoryProvider).fleet();
  if (!ref.mounted) return const [];
  // A published URL can keep its name while its image bytes change. Evict
  // only catalog images so a successful refresh also reloads their pixels.
  await Future.wait(
    items
        .map((item) => item.imageUrl)
        .where((url) => url.isNotEmpty)
        .toSet()
        .map((url) => NetworkImage(url).evict()),
  );
  return items;
}, retry: (_, _) => null);

final loyaltyWalletProvider = FutureProvider.autoDispose
    .family<LoyaltyWallet, PassengerSession>((ref, session) {
      if (!_isCurrentSession(ref, session)) {
        return const LoyaltyWallet(active: false, pointsAvailable: 0);
      }
      return ref.watch(travelRepositoryProvider).wallet();
    });

final destinationsProvider = FutureProvider.family<List<CityOption>, int>(
  (ref, originId) => ref.watch(travelRepositoryProvider).destinations(originId),
);

final schedulesProvider =
    FutureProvider.family<List<TravelSchedule>, SearchQuery>(
      (ref, query) => ref.watch(travelRepositoryProvider).schedules(query),
      // Show search failures immediately; the screen offers an explicit retry.
      retry: (retryCount, error) => null,
    );

final bookingsProvider = FutureProvider.autoDispose
    .family<List<BookingSummary>, PassengerSession>(
      (ref, session) {
        if (!_isCurrentSession(ref, session)) return const [];
        return ref.watch(travelRepositoryProvider).bookings();
      },
      // Surface failures immediately so My Trips can offer an explicit retry.
      retry: (retryCount, error) => null,
    );

bool _isCurrentSession(Ref ref, PassengerSession session) {
  final current = ref.watch(passengerSessionProvider);
  return session.passengerId != null && current == session;
}

class BookingFlowState {
  const BookingFlowState({
    this.query,
    this.schedule,
    this.availableSeats = const [],
    this.selectedSeats = const [],
    this.passengers = const [],
    this.quote,
  });

  final SearchQuery? query;
  final TravelSchedule? schedule;
  final List<TravelSeat> availableSeats;
  final List<TravelSeat> selectedSeats;
  final List<PassengerDetails> passengers;
  final FareQuote? quote;

  BookingFlowState copyWith({
    SearchQuery? query,
    TravelSchedule? schedule,
    List<TravelSeat>? availableSeats,
    List<TravelSeat>? selectedSeats,
    List<PassengerDetails>? passengers,
    FareQuote? quote,
    bool clearQuote = false,
  }) => BookingFlowState(
    query: query ?? this.query,
    schedule: schedule ?? this.schedule,
    availableSeats: availableSeats ?? this.availableSeats,
    selectedSeats: selectedSeats ?? this.selectedSeats,
    passengers: passengers ?? this.passengers,
    quote: clearQuote ? null : quote ?? this.quote,
  );
}

final bookingFlowProvider =
    NotifierProvider<BookingFlowController, BookingFlowState>(
      BookingFlowController.new,
    );

class BookingFlowController extends Notifier<BookingFlowState> {
  @override
  BookingFlowState build() {
    ref.watch(passengerSessionProvider);
    return const BookingFlowState();
  }

  void begin(SearchQuery query, TravelSchedule schedule) {
    state = BookingFlowState(query: query, schedule: schedule);
  }

  void setSeatLayout(List<TravelSeat> seats) {
    state = state.copyWith(availableSeats: seats);
  }

  bool toggleSeat(TravelSeat seat, int maximumSelectable) {
    final selected = [...state.selectedSeats];
    final index = selected.indexWhere((item) => item.number == seat.number);
    if (index >= 0) {
      selected.removeAt(index);
    } else {
      if (!seat.selectable || selected.length >= maximumSelectable) {
        return false;
      }
      selected.add(seat);
    }
    state = state.copyWith(
      selectedSeats: selected,
      passengers: const [],
      clearQuote: true,
    );
    return true;
  }

  void setPassengers(List<PassengerDetails> passengers) {
    state = state.copyWith(passengers: passengers, clearQuote: true);
  }

  void setQuote(FareQuote quote) {
    state = state.copyWith(quote: quote);
  }

  void reset() => state = const BookingFlowState();
}
