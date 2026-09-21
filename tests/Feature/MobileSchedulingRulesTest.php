<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileSchedulingRulesTest extends TestCase
{
    private $date = '2026-09-13';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mobile_payments.preview_only', false);
        // These are real model/service/HTTP tests against a disposable connection.
        // Never run the legacy ERP migrations against a developer's configured database.
        config()->set('database.connections.mobile_rules_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        config()->set('database.default', 'mobile_rules_testing');
        config()->set('cache.default', 'array');
        config()->set('mobile.company_id', 1);
        config()->set('mobile.terminal_id', 10);
        config()->set('mobile.booking_user_id', 20);
        config()->set('mobile.payment_methods', ['counter']);
        config()->set('mobile.features.wallet', false);
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00', 'Asia/Karachi'));
        $this->createSchema();
        $this->seedJourney();
        Sanctum::actingAs(new PassengerAccount([
            'id' => 99, 'company_id' => 1, 'mobile' => '03001234567', 'mobile_verified_at' => now(),
        ]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('mobile_rules_testing');
        parent::tearDown();
    }

    public function test_city_catalog_only_lists_upcoming_origins_and_reachable_destinations()
    {
        DB::table('cities')->insert([
            ['id' => 3, 'company_id' => 1, 'name' => 'Unused city'],
            ['id' => 4, 'company_id' => 1, 'name' => 'Fare only'],
        ]);
        DB::table('routes_fares')->insert([
            'company_id' => 1, 'route_id' => 30, 'departure_city_id' => 1, 'destination_city_id' => 4,
        ]);
        $this->copySearchRun(41, 51, $this->date);
        $this->getJson('/api/mobile/v1/cities')->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'Cities retrieved.',
                'data' => [['id' => 1, 'name' => 'Origin']], 'errors' => null]);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'Destinations retrieved.',
                'data' => [['id' => 2, 'name' => 'Destination']], 'errors' => null]);
        $this->getJson('/api/mobile/v1/destinations?origin_id=2')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/mobile/v1/destinations?origin_id=999')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/mobile/v1/destinations')->assertStatus(422)->assertJsonValidationErrors('origin_id', 'errors');
    }

    /** @dataProvider cityCatalogRestrictions */
    public function test_city_catalog_excludes_runs_that_are_not_mobile_visible(string $restriction)
    {
        $this->restrictJourney($restriction);
        $this->getJson('/api/mobile/v1/cities')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()->assertJsonPath('data', []);
    }

    public static function cityCatalogRestrictions(): array
    {
        return array_values(array_filter(self::scheduleRestrictions(), function ($case) {
            // City discovery covers active services even before sales open for a run.
            return $case[0] !== 'booking_not_open';
        }));
    }

    /** @dataProvider unavailableCityCatalogRecords */
    public function test_city_catalog_excludes_deleted_and_foreign_company_records(string $table, string $column, $value)
    {
        DB::table($table)->update([$column => $value]);
        $this->getJson('/api/mobile/v1/cities')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()->assertJsonPath('data', []);
    }

    public static function unavailableCityCatalogRecords(): array
    {
        $cases = [];
        foreach (['cities', 'schedule_details', 'schedules', 'routes'] as $table) {
            $cases[$table . ' deleted'] = [$table, 'deleted_at', '2026-09-12 00:00:00'];
            $cases[$table . ' foreign company'] = [$table, 'company_id', 2];
        }
        return $cases;
    }

    public function test_city_catalog_uses_each_segments_pair_for_online_visibility_and_sorts_cities()
    {
        DB::table('cities')->insert(['id' => 3, 'company_id' => 1, 'name' => 'Alpha stop']);
        $detail = (array) DB::table('schedule_details')->where('id', 50)->first();
        DB::table('schedule_details')->insert(array_merge($detail, ['id' => 51, 'destination_id' => 3]));
        DB::table('schedule_details')->insert(array_merge($detail, ['id' => 52, 'departure_id' => 3]));
        DB::table('terminal_visibilities')->update(['online_visibilty' => 1]);
        $this->getJson('/api/mobile/v1/cities')->assertOk()->assertJsonPath('data', [
            ['id' => 3, 'name' => 'Alpha stop'], ['id' => 1, 'name' => 'Origin'],
        ]);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()
            ->assertJsonPath('data', [['id' => 3, 'name' => 'Alpha stop']]);
        $this->getJson('/api/mobile/v1/destinations?origin_id=3')->assertOk()
            ->assertJsonPath('data', [['id' => 2, 'name' => 'Destination']]);
        DB::table('terminal_visibilities')->update(['online_visibilty' => 0]);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()->assertJsonPath('data', [
            ['id' => 3, 'name' => 'Alpha stop'], ['id' => 2, 'name' => 'Destination'],
        ]);
    }

    public function test_city_catalog_drops_only_matching_runs_and_refreshes_without_caching()
    {
        // The second segment departs tomorrow, but its run began tonight.
        $this->copySearchRun(41, 51, '2026-09-12');
        DB::table('drop_schedules')->insert([
            ['company_id' => 1, 'schedule_id' => 40, 'schedule_date' => $this->date],
            ['company_id' => 1, 'schedule_id' => 41, 'schedule_date' => $this->date],
            ['company_id' => 2, 'schedule_id' => 41, 'schedule_date' => '2026-09-12'],
        ]);
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $this->assertSame([1], $service->cities()->pluck('id')->all());
        $this->assertSame([2], $service->destinations(1)->pluck('id')->all());
        DB::table('drop_schedules')->insert(['company_id' => 1, 'schedule_id' => 41, 'schedule_date' => '2026-09-12']);
        $this->assertCount(0, $service->cities());
        $this->assertCount(0, $service->destinations(1));
        DB::table('drop_schedules')->update(['deleted_at' => now()]);
        $this->assertSame([1], $service->cities()->pluck('id')->all());
        $this->assertSame([2], $service->destinations(1)->pluck('id')->all());
    }

    public function test_city_catalog_uses_upcoming_departure_time_and_exclusive_advance_booking_dates()
    {
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        foreach ([['2026-09-11', '13:00:00'], ['2026-09-12', '11:59:59'], ['2026-09-12', '12:00:00'], ['2026-09-22', '13:00:00']] as [$date, $time]) {
            DB::table('schedule_details')->update(['departure_date' => $date, 'departure_time' => $time]);
            $this->assertCount(0, $service->cities(), $date . ' ' . $time);
            $this->assertCount(0, $service->destinations(1), $date . ' ' . $time);
        }
        foreach ([['2026-09-12', '12:00:01'], ['2026-09-21', '23:59:59']] as [$date, $time]) {
            DB::table('schedule_details')->update(['departure_date' => $date, 'departure_time' => $time]);
            $this->assertSame([1], $service->cities()->pluck('id')->all());
            $this->assertSame([2], $service->destinations(1)->pluck('id')->all());
        }
        DB::table('terminals')->update(['advance_booking' => null]);
        DB::table('schedule_details')->update(['departure_date' => '2026-10-12']);
        $this->assertSame([1], $service->cities()->pluck('id')->all());
    }

    public function test_city_catalog_keeps_active_services_before_booking_opens_or_when_seats_sell_out()
    {
        $this->restrictJourney('booking_not_open');
        $this->limitOnlineSeats(0);
        $this->getJson('/api/mobile/v1/cities')->assertOk()->assertJsonPath('data.0.id', 1);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertOk()->assertJsonPath('data.0.id', 2);
        $this->getJson($this->searchUrl())->assertOk()->assertJsonPath('data', []);
    }

    public function test_city_catalog_fails_closed_for_an_invalid_mobile_terminal()
    {
        DB::table('terminals')->update(['is_online_terminal' => 0]);
        $this->getJson('/api/mobile/v1/cities')->assertStatus(503)->assertJsonPath('success', false);
        $this->getJson('/api/mobile/v1/destinations?origin_id=1')->assertStatus(503)->assertJsonPath('success', false);
    }

    public function test_city_catalog_query_count_stays_constant_as_cities_and_runs_increase()
    {
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $measure = function () use ($service) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $origins = $service->cities();
                $destinations = $service->destinations(1);
                return [$origins, $destinations, DB::getQueryLog()];
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }
        };
        [$oneOrigin, $oneDestination, $oneQueries] = $measure();
        $this->assertCount(1, $oneOrigin);
        $this->assertCount(1, $oneDestination);
        for ($i = 1; $i <= 20; $i++) {
            DB::table('cities')->insert(['id' => 100 + $i, 'company_id' => 1, 'name' => 'City ' . $i]);
            $this->copySearchRun(100 + $i, 200 + $i, $this->date);
            DB::table('schedule_details')->where('id', 200 + $i)->update(['destination_id' => 100 + $i]);
            $this->copySearchRun(200 + $i, 300 + $i, $this->date);
            DB::table('schedule_details')->where('id', 300 + $i)->update(['departure_id' => 100 + $i]);
        }
        [$manyOrigins, $manyDestinations, $manyQueries] = $measure();
        $this->assertCount(21, $manyOrigins);
        $this->assertCount(21, $manyDestinations);
        $this->assertCount(count($oneQueries), $manyQueries, 'Catalog queries must not grow per city or run.');
        $this->assertCount(4, $manyQueries, 'Each dropdown needs terminal validation and one city query.');
        foreach ($manyQueries as $query) {
            foreach (['tickets', 'fare_tables', 'bus_classes'] as $expensiveTable) {
                $this->assertStringNotContainsString($expensiveTable, $query['query']);
            }
        }
    }


    public function test_live_preview_shows_methods_but_cannot_create_a_booking()
    {
        $this->paymentInput();
        config()->set('mobile_payments.preview_only', true);
        config()->set('mobile_payments.environment', 'live');
        config()->set('app.url', 'http://127.0.0.1:8000');
        config()->set('mobile_payments.jazzcash.checkout_url', 'https://onlinepayments.jazzcash.com.pk/merchantform');
        config()->set('mobile_payments.jazzcash.status_url', 'https://onlinepayments.jazzcash.com.pk/status');
        \Illuminate\Support\Facades\Http::fake();
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())
            ->assertOk()->assertJsonPath('data.payment_preview', true)
            ->assertJsonPath('data.payment_environment', 'live');
        $this->assertContains('jazzcash', $quote->json('data.payment_methods'));
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'jazzcash',
        ])->assertStatus(409)->assertJsonPath('message', 'Payment preview only. No booking or payment will be submitted.');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('mobile_payments', 0);
        $this->assertDatabaseHas('mobile_booking_quotes', ['used_at' => null]);
        \Illuminate\Support\Facades\Http::assertNothingSent();
        config()->set('mobile_payments.preview_only', false);
        $this->assertNotContains('jazzcash', app(\App\Services\Mobile\MobileBookingService::class)->paymentMethods());
    }

    public function test_allowed_journey_can_be_searched_quoted_and_booked()
    {
        $this->getJson($this->searchUrl())->assertOk()->assertJsonPath('data.0.available_seats', 3);
        $this->getJson($this->seatsUrl())->assertOk()->assertJsonPath('data.seats.0.status', 'available');
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertCreated();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('tickets', ['seat_no' => '1', 'online_terminal' => 1]);
    }

    /** @dataProvider bookingGenderMethods */
    public function test_booking_stores_erp_gender_codes_and_returns_mobile_labels(string $method)
    {
        \Illuminate\Support\Facades\Http::fake();
        if ($method !== 'counter') {
            $this->paymentInput();
            config()->set('mobile.payment_methods', ['jazzcash', 'bank_alfalah']);
            config()->set('mobile_payments.bank_alfalah', [
                'merchant_id' => '123', 'store_id' => '456', 'merchant_hash' => 'test-hash',
                'username' => 'test-user', 'password' => 'test-password',
                'key1' => '1234567890123456', 'key2' => 'abcdefghijklmnop',
                'base_url' => 'https://sandbox.bankalfalah.com',
            ]);
        }
        // A partial journey also exercises the ERP partial-ticket history table.
        DB::table('cities')->insert(['id' => 3, 'company_id' => 1, 'name' => 'Final city']);
        DB::table('routes_fares')->insert([
            'route_id' => 30, 'company_id' => 1, 'departure_city_id' => 2, 'destination_city_id' => 3,
        ]);
        $input = $this->quoteInput(['1', '2']);
        $input['passengers'][1]['gender'] = 'female';
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $input)->assertOk();
        $booking = $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => $method,
        ])->assertCreated()->assertJsonPath('data.passengers.0.gender', 'male')
            ->assertJsonPath('data.passengers.1.gender', 'female');
        $invoice = $booking->json('data.id');
        foreach (['tickets', 'ticket_advanced_bookeds', 'ticket_is_partials'] as $table) {
            $this->assertSame([1, 0], DB::table($table)->orderBy('id')->pluck('gender')->all(), $table);
            $this->assertSame(['advance booking', 'advance booking'], DB::table($table)->orderBy('id')->pluck('type')->all(), $table);
        }
        $this->getJson('/api/mobile/v1/bookings/' . $invoice)->assertOk()
            ->assertJsonPath('data.passengers.0.gender', 'male')
            ->assertJsonPath('data.passengers.1.gender', 'female');
        $this->getJson('/api/mobile/v1/bookings')->assertOk()
            ->assertJsonPath('data.items.0.passengers.0.gender', 'male')
            ->assertJsonPath('data.items.0.passengers.1.gender', 'female');
        if ($method !== 'counter') {
            $booking->assertJsonPath('data.payment.method', $method)
                ->assertJsonPath('data.payment.status', 'pending');
        }
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public static function bookingGenderMethods(): array
    {
        return [['counter'], ['jazzcash'], ['bank_alfalah']];
    }

    public function test_unsupported_booking_gender_returns_validation_error_without_creating_quote()
    {
        $input = $this->quoteInput();
        $input['passengers'][0]['gender'] = 'other';
        $this->postJson('/api/mobile/v1/bookings/quote', $input)->assertStatus(422)
            ->assertJsonValidationErrors(['passengers.0.gender']);
        $this->assertDatabaseCount('mobile_booking_quotes', 0);
    }

    public function test_old_quote_with_unsupported_gender_cannot_create_mislabelled_tickets()
    {
        $input = $this->paymentInput();
        $quote = \App\Models\MobileBookingQuote::where('token', $input['quote_token'])->firstOrFail();
        $payload = $quote->payload;
        $payload['passengers'][0]['gender'] = 'other';
        $quote->update(['payload' => $payload]);
        $this->postJson('/api/mobile/v1/bookings', $input)->assertStatus(422)
            ->assertJsonPath('message', 'This passenger gender is not supported for ticket booking. Please contact support.');
        foreach (['tickets', 'ticket_advanced_bookeds', 'ticket_is_partials', 'invoices', 'customers', 'mobile_payments'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertNull($quote->fresh()->used_at);
    }

    /** @dataProvider scheduleRestrictions */
    public function test_restricted_journey_cannot_be_searched_previewed_or_quoted(string $restriction)
    {
        $this->restrictJourney($restriction);
        $this->getJson($this->searchUrl())->assertOk()->assertJsonPath('data', []);
        $this->getJson($this->seatsUrl())->assertNotFound()->assertJsonPath('success', false);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())
            ->assertNotFound()->assertJsonPath('success', false);
        $this->assertDatabaseCount('mobile_booking_quotes', 0);
    }

    /** @dataProvider scheduleRestrictions */
    public function test_erp_restriction_changed_after_quote_prevents_booking(string $restriction)
    {
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $this->restrictJourney($restriction);
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertNotFound()->assertJsonPath('success', false);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseHas('mobile_booking_quotes', ['used_at' => null]);
    }

    public static function scheduleRestrictions(): array
    {
        return array_map(function ($name) { return [$name]; }, [
            'online_city_pair', 'no_schedule_permissions', 'schedule_visibility_disabled',
            'visibility_for_other_terminal', 'visibility_for_other_company', 'deleted_permission',
            'dropped_run', 'hidden_schedule', 'hidden_route', 'hidden_origin', 'hidden_destination',
            'advance_booking_boundary', 'unassigned_online_terminal', 'booking_not_open',
        ]);
    }

    public function test_reenabling_online_booking_takes_effect_without_restarting_service()
    {
        $this->restrictJourney('online_city_pair');
        $this->getJson($this->searchUrl())->assertJsonPath('data', []);
        DB::table('terminal_visibilities')->update(['online_visibilty' => 0]);
        $this->getJson($this->searchUrl())->assertJsonCount(1, 'data');
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
    }

    public function test_other_city_pairs_companies_and_run_dates_do_not_block_this_journey()
    {
        DB::table('terminal_visibilities')->insert([
            ['company_id' => 1, 'route_id' => 30, 'departure_city_id' => 1, 'destination_city_id' => 3, 'online_visibilty' => 1],
            ['company_id' => 2, 'route_id' => 30, 'departure_city_id' => 1, 'destination_city_id' => 2, 'online_visibilty' => 1],
        ]);
        DB::table('drop_schedules')->insert([
            ['company_id' => 1, 'schedule_id' => 40, 'schedule_date' => '2026-09-14'],
            ['company_id' => 2, 'schedule_id' => 40, 'schedule_date' => $this->date],
        ]);
        DB::table('route_online_terminals')->insert(['company_id' => 2, 'route_id' => 30, 'terminal_id' => 11]);
        $this->getJson($this->searchUrl())->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($this->seatsUrl())->assertOk();
    }

    public function test_explicit_online_terminal_assignment_allows_this_terminal()
    {
        DB::table('route_online_terminals')->insert(['company_id' => 1, 'route_id' => 30, 'terminal_id' => 10]);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
    }

    public function test_advance_booking_window_uses_exclusive_erp_boundary()
    {
        DB::table('terminals')->update(['advance_booking' => 1]);
        $this->getJson($this->searchUrl())->assertJsonPath('data', []);
        DB::table('terminals')->update(['advance_booking' => 2]);
        $this->getJson($this->searchUrl())->assertJsonCount(1, 'data');
    }

    public function test_booking_minutes_opens_at_erp_adjusted_time_and_honors_user_switch()
    {
        DB::table('users')->update(['check_booking_minutes' => 1]);
        DB::table('terminal_visibilities')->update(['booking_minutes' => 60]);
        DB::table('terminal_time_differences')->insert([
            'company_id' => 1, 'terminal_id' => 10, 'route_id' => 30, 'time_difference' => 30,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-09-13 11:29:59'));
        $this->getJson($this->searchUrl())->assertJsonPath('data', []);
        Carbon::setTestNow(Carbon::parse('2026-09-13 11:30:00'));
        $this->getJson($this->searchUrl())->assertJsonCount(1, 'data');
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));
        DB::table('users')->update(['check_booking_minutes' => 0]);
        $this->getJson($this->seatsUrl())->assertOk();
    }

    public function test_departure_date_passing_midnight_invalidates_a_previously_valid_quote()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 23:59:00'));
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        Carbon::setTestNow(Carbon::parse('2026-09-14 00:01:00'));
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertNotFound();
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_terminal_and_route_seat_restrictions_agree_with_search_count()
    {
        DB::table('terminals')->update(['available_seats' => '1-2']);
        DB::table('routes')->update(['online_seat_choices' => '2,3']);
        $this->getJson($this->searchUrl())->assertJsonPath('data.0.available_seats', 1);
        $this->getJson($this->seatsUrl())->assertJsonPath('data.seats.0.status', 'unavailable')
            ->assertJsonPath('data.seats.1.status', 'available');
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['1']))->assertStatus(409);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['2']))->assertOk();
    }

    public function test_online_quota_limits_selection_and_is_rechecked_after_other_terminal_books()
    {
        $this->limitOnlineSeats(2);
        $this->getJson($this->searchUrl())->assertJsonPath('data.0.available_seats', 2);
        $this->getJson($this->seatsUrl())->assertJsonPath('data.maximum_selectable_seats', 2);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['1', '2', '3']))->assertStatus(409);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['1', '2']))->assertOk();
        $this->addOnlineTicket('3');
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertStatus(409);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseHas('mobile_booking_quotes', ['used_at' => null]);
    }

    public function test_exhausted_online_quota_blocks_seats_and_new_mobile_sales_consume_quota()
    {
        $this->limitOnlineSeats(1);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertCreated();
        $this->getJson($this->searchUrl())->assertJsonPath('data.0.available_seats', 0);
        $this->getJson($this->seatsUrl())->assertJsonPath('data.maximum_selectable_seats', 0)
            ->assertJsonPath('data.seats.1.status', 'unavailable');
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['2']))->assertStatus(409);
    }

    public function test_online_quota_is_run_wide_but_does_not_count_other_runs_companies_or_cancelled_tickets()
    {
        $this->limitOnlineSeats(2);
        $this->addOnlineTicket('other-segment', ['departure_city_id' => 3, 'destination_city_id' => 4]);
        $this->addOnlineTicket('old-run', ['schedule_date' => '2026-09-12']);
        $this->addOnlineTicket('other-company', ['company_id' => 2]);
        $this->addOnlineTicket('cancelled', ['type' => 'cancelled']);
        $this->getJson($this->seatsUrl())->assertJsonPath('data.maximum_selectable_seats', 1);
        DB::table('limited_seats')->update(['limited_seat' => 0]);
        $this->getJson($this->seatsUrl())->assertJsonPath('data.maximum_selectable_seats', 5);
    }

    public function test_invalid_mobile_terminal_fails_closed_instead_of_bypassing_online_quota_accounting()
    {
        DB::table('terminals')->update(['is_online_terminal' => 0]);
        $this->getJson($this->searchUrl())->assertStatus(503);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertStatus(503);
    }

    private function restrictJourney(string $restriction): void
    {
        switch ($restriction) {
            case 'online_city_pair': DB::table('terminal_visibilities')->update(['online_visibilty' => 1]); break;
            case 'no_schedule_permissions': DB::table('schedule_terminal_visibilities')->delete(); break;
            case 'schedule_visibility_disabled': DB::table('schedule_terminal_visibilities')->update(['visibility' => 0]); break;
            case 'visibility_for_other_terminal': DB::table('schedule_terminal_visibilities')->update(['terminal_id' => 11]); break;
            case 'visibility_for_other_company': DB::table('schedule_terminal_visibilities')->update(['company_id' => 2]); break;
            case 'deleted_permission': DB::table('schedule_terminal_visibilities')->update(['deleted_at' => now()]); break;
            case 'dropped_run': DB::table('drop_schedules')->insert(['company_id' => 1, 'schedule_id' => 40, 'schedule_date' => $this->date]); break;
            case 'hidden_schedule': DB::table('schedules')->update(['hide' => 1]); break;
            case 'hidden_route': DB::table('routes')->update(['hide' => 1]); break;
            case 'hidden_origin': DB::table('cities')->where('id', 1)->update(['hide' => 1]); break;
            case 'hidden_destination': DB::table('cities')->where('id', 2)->update(['hide' => 1]); break;
            case 'advance_booking_boundary': DB::table('terminals')->update(['advance_booking' => 1]); break;
            case 'unassigned_online_terminal': DB::table('route_online_terminals')->insert(['company_id' => 1, 'route_id' => 30, 'terminal_id' => 11]); break;
            case 'booking_not_open':
                DB::table('users')->update(['check_booking_minutes' => 1]);
                DB::table('terminal_visibilities')->update(['booking_minutes' => 60]);
                break;
            default: $this->fail('Unknown restriction: ' . $restriction);
        }
    }

    private function limitOnlineSeats(int $count): void
    {
        DB::table('routes')->update(['online_seats' => $count]);
        DB::table('limited_seats')->insert([
            'company_id' => 1, 'route_id' => 30, 'departure_city_id' => 1, 'destination_city_id' => 2, 'limited_seat' => 1,
        ]);
    }

    private function addOnlineTicket(string $seat, array $overrides = []): void
    {
        DB::table('tickets')->insert(array_merge([
            'company_id' => 1, 'schedule_id' => 40, 'schedule_date' => $this->date,
            'seat_no' => $seat, 'departure_city_id' => 1, 'destination_city_id' => 2,
            'type' => 'advance booking', 'online_terminal' => 1, 'terminal_id' => 11,
        ], $overrides));
    }

    private function searchUrl(): string
    {
        return '/api/mobile/v1/schedules?origin_id=1&destination_id=2&date=' . $this->date;
    }

    private function seatsUrl(): string
    {
        return '/api/mobile/v1/schedules/50/seats?origin_id=1&destination_id=2&date=' . $this->date;
    }

    private function quoteInput(array $seats = ['1']): array
    {
        return [
            'origin_id' => 1, 'destination_id' => 2, 'date' => $this->date, 'schedule_detail_id' => 50,
            'seats' => $seats, 'passengers' => array_map(function ($seat) {
                return ['seat_number' => $seat, 'full_name' => 'Test Passenger', 'cnic' => '3520212345671',
                    'mobile' => '03001234567', 'gender' => 'male'];
            }, $seats),
        ];
    }

    public function test_preview_blocks_existing_checkout_and_refresh_without_gateway_calls()
    {
        $input = $this->paymentInput();
        $created = $this->postJson('/api/mobile/v1/bookings', $input)->assertCreated();
        $url = $created->json('data.payment.checkout_url');
        $invoice = $created->json('data.id');
        config()->set('mobile_payments.preview_only', true);
        \Illuminate\Support\Facades\Http::fake();
        $this->get($url)->assertStatus(409);
        $this->postJson('/api/mobile/v1/bookings/' . $invoice . '/payment/refresh')->assertStatus(409);
        $this->getJson('/api/mobile/v1/bookings/' . $invoice)
            ->assertOk()->assertJsonPath('data.payment.checkout_url', null);
        $this->assertDatabaseHas('mobile_payments', ['status' => 'pending', 'started_at' => null, 'checked_at' => null]);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_mobile_payment_creation_is_idempotent_and_never_issues_an_unpaid_qr()
    {
        $input = $this->paymentInput();
        $first = $this->postJson('/api/mobile/v1/bookings', $input)->assertCreated();
        $this->postJson('/api/mobile/v1/bookings', $input)->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $first->assertJsonPath('data.payment_status', 'pending')->assertJsonPath('data.qr_value', null)
            ->assertJsonPath('data.payment.method', 'jazzcash');
        $this->assertDatabaseCount('mobile_payments', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking', 'transaction_id' => null]);
        $this->assertStringNotContainsString('test-password', $first->getContent());
    }

    public function test_status_checks_before_checkout_preserve_the_original_payment_deadline()
    {
        \Illuminate\Support\Facades\Http::fake();
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $deadline = $booking->json('data.payment.expires_at');
        $this->assertSame(now()->addMinutes(10)->toIso8601String(), $deadline);
        $url = '/api/mobile/v1/bookings/' . $booking->json('data.id') . '/payment/refresh';
        foreach ([2, 16, 16] as $seconds) {
            Carbon::setTestNow(now()->addSeconds($seconds));
            $response = $this->postJson($url)->assertOk()
                ->assertJsonPath('data.payment.status', 'pending')
                ->assertJsonPath('data.payment.expires_at', $deadline);
            $this->assertNotNull($response->json('data.payment.checkout_url'));
        }
        $this->assertNull(\App\Models\MobilePayment::first()->started_at);
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking']);
        $this->assertDatabaseCount('booking_cancels', 0);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_mobile_payment_checkout_requires_signature_and_uses_server_amount()
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $url = $booking->json('data.payment.checkout_url');
        $this->get(strtok($url, '?'))->assertForbidden();
        $response = $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $response->assertSee('name="pp_Amount" value="250000"', false)
            ->assertSee('name="pp_BankID" value=""', false)
            ->assertSee('name="pp_ProductID" value=""', false);
        $this->get($url)->assertStatus(409)
            ->assertDontSee('<script', false)->assertDontSee('<form', false);
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking']);
    }

    /** @dataProvider automaticCheckoutMethods */
    public function test_checkout_automatically_forwards_the_signed_provider_form(string $method)
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        config()->set('mobile_payments.bank_alfalah', [
            'merchant_id' => '123', 'store_id' => '000456', 'merchant_hash' => 'test-hash',
            'username' => 'test-user', 'password' => 'test-password',
            'key1' => '1234567890123456', 'key2' => 'abcdefghijklmnop',
            'base_url' => 'https://sandbox.bankalfalah.com',
        ]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([
            'success' => 'true', 'AuthToken' => 'test-token',
        ])]);
        \App\Models\MobilePayment::first()->update(['method' => $method]);
        $response = $this->get($booking->json('data.payment.checkout_url'))->assertOk();
        $response->assertSee('Opening secure payment…')
            ->assertSee('id="provider-checkout" method="post"', false);
        $html = $response->getContent();
        $this->assertSame(1, preg_match('/<script nonce="([^"\s]+)">/', $html, $matches));
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'nonce-{$matches[1]}'", $policy);
        $this->assertStringNotContainsString("script-src 'unsafe-inline'", $policy);
        $this->assertStringContainsString("default-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $html);
        $this->assertStringNotContainsString('<button', preg_replace('/<noscript>.*?<\/noscript>/s', '', $html));
        $this->assertStringContainsString('<button type="submit">Continue to payment</button>', $html);
        $host = $method === 'jazzcash' ? 'https://sandbox.jazzcash.com.pk' : 'https://sandbox.bankalfalah.com';
        $this->assertStringContainsString('action="' . $host, $html);
        $this->assertStringContainsString($host, $policy);
        $this->assertNotNull(\App\Models\MobilePayment::first()->started_at);
        $this->getJson('/api/mobile/v1/bookings/' . $booking->json('data.id'))->assertOk()
            ->assertJsonPath('data.payment.checkout_url', null)->assertJsonPath('data.payment.status', 'pending');
    }

    public static function automaticCheckoutMethods(): array
    {
        return [['jazzcash'], ['bank_alfalah']];
    }

    public function test_mobile_payment_return_does_not_trust_forged_success_fields()
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        $payment->update(['started_at' => now()]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['pp_ResponseCode' => '000', 'pp_Status' => 'Pending'])]);
        $this->post('/api/mobile/v1/payments/' . $payment->public_id . '/return', ['pp_ResponseCode' => '000', 'pp_Status' => 'Completed', 'pp_Amount' => 250000])->assertOk()
            ->assertDontSee('<script', false)->assertDontSee('<form', false);
        $this->assertDatabaseHas('mobile_payments', ['status' => 'pending']);
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking']);
    }

    public function test_mobile_payment_verification_confirms_tickets_once()
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        $payment->update(['started_at' => now()]);
        $this->fakePaidPayment($payment);
        $url = '/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh';
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.payment_status', 'paid')->assertJsonPath('data.qr_value', 'KAINAT:' . $payment->invoice_id);
        $this->postJson($url)->assertOk();
        $this->assertDatabaseHas('tickets', ['type' => 'booked', 'transaction_id' => $payment->transaction_reference]);
        $this->assertSame(1, DB::table('activity_logs')->where('message', 'like', 'Payment verified;%')->count());
        \Illuminate\Support\Facades\Http::assertSentCount(1);
    }

    public function test_mobile_payment_expiry_releases_seats_and_returns_points_once()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        DB::table('card_assigns')->insert(['id' => 7, 'company_id' => 1, 'starting_points' => 8]);
        $payment->update(['wallet_card_id' => 7, 'wallet_points' => 2]);
        DB::table('tickets')->update(['points_usage' => 2]);
        Carbon::setTestNow(now()->addMinutes(11));
        $url = '/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh';
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'expired')->assertJsonPath('data.qr_value', null);
        Carbon::setTestNow(now()->addSeconds(16));
        $this->postJson($url)->assertOk();
        $this->assertDatabaseHas('card_assigns', ['id' => 7, 'starting_points' => 10]);
        $this->assertDatabaseCount('booking_cancels', 1);
        $this->assertSame(0, \App\Models\Ticket::count());
        $this->getJson($this->seatsUrl())->assertOk()->assertJsonPath('data.seats.0.status', 'available');
    }

    public function test_mobile_payment_received_after_expiry_requires_review_without_reviving_seats()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        Carbon::setTestNow(now()->addMinutes(11));
        $url = '/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh';
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'expired');
        $payment->update(['started_at' => $payment->created_at]);
        $this->fakePaidPayment($payment);
        Carbon::setTestNow(now()->addSeconds(16));
        $this->postJson($url)->assertOk()->assertJsonPath('data.status', 'review_required')
            ->assertJsonPath('data.payment_status', 'paid')->assertJsonPath('data.qr_value', null);
        $this->assertSame(0, \App\Models\Ticket::count());
    }

    public function test_mobile_payment_provider_outage_does_not_confirm_or_release_uncertain_payment()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        $payment->update(['started_at' => now()]);
        Carbon::setTestNow(now()->addMinutes(11));
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 503)]);
        $this->postJson('/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh')->assertStatus(503);
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking', 'deleted_at' => null]);
        $this->assertDatabaseHas('mobile_payments', ['status' => 'pending']);
    }

    public function test_mobile_payment_refresh_is_scoped_to_passenger_and_company()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        Sanctum::actingAs(new PassengerAccount(['id' => 100, 'company_id' => 1]));
        $this->postJson('/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh')->assertNotFound();
        Sanctum::actingAs(new PassengerAccount(['id' => 99, 'company_id' => 2]));
        $this->postJson('/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh')->assertNotFound();
    }

    /** @dataProvider reservationDeadlines */
    public function test_online_hold_respects_erp_terminal_reservation_deadline(int $terminalMinutes, int $expectedMinutes)
    {
        $input = $this->paymentInput();
        DB::table('terminals')->where('id', 10)->update(['reservation_cancel' => $terminalMinutes]);
        $this->postJson('/api/mobile/v1/bookings', $input)->assertCreated()
            ->assertJsonPath('data.payment.expires_at', now()->addMinutes($expectedMinutes)->toIso8601String());
        $this->assertDatabaseHas('tickets', ['type' => 'advance booking']);
    }

    public static function reservationDeadlines(): array
    {
        return [[0, 10], [5, 5], [20, 10]];
    }

    /** @dataProvider erpCancellationModes */
    public function test_erp_can_cancel_mobile_seats_with_existing_permissions(bool $issued, bool $bulk)
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        $originalCheckout = $booking->json('data.payment.checkout_url');
        if ($issued) {
            $payment->update(['started_at' => now()]);
            $this->fakePaidPayment($payment);
            $this->postJson('/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh')
                ->assertOk()->assertJsonPath('data.status', 'confirmed');
        }
        $ticket = \App\Models\Ticket::first();
        $this->createErpCancellationSchema();
        $permission = $issued ? 'cancel-ticket' : 'reserved-cancel';
        $role = \App\Models\admin\Role::create(['permissions' => []]);
        Sanctum::actingAs(new \App\Models\User([
            'id' => 20, 'company_id' => 1, 'role_id' => $role->id, 'name' => 'ERP operator',
        ]));
        $url = '/api/web/v1/booking/canceling' . ($bulk ? '/all' : '');
        $body = $bulk ? ['cancelAllSeat' => [$ticket->id], 'percentage' => 0, 'reason' => 'Staff cancellation'] : [
            'date' => $ticket->date, 'schedule_id' => $ticket->schedule_id, 'customer_id' => $ticket->customer_id,
            'departure_id' => $ticket->departure_city_id, 'destination_id' => $ticket->destination_city_id,
            'seat_no' => $ticket->seat_no, 'percentage' => 0, 'remarks' => 'Staff cancellation',
        ];
        $this->postJson($url, $body)->assertForbidden();
        $role->update(['permissions' => [['childs' => [['buttons' => [['name' => $permission, 'allow' => true]]]]]]]);
        $this->postJson($url, $body)->assertOk();
        $this->assertDatabaseHas('booking_cancels', [
            'ticket_id' => $ticket->id, 'type' => $issued ? 'booked' : 'advance booking', 'added_by' => 20,
        ]);
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id, 'type' => 'canceled']);

        Sanctum::actingAs(new PassengerAccount(['id' => 99, 'company_id' => 1]));
        $this->getJson('/api/mobile/v1/bookings/' . $payment->invoice_id)->assertOk()
            ->assertJsonPath('data.status', 'canceled')->assertJsonPath('data.payment.checkout_url', null)
            ->assertJsonPath('data.qr_value', null);
        $this->get($originalCheckout)->assertStatus(409);
        if (!$issued) {
            // A gateway receipt arriving after staff release cannot revive the seat.
            $payment->update(['started_at' => now()]);
            $this->fakePaidPayment($payment);
            $this->postJson('/api/mobile/v1/bookings/' . $payment->invoice_id . '/payment/refresh')
                ->assertOk()->assertJsonPath('data.payment.status', 'review_required')
                ->assertJsonPath('data.qr_value', null);
            $this->assertSoftDeleted('tickets', ['id' => $ticket->id, 'type' => 'canceled']);
            $this->assertDatabaseCount('booking_cancels', 1);
        }
    }

    public static function erpCancellationModes(): array
    {
        return ['reserved single' => [false, false], 'reserved bulk' => [false, true], 'issued single' => [true, false]];
    }

    private function createErpCancellationSchema(): void
    {
        // Legacy route files use require_once, so register the real booking
        // routes again for this test's fresh application instance.
        \Illuminate\Support\Facades\Route::prefix('api')->middleware('api')
            ->group(base_path('routes/api/booking.php'));
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->text('permissions'); $table->timestamps(); $table->softDeletes();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('refund_reason')->nullable(); $table->float('refund_percentage')->nullable();
            $table->float('refund_amount')->nullable();
        });
        Schema::table('card_assigns', function (Blueprint $table) { $table->string('cnic')->nullable(); });
        Schema::create('ticket_e_l_t_s', function (Blueprint $table) {
            $table->id(); $table->integer('ticket_id'); $table->timestamps(); $table->softDeletes();
        });
    }

    public function test_legacy_expiry_leaves_mobile_payments_to_reconciler_and_still_cancels_other_reservations()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $mobile = \App\Models\Ticket::first();
        DB::table('terminals')->where('id', 10)->update(['reservation_cancel' => 1]);
        $legacy = $mobile->replicate();
        $legacy->invoice_id = 999;
        $legacy->seat_no = '2';
        $legacy->save();
        $otherCompany = $mobile->replicate();
        $otherCompany->company_id = 2;
        $otherCompany->seat_no = '3';
        $otherCompany->save();
        Carbon::setTestNow(now()->addMinutes(11));
        $this->artisan('reserved:cancel')->assertExitCode(0);
        $this->assertDatabaseHas('tickets', ['id' => $mobile->id, 'type' => 'advance booking', 'deleted_at' => null]);
        $this->assertSoftDeleted('tickets', ['id' => $legacy->id, 'type' => 'canceled']);
        $this->assertSoftDeleted('tickets', ['id' => $otherCompany->id, 'type' => 'canceled']);
        $this->artisan('mobile:reconcile-payments')->assertExitCode(0);
        $this->assertSoftDeleted('tickets', ['id' => $mobile->id, 'type' => 'canceled']);
        $this->assertDatabaseHas('mobile_payments', ['status' => 'expired']);
        $this->assertSame(1, DB::table('booking_cancels')->where('ticket_id', $mobile->id)->count());
    }

    public function test_legacy_expiry_still_works_without_mobile_payment_table()
    {
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertCreated();
        Schema::create('booking_cancels', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('ticket_id'); $table->integer('percentage');
            $table->string('reason'); $table->string('type'); $table->integer('added_by'); $table->timestamps();
        });
        DB::table('terminals')->update(['reservation_cancel' => 1]);
        Carbon::setTestNow(now()->addMinutes(2));
        $this->artisan('reserved:cancel')->assertExitCode(0);
        $this->assertSame(0, \App\Models\Ticket::count());
        $this->assertDatabaseHas('booking_cancels', ['type' => 'advance booking', 'reason' => 'auto cancel']);
    }

    public function test_status_repair_is_scoped_idempotent_and_preserves_cancellations_and_deadlines()
    {
        $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $ticket = \App\Models\Ticket::first();
        $ticket->update(['type' => 'pending booking']);
        DB::table('ticket_advanced_bookeds')->update(['type' => 'pending booking']);
        // Model the real advance history schema, which has no deleted_at column.
        Schema::table('ticket_advanced_bookeds', function (Blueprint $table) { $table->dropColumn('deleted_at'); });
        DB::table('ticket_is_partials')->insert([
            'ticket_id' => $ticket->id, 'company_id' => 1, 'type' => 'pending booking',
        ]);
        $preserved = [];
        foreach ([
            ['invoice_id' => 999], ['company_id' => 2], ['deleted_at' => now()],
            ['type' => 'booked'], ['type' => 'canceled'],
        ] as $attributes) {
            $other = $ticket->replicate()->fill($attributes);
            $other->save();
            $preserved[$other->id] = (array) DB::table('tickets')->where('id', $other->id)->first();
        }
        $before = (array) DB::table('tickets')->where('id', $ticket->id)->first();
        $paymentBefore = DB::table('mobile_payments')->first();
        require_once base_path('database/migrations/2026_09_18_000012_align_mobile_reservations_with_erp_status.php');
        $repair = new \AlignMobileReservationsWithErpStatus();
        $repair->up();
        $repair->up();
        $repair->down();
        $before['type'] = 'advance booking';
        $this->assertEquals($before, (array) DB::table('tickets')->where('id', $ticket->id)->first());
        $this->assertEquals($paymentBefore, DB::table('mobile_payments')->first());
        foreach ($preserved as $id => $row) {
            $this->assertEquals($row, (array) DB::table('tickets')->where('id', $id)->first());
        }
        foreach (['ticket_advanced_bookeds', 'ticket_is_partials'] as $table) {
            $this->assertDatabaseHas($table, ['ticket_id' => $ticket->id, 'type' => 'advance booking']);
        }
    }


    private function paymentInput(): array
    {
        require_once base_path('database/migrations/2026_09_12_000008_create_mobile_payments_table.php');
        (new \CreateMobilePaymentsTable())->up();
        require_once base_path('database/migrations/2026_09_12_000009_add_environment_to_mobile_payments.php');
        (new \AddEnvironmentToMobilePayments())->up();
        config()->set('mobile_payments.environment', 'sandbox');
        Schema::table('tickets', function (Blueprint $table) { $table->string('transaction_id')->nullable(); });
        Schema::create('booking_cancels', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('ticket_id'); $table->integer('percentage');
            $table->string('reason'); $table->string('type'); $table->integer('added_by'); $table->timestamps();
        });
        Schema::create('card_assigns', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('starting_points'); $table->timestamps(); $table->softDeletes();
        });
        config()->set('app.url', 'https://mobile.example.test');
        \Illuminate\Support\Facades\URL::forceRootUrl('https://mobile.example.test');
        \Illuminate\Support\Facades\URL::forceScheme('https');
        config()->set('mobile_payments.enabled', true);
        config()->set('mobile.payment_methods', ['counter', 'jazzcash']);
        config()->set('mobile_payments.jazzcash', ['merchant_id' => 'test-merchant', 'password' => 'test-password',
            'integrity_salt' => 'test-salt', 'checkout_url' => 'https://sandbox.jazzcash.com.pk/merchantform',
            'status_url' => 'https://sandbox.jazzcash.com.pk/status']);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $quote->assertJsonPath('data.payment_methods.1', 'jazzcash');
        return ['quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'jazzcash'];
    }

    private function fakePaidPayment($payment): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([
            'pp_ResponseCode' => '000', 'pp_PaymentResponseCode' => '121', 'pp_Status' => 'Completed',
            'pp_TxnRefNo' => $payment->transaction_reference, 'pp_Amount' => (string) $payment->amount_minor,
            'pp_MerchantID' => 'test-merchant', 'pp_TxnCurrency' => 'PKR', 'pp_BillReference' => (string) $payment->invoice_id,
        ])]);
    }

    public function test_search_query_count_stays_bounded_as_matching_buses_increase()
    {
        $this->seedSearchAdjustments();
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $measure = function () use ($service) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $results = $service->schedules(1, 2, $this->date);
                return [$results, count(DB::getQueryLog())];
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }
        };
        [$one, $oneCount] = $measure();
        $this->assertCount(1, $one);
        for ($i = 1; $i < 20; $i++) {
            $this->copySearchRun(100 + $i, 200 + $i, $this->date);
        }
        [$many, $manyCount] = $measure();
        $this->assertCount(20, $many);
        $this->assertLessThanOrEqual(22, $manyCount, 'Search should batch queries across buses and fare classes.');
        $this->assertLessThanOrEqual($oneCount + 1, $manyCount, 'More buses must not add per-bus SQL queries.');
        foreach ($many as $row) {
            $this->assertSame($one->first()['fares'], $row['fares']);
            $this->assertSame(3, $row['available_seats']);
        }
    }

    public function test_batched_search_preserves_fare_adjustments_and_matches_direct_seat_lookup()
    {
        $this->seedSearchAdjustments();
        $this->copySearchRun(41, 51, $this->date);
        DB::table('schedules')->where('id', 41)->update(['discount_id' => null, 'surcharge_id' => null]);
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $rows = $service->schedules(1, 2, $this->date)->keyBy('schedule_detail_id');
        // ERP uses base + active surcharge, replacing both eligible discounts.
        $this->assertSame([2600.0, 4100.0], array_column($rows[50]['fares'], 'amount'));
        $this->assertSame([2400.0, 3800.0], array_column($rows[51]['fares'], 'amount'));
        foreach ($rows as $row) {
            $detail = $service->findDetail($row['schedule_detail_id'], 1, 2, $this->date);
            $this->assertSame($service->faresForDetail($detail, 1, 2), $row['fares']);
            $layout = $service->seatLayout($row['schedule_detail_id'], 1, 2, $this->date);
            $this->assertSame(count($layout['seats']), $row['total_seats']);
            $this->assertSame(collect($layout['seats'])->where('status', 'available')->count(), $row['available_seats']);
        }
        DB::table('fare_tables')->where('fare_class', 71)->delete();
        $this->getJson($this->searchUrl())->assertStatus(422)
            ->assertJsonPath('message', 'The fare table is incomplete for this schedule.');
    }

    public function test_batched_tickets_keep_overnight_runs_companies_and_cancelled_sales_separate()
    {
        $this->copySearchRun(41, 51, '2026-09-12');
        // Same schedule ID can also have another run arriving at this stop today.
        $detail = (array) DB::table('schedule_details')->where('id', 50)->first();
        $detail['id'] = 52;
        $detail['schedule_date'] = '2026-09-12';
        DB::table('schedule_details')->insert($detail);
        $this->limitOnlineSeats(3);
        $this->addOnlineTicket('1');
        $this->addOnlineTicket('2', ['schedule_id' => 41, 'schedule_date' => '2026-09-12']);
        $this->addOnlineTicket('3', ['schedule_id' => 41, 'schedule_date' => $this->date]);
        $this->addOnlineTicket('2', ['company_id' => 2]);
        $this->addOnlineTicket('3', ['type' => 'cancelled']);
        $this->addOnlineTicket('3', ['deleted_at' => now()]);
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $rows = $service->schedules(1, 2, $this->date)->keyBy('schedule_detail_id');
        $this->assertCount(3, $rows);
        $this->assertSame(2, $rows[50]['available_seats']);
        $this->assertSame(2, $rows[51]['available_seats']);
        $this->assertSame(3, $rows[52]['available_seats']);
        foreach ($rows as $row) {
            $layout = $service->seatLayout($row['schedule_detail_id'], 1, 2, $this->date);
            $this->assertSame(min(collect($layout['seats'])->where('status', 'available')->count(), $layout['maximum_selectable_seats']), $row['available_seats']);
        }
    }

    public function test_batched_search_counts_only_overlapping_seats_but_run_wide_online_sales()
    {
        DB::table('cities')->insert(['id' => 3, 'company_id' => 1, 'name' => 'Intermediate']);
        DB::table('routes_fares')->update(['destination_city_id' => 3]);
        DB::table('routes_fares')->insert(['company_id' => 1, 'route_id' => 30, 'departure_city_id' => 3, 'destination_city_id' => 2]);
        DB::table('schedule_details')->update(['departure_id' => 3]);
        DB::table('fare_tables')->update(['from_city_id' => 3]);
        $this->addOnlineTicket('1', ['departure_city_id' => 1, 'destination_city_id' => 3]);
        $this->addOnlineTicket('2', ['departure_city_id' => 3, 'destination_city_id' => 2, 'online_terminal' => 0]);
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $this->assertSame(2, $service->schedules(3, 2, $this->date)->first()['available_seats']);
        DB::table('routes')->update(['online_seats' => 2]);
        DB::table('limited_seats')->insert(['company_id' => 1, 'route_id' => 30,
            'departure_city_id' => 3, 'destination_city_id' => 2, 'limited_seat' => 1]);
        $this->assertSame(1, $service->schedules(3, 2, $this->date)->first()['available_seats']);
    }

    public function test_reusing_travel_service_does_not_reuse_search_data_after_erp_changes()
    {
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $this->assertSame(3, $service->schedules(1, 2, $this->date)->first()['available_seats']);
        $this->addOnlineTicket('1');
        DB::table('fare_tables')->update(['fare' => 3000]);
        $row = $service->schedules(1, 2, $this->date)->first();
        $this->assertSame(2, $row['available_seats']);
        $this->assertSame(3000.0, $row['fares'][0]['amount']);
        DB::table('terminals')->update(['available_seats' => '1']);
        $this->assertSame(0, $service->schedules(1, 2, $this->date)->first()['available_seats']);
        DB::table('users')->update(['check_booking_minutes' => 1]);
        DB::table('terminal_visibilities')->update(['booking_minutes' => 0]);
        $this->assertCount(0, $service->schedules(1, 2, $this->date));
    }

    public function test_batched_search_preserves_nullable_legacy_run_dates()
    {
        DB::table('schedule_details')->update(['schedule_date' => null]);
        $this->addOnlineTicket('1', ['schedule_date' => null]);
        $this->limitOnlineSeats(3);
        $service = app(\App\Services\Mobile\MobileTravelService::class);
        $this->assertSame(2, $service->schedules(1, 2, $this->date)->first()['available_seats']);
        $layout = $service->seatLayout(50, 1, 2, $this->date);
        $this->assertSame('booked', $layout['seats'][0]['status']);
        $this->assertSame(2, $layout['maximum_selectable_seats']);
    }

    private function copySearchRun(int $scheduleId, int $detailId, string $runDate): void
    {
        $schedule = (array) DB::table('schedules')->where('id', 40)->first();
        $schedule['id'] = $scheduleId;
        DB::table('schedules')->insert($schedule);
        $detail = (array) DB::table('schedule_details')->where('id', 50)->first();
        $detail['id'] = $detailId;
        $detail['schedule_id'] = $scheduleId;
        $detail['schedule_date'] = $runDate;
        DB::table('schedule_details')->insert($detail);
        DB::table('schedule_terminal_visibilities')->insert([
            'company_id' => 1, 'terminal_id' => 10, 'schedule_id' => $scheduleId, 'visibility' => 1,
        ]);
    }

    /** @dataProvider fareAdjustmentCases */
    public function test_fare_adjustments_agree_from_search_through_booking_and_legacy_validation(
        float $base, string $discountType, float $discountValue, float $terminalPercent,
        ?string $surchargeType, float $surchargeValue, float $expectedDiscount,
        float $expectedSurcharge, float $expectedRounding, float $expectedTotal
    ) {
        $this->seedSearchAdjustments();
        DB::table('fare_tables')->where('fare_class', 70)->update(['fare' => $base]);
        DB::table('discounts')->update(['type' => $discountType, 'flat' => $discountValue, 'percentage' => $discountValue]);
        DB::table('terminal_discounts')->update(['discount' => $terminalPercent]);
        DB::table('surcharges')->update(['is_active' => $surchargeType === null ? 0 : 1,
            'type' => $surchargeType ?? 'flat', 'flat' => $surchargeValue, 'percentage' => $surchargeValue]);

        $search = $this->getJson($this->searchUrl())->assertOk()->json('data.0.fares.0');
        $seat = $this->getJson($this->seatsUrl())->assertOk()->json('data.seats.0');
        $this->assertEquals($expectedTotal, $search['amount']);
        $this->assertEquals($expectedTotal, $seat['price']);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        foreach (['base_fare' => $base, 'discount' => $expectedDiscount, 'surcharge' => $expectedSurcharge,
            'rounding_adjustment' => $expectedRounding, 'total' => $expectedTotal] as $field => $expected) {
            $this->assertEquals($expected, $quote->json('data.' . $field), $field);
        }
        $this->assertQuoteAddsUp($quote->json('data'));
        $created = $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertCreated();
        $this->assertEquals($expectedTotal, $created->json('data.total'));
        $this->assertDatabaseHas('tickets', ['seat_no' => '1', 'seat_fare' => $expectedTotal]);

        // Exercise the real ERP/online fare validator with the same company/terminal.
        \Illuminate\Support\Facades\Auth::setUser(new \App\Models\User([
            'id' => 20, 'company_id' => 1, 'terminal_id' => 10,
        ]));
        $request = new \Illuminate\Http\Request([
            'schedule_id' => 40, 'departure_city_id' => 1, 'destination_city_id' => 2,
            'date' => $this->date, 'selected_seats' => ['1'],
            'selected_seats_class' => [70], 'selected_seats_fare' => [$expectedTotal],
        ]);
        $this->assertNull(seatFareIsWrong($request));
        $request->merge(['selected_seats_fare' => [$expectedTotal + 50]]);
        $this->assertEquals($expectedTotal, seatFareIsWrong($request)['expected_fare']);
    }

    public static function fareAdjustmentCases(): array
    {
        return [
            'unchanged fare' => [2525, 'flat', 0, 0, null, 0, 0, 0, 0, 2525],
            'percentage discount' => [2500, 'percentage', 10, 0, null, 0, 250, 0, 0, 2250],
            'flat discount' => [2500, 'flat', 200, 0, null, 0, 200, 0, 0, 2300],
            'terminal discount' => [2500, 'flat', 0, 5, null, 0, 125, 0, 25, 2400],
            'flat surcharge' => [2500, 'flat', 0, 0, 'flat', 100, 0, 100, 0, 2600],
            'percentage surcharge' => [2500, 'flat', 0, 0, 'percentage', 10, 0, 250, 0, 2750],
            'surcharge overrides percentage discount' => [2500, 'percentage', 10, 0, 'flat', 100, 0, 100, 0, 2600],
            'surcharge overrides flat discount' => [2500, 'flat', 100, 0, 'percentage', 10, 0, 250, 0, 2750],
            'flat surcharge overrides both discounts' => [2500, 'percentage', 10, 5, 'flat', 100, 0, 100, 0, 2600],
            'percentage surcharge overrides both discounts' => [2500, 'flat', 200, 5, 'percentage', 10, 0, 250, 0, 2750],
            'round down' => [2500, 'flat', 126, 0, null, 0, 126, 0, -24, 2350],
            'fractional terminal discount' => [2500, 'flat', 0, 1.25, null, 0, 31.25, 0, -18.75, 2450],
            'both discounts without surcharge' => [2500, 'percentage', 10, 5, null, 0, 375, 0, 25, 2150],
            'zero active surcharge still overrides discounts' => [2500, 'percentage', 10, 5, 'flat', 0, 0, 0, 0, 2500],
            'fractional schedule discount rounds before terminal discount' => [2500, 'percentage', 1.03, 1, null, 0, 51, 0, 1, 2450],
            'fractional flat discount uses ERP integer cast' => [2500, 'flat', 25.9, 0, null, 0, 25, 0, 25, 2500],
            'percentage surcharge rounds before nearest fifty' => [2500, 'flat', 0, 0, 'percentage', 2.99, 0, 75, 25, 2600],
            'free fare matches ERP' => [2500, 'flat', 2500, 0, null, 0, 2500, 0, 0, 0],
        ];
    }

    public function test_mixed_class_quote_sums_each_seat_adjustment_and_rounding()
    {
        $this->seedSearchAdjustments();
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['1', '3']))->assertOk();
        // ERP: 2500 + 100 = 2600; 4000 + 100 = 4100. Discounts are superseded.
        foreach (['base_fare' => 6500, 'discount' => 0, 'surcharge' => 200,
            'rounding_adjustment' => 0, 'total' => 6700] as $field => $expected) {
            $this->assertEquals($expected, $quote->json('data.' . $field), $field);
        }
        $this->assertQuoteAddsUp($quote->json('data'));
    }

    /** @dataProvider excludedFareAdjustments */
    public function test_inactive_unassigned_and_out_of_date_adjustments_are_excluded(string $excluded, float $expected)
    {
        $this->seedSearchAdjustments();
        if (!in_array($excluded, ['inactive surcharge', 'deleted surcharge'], true)) {
            DB::table('schedules')->update(['surcharge_id' => null]);
        }
        switch ($excluded) {
            case 'inactive discount': DB::table('discounts')->update(['is_active' => 0]); break;
            case 'another terminal': DB::table('schedule_terminal_discounts')->update(['terminal_id' => 11]); break;
            case 'expired terminal discount': DB::table('terminal_discounts')->update(['end_date' => '2026-09-12']); break;
            case 'future terminal discount': DB::table('terminal_discounts')->update(['start_date' => '2026-09-14']); break;
            case 'inactive surcharge': DB::table('surcharges')->update(['is_active' => 0]); break;
            case 'deleted surcharge': DB::table('surcharges')->update(['deleted_at' => now()]); break;
            case 'deleted discount': DB::table('discounts')->update(['deleted_at' => now()]); break;
            case 'deleted terminal discount': DB::table('terminal_discounts')->update(['deleted_at' => now()]); break;
        }
        $this->assertEquals($expected, $this->getJson($this->searchUrl())->assertOk()->json('data.0.fares.0.amount'));
        $this->assertEquals($expected, $this->getJson($this->seatsUrl())->assertOk()->json('data.seats.0.price'));
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        $this->assertEquals($expected, $quote->json('data.total'));
        $this->assertQuoteAddsUp($quote->json('data'));
    }

    public static function excludedFareAdjustments(): array
    {
        return [
            ['inactive discount', 2400], ['another terminal', 2400],
            ['expired terminal discount', 2250], ['future terminal discount', 2250],
            ['inactive surcharge', 2150], ['deleted surcharge', 2150],
            ['deleted discount', 2400], ['deleted terminal discount', 2250],
        ];
    }

    /** @dataProvider changedFareAdjustments */
    public function test_changed_discount_or_surcharge_rejects_old_quote(string $table, array $changes)
    {
        $this->seedSearchAdjustments();
        if ($table !== 'surcharges') {
            DB::table('schedules')->update(['surcharge_id' => null]);
        }
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        DB::table($table)->update($changes);
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertStatus(409)->assertJsonPath('message', 'Seat availability or fare changed. Request a new quote.');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('tickets', 0);
    }

    public static function changedFareAdjustments(): array
    {
        return [['discounts', ['percentage' => 20]], ['surcharges', ['flat' => 300]],
            ['terminal_discounts', ['discount' => 10]]];
    }

    public function test_payment_amount_uses_adjusted_fare_once()
    {
        $this->seedSearchAdjustments();
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $this->assertEquals(2600, $booking->json('data.total'));
        $this->assertDatabaseHas('mobile_payments', ['amount_minor' => 260000]);
        $this->get($booking->json('data.payment.checkout_url'))->assertOk()
            ->assertSee('name="pp_Amount" value="260000"', false);
    }

    public function test_wallet_is_deducted_after_discounts_surcharges_and_rounding()
    {
        $this->seedSearchAdjustments();
        config()->set('mobile.features.wallet', true);
        $loyalty = \Mockery::mock(\App\Services\Mobile\MobileLoyaltyService::class);
        $loyalty->shouldReceive('deductionFor')->once()->withArgs(function ($account, $points, $amount) {
            return $account->id === 99 && $points === 2 && $amount === 2600.0;
        })->andReturn(['points' => 2, 'amount' => 100.25]);
        $this->app->instance(\App\Services\Mobile\MobileLoyaltyService::class, $loyalty);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', array_merge($this->quoteInput(), ['points_to_use' => 2]))->assertOk();
        $this->assertEquals(100.25, $quote->json('data.wallet_deduction'));
        $this->assertEquals(2499.75, $quote->json('data.total'));
        $this->assertQuoteAddsUp($quote->json('data'));
    }

    public function test_discounts_changed_under_active_surcharge_do_not_change_erp_fare()
    {
        $this->seedSearchAdjustments();
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        DB::table('discounts')->update(['percentage' => 50]);
        DB::table('terminal_discounts')->update(['discount' => 20]);
        $created = $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertCreated();
        $this->assertEquals(2600, $created->json('data.total'));
    }

    public function test_mixed_class_discounts_without_surcharge_round_each_seat_before_summing()
    {
        $this->seedSearchAdjustments();
        DB::table('schedules')->update(['surcharge_id' => null]);
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput(['1', '3']))->assertOk();
        // 2500 - 375 + 25 = 2150; 4000 - 600 = 3400.
        foreach (['base_fare' => 6500, 'discount' => 975, 'surcharge' => 0,
            'rounding_adjustment' => 25, 'total' => 5550] as $field => $expected) {
            $this->assertEquals($expected, $quote->json('data.' . $field), $field);
        }
        $this->assertQuoteAddsUp($quote->json('data'));
    }

    public function test_inactive_fare_class_blocks_search_seats_quotes_and_existing_quote()
    {
        $quote = $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk();
        DB::table('fare_classes')->update(['is_active' => 0]);
        $message = 'This schedule contains an inactive or unavailable fare class.';
        $this->getJson($this->searchUrl())->assertStatus(422)->assertJsonPath('message', $message);
        $this->getJson($this->seatsUrl())->assertStatus(422)->assertJsonPath('message', $message);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertStatus(422)->assertJsonPath('message', $message);
        $this->postJson('/api/mobile/v1/bookings', [
            'quote_token' => $quote->json('data.quote_token'), 'payment_method' => 'counter',
        ])->assertStatus(422)->assertJsonPath('message', $message);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_fare_table_must_cover_all_company_classes_like_erp()
    {
        DB::table('fare_classes')->insert(['id' => 71, 'company_id' => 1, 'name' => 'Other class', 'is_active' => 1]);
        $this->getJson($this->searchUrl())->assertStatus(422);
        $this->getJson($this->seatsUrl())->assertStatus(422);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertStatus(422);
        DB::table('fare_tables')->insert(['company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 71, 'fare' => 4000]);
        // Other companies' classes and tariffs cannot affect this company.
        DB::table('fare_classes')->insert(['id' => 72, 'company_id' => 2, 'name' => 'Other company', 'is_active' => 1]);
        DB::table('fare_tables')->insert(['company_id' => 2, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 72, 'fare' => 1]);
        $this->getJson($this->searchUrl())->assertOk()->assertJsonPath('data.0.fares.0.amount', 2500);
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertOk()->assertJsonPath('data.total', 2500);
    }

    public function test_negative_erp_fare_is_rejected_instead_of_becoming_a_free_booking()
    {
        $this->seedSearchAdjustments();
        DB::table('schedules')->update(['surcharge_id' => null]);
        DB::table('discounts')->update(['type' => 'flat', 'flat' => 3000]);
        $this->getJson($this->searchUrl())->assertStatus(422)
            ->assertJsonPath('message', 'The fare configuration is invalid for this schedule.');
        $this->postJson('/api/mobile/v1/bookings/quote', $this->quoteInput())->assertStatus(422);
        $this->assertDatabaseCount('mobile_booking_quotes', 0);
        $this->assertDatabaseCount('tickets', 0);
    }


    private function assertQuoteAddsUp(array $quote): void
    {
        $sum = $quote['base_fare'] - $quote['discount'] + $quote['surcharge']
            + $quote['rounding_adjustment'] + $quote['taxes'] + $quote['fees'] - $quote['wallet_deduction'];
        $this->assertEqualsWithDelta($quote['total'], $sum, 0.001);
    }


    private function seedSearchAdjustments(): void
    {
        foreach (['discounts', 'surcharges'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('percentage')->nullable();
                $table->integer('flat')->nullable();
            });
        }
        DB::table('discounts')->insert(['id' => 80, 'company_id' => 1, 'is_active' => 1, 'type' => 'percentage', 'percentage' => 10]);
        DB::table('schedule_terminal_discounts')->insert(['company_id' => 1, 'discount_id' => 80, 'terminal_id' => 10]);
        DB::table('surcharges')->insert(['id' => 81, 'company_id' => 1, 'is_active' => 1, 'type' => 'flat', 'flat' => 100]);
        DB::table('terminal_discounts')->insert(['company_id' => 1, 'terminal_id' => 10, 'route_id' => 30,
            'discount' => 5, 'start_date' => $this->date, 'end_date' => $this->date]);
        DB::table('schedules')->update(['discount_id' => 80, 'surcharge_id' => 81]);
        DB::table('fare_classes')->insert(['id' => 71, 'company_id' => 1, 'name' => 'Premium', 'is_active' => 1]);
        DB::table('fare_tables')->insert(['company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 71, 'fare' => 4000]);
        $map = json_decode(DB::table('bus_classes')->value('seat_map'), true);
        $map[0][2]['class'] = 71;
        DB::table('bus_classes')->update(['seat_map' => json_encode($map)]);
        DB::table('users')->update(['check_booking_minutes' => 1]);
        DB::table('terminal_visibilities')->update(['booking_minutes' => 1440]);
        DB::table('terminal_time_differences')->insert(['company_id' => 1, 'terminal_id' => 10, 'route_id' => 30, 'time_difference' => 0]);
        $this->limitOnlineSeats(3);
    }

    private function seedJourney(): void
    {
        DB::table('cities')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Origin'], ['id' => 2, 'company_id' => 1, 'name' => 'Destination'],
        ]);
        DB::table('terminals')->insert(['id' => 10, 'company_id' => 1, 'name' => 'Mobile', 'is_online_terminal' => 1, 'advance_booking' => 10]);
        DB::table('users')->insert(['id' => 20, 'company_id' => 1, 'check_booking_minutes' => 0]);
        DB::table('routes')->insert(['id' => 30, 'company_id' => 1, 'name' => 'Test route', 'online_seats' => 3]);
        DB::table('routes_fares')->insert(['route_id' => 30, 'company_id' => 1, 'departure_city_id' => 1, 'destination_city_id' => 2]);
        DB::table('schedules')->insert(['id' => 40, 'company_id' => 1, 'route_id' => 30, 'bus_class_id' => 60]);
        DB::table('schedule_details')->insert(['id' => 50, 'company_id' => 1, 'schedule_id' => 40, 'bus_class_id' => 60,
            'departure_id' => 1, 'destination_id' => 2, 'departure_date' => $this->date, 'schedule_date' => $this->date, 'departure_time' => '12:00:00']);
        DB::table('schedule_terminal_visibilities')->insert(['company_id' => 1, 'terminal_id' => 10, 'schedule_id' => 40, 'visibility' => 1]);
        DB::table('terminal_visibilities')->insert(['company_id' => 1, 'route_id' => 30, 'departure_city_id' => 1, 'destination_city_id' => 2, 'online_visibilty' => 0]);
        DB::table('bus_classes')->insert(['id' => 60, 'company_id' => 1, 'name' => 'Standard', 'seat_map' => json_encode([array_map(function ($seat) {
            return ['seatNo' => $seat, 'reserved' => true, 'type' => 0, 'class' => 70];
        }, ['1', '2', '3'])])]);
        DB::table('fare_classes')->insert(['id' => 70, 'company_id' => 1, 'name' => 'Standard', 'is_active' => 1]);
        DB::table('fare_tables')->insert(['company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 70, 'fare' => 2500]);
    }

    private function createSchema(): void
    {
        // Only columns needed by the real scheduling and advance-booking paths.
        $tables = [
            'cities' => ['', 'name'],
            'terminals' => ['is_online_terminal advance_booking reservation_cancel', 'name available_seats'],
            'users' => ['check_booking_minutes', ''],
            'routes' => ['online_seats', 'name online_seat_choices'],
            'routes_fares' => ['route_id departure_city_id destination_city_id', ''],
            'route_online_terminals' => ['route_id terminal_id', ''],
            'schedules' => ['route_id bus_class_id discount_id surcharge_id', ''],
            'schedule_details' => ['schedule_id bus_class_id departure_id destination_id', 'departure_date departure_time schedule_date'],
            'schedule_terminal_visibilities' => ['schedule_id terminal_id visibility', ''],
            'terminal_visibilities' => ['route_id departure_city_id destination_city_id online_visibilty booking_minutes', ''],
            'terminal_time_differences' => ['route_id terminal_id time_difference', ''],
            'limited_seats' => ['route_id departure_city_id destination_city_id limited_seat', ''],
            'drop_schedules' => ['schedule_id', 'schedule_date'],
            'bus_classes' => ['', 'name seat_map'],
            'fare_classes' => ['is_active', 'name'],
            'fare_tables' => ['from_city_id to_city_id fare_class fare', ''],
            'discounts' => ['is_active', 'type'],
            'schedule_terminal_discounts' => ['discount_id terminal_id', ''],
            'surcharges' => ['is_active', 'type'],
            'terminal_discounts' => ['terminal_id route_id discount', 'start_date end_date'],
            'invoices' => ['schedule_id route_id terminal_id passenger_account_id added_by', 'schedule_date schedule_time'],
            'customers' => ['added_by updated_by', 'name cnic contact'],
            'tickets' => ['schedule_id route_id schedule_details_id terminal_id departure_city_id destination_city_id bus_class_id seat_fare is_partial booking_no invoice_id customer_id online_terminal added_by updated_by discount schedule_discount terminal_discount points_usage gender',
                'seat_no schedule_date schedule_time schedule_time_exact date terminal_name type booked_time'],
            'ticket_advanced_bookeds' => ['departure_city_id destination_city_id ticket_id seat_fare booking_no customer_id schedule_id added_by gender', 'seat_no date type'],
            'ticket_is_partials' => ['departure_city_id destination_city_id ticket_id seat_fare booking_no customer_id schedule_id added_by gender', 'seat_no date type'],
            'activity_logs' => ['activity_by', 'message requested_host'],
        ];
        foreach ($tables as $name => [$integers, $strings]) {
            Schema::create($name, function (Blueprint $table) use ($integers, $strings) {
                $table->id();
                $table->integer('company_id')->nullable();
                $table->integer('hide')->default(0);
                foreach (array_filter(explode(' ', $integers)) as $column) { $table->integer($column)->nullable(); }
                foreach (array_filter(explode(' ', $strings)) as $column) { $table->text($column)->nullable(); }
                $table->softDeletes();
                $table->timestamps();
            });
        }
        // SQLite otherwise accepts "male" in INTEGER columns; model strict MySQL inserts.
        foreach (['tickets', 'ticket_advanced_bookeds', 'ticket_is_partials'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_integer_gender BEFORE INSERT ON {$table}
                WHEN NEW.gender IS NOT NULL AND typeof(NEW.gender) != 'integer'
                BEGIN SELECT RAISE(ABORT, 'Ticket gender must be an integer'); END");
        }
        require_once base_path('database/migrations/2026_07_18_000003_create_mobile_booking_quotes_table.php');
        (new \CreateMobileBookingQuotesTable())->up();
    }
}
