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
        $this->assertDatabaseHas('tickets', ['type' => 'pending booking', 'transaction_id' => null]);
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
        $this->assertDatabaseHas('tickets', ['type' => 'pending booking']);
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
            ->assertSee('name="pp_BankID" value="TBANK"', false)
            ->assertSee('name="pp_ProductID" value="RETL"', false);
        $this->get($url)->assertStatus(409);
        $this->assertDatabaseHas('tickets', ['type' => 'pending booking']);
    }

    public function test_mobile_payment_return_does_not_trust_forged_success_fields()
    {
        $booking = $this->postJson('/api/mobile/v1/bookings', $this->paymentInput())->assertCreated();
        $payment = \App\Models\MobilePayment::first();
        $payment->update(['started_at' => now()]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['pp_ResponseCode' => '000', 'pp_Status' => 'Pending'])]);
        $this->post('/api/mobile/v1/payments/' . $payment->public_id . '/return', ['pp_ResponseCode' => '000', 'pp_Status' => 'Completed', 'pp_Amount' => 250000])->assertOk();
        $this->assertDatabaseHas('mobile_payments', ['status' => 'pending']);
        $this->assertDatabaseHas('tickets', ['type' => 'pending booking']);
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
        $this->assertDatabaseHas('tickets', ['type' => 'pending booking', 'deleted_at' => null]);
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
        // 10% schedule discount + 5% terminal discount + 100 flat surcharge, ERP-rounded.
        $this->assertSame([2250.0, 3500.0], array_column($rows[50]['fares'], 'amount'));
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
        DB::table('fare_classes')->insert(['id' => 71, 'company_id' => 1, 'name' => 'Premium']);
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
        DB::table('fare_classes')->insert(['id' => 70, 'company_id' => 1, 'name' => 'Standard']);
        DB::table('fare_tables')->insert(['company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 70, 'fare' => 2500]);
    }

    private function createSchema(): void
    {
        // Only columns needed by the real scheduling and advance-booking paths.
        $tables = [
            'cities' => ['', 'name'],
            'terminals' => ['is_online_terminal advance_booking', 'name available_seats'],
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
            'fare_classes' => ['', 'name'],
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
