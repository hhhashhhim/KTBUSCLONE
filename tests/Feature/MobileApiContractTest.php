<?php

namespace Tests\Feature;

use App\Models\MobileBookingQuote;
use App\Models\PassengerAccount;
use App\Services\Mobile\MobileBookingService;
use App\Services\Mobile\MobileTravelService;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MobileApiContractTest extends TestCase
{
    public function test_public_app_configuration_uses_safe_response_contract()
    {
        config()->set('mobile.company_id', null);
        config()->set('mobile.latest_version', '1.2.0');
        config()->set('mobile.minimum_supported_version', '1.0.0');

        $response = $this->getJson('/api/mobile/v1/app/config');

        $response->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'latest_version' => '1.2.0',
                'minimum_supported_version' => '1.0.0',
                'features' => [
                    'wallet' => true,
                    'online_payments' => false,
                    'notifications' => false,
                    'promotions' => false,
                ],
            ],
            'errors' => null,
        ]);
    }

    public function test_registration_validation_uses_mobile_error_contract()
    {
        $response = $this->postJson('/api/mobile/v1/auth/register', []);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'The request is invalid.',
            'data' => null,
        ])->assertJsonStructure([
            'errors' => ['full_name', 'mobile', 'cnic', 'password'],
        ]);
    }

    public function test_personal_routes_require_a_passenger_token()
    {
        $this->getJson('/api/mobile/v1/profile')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/bookings')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/cities')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/saved-passengers')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/fleet')->assertUnauthorized();
        $this->getJson('/api/mobile/v1/wallet')->assertUnauthorized();
    }

    public function test_saved_passenger_validation_uses_mobile_error_contract()
    {
        $this->authenticatePassenger();

        $this->postJson('/api/mobile/v1/saved-passengers', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The request is invalid.',
                'data' => null,
            ])
            ->assertJsonStructure([
                'errors' => ['full_name', 'cnic', 'mobile', 'gender'],
            ]);
    }

    public function test_password_reset_validation_uses_mobile_error_contract()
    {
        $this->postJson('/api/mobile/v1/auth/reset-password', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The request is invalid.',
                'data' => null,
            ])
            ->assertJsonStructure([
                'errors' => ['mobile', 'reset_token', 'password'],
            ]);
    }

    public function test_authenticated_schedule_search_returns_dynamic_service_data()
    {
        $this->authenticatePassenger();
        $date = now()->addDay()->toDateString();
        $travel = Mockery::mock(MobileTravelService::class);
        $travel->shouldReceive('schedules')->once()->with(1, 2, $date)->andReturn(collect([
            [
                'id' => 9,
                'schedule_detail_id' => 19,
                'route_id' => 29,
                'origin' => ['id' => 1, 'name' => 'Lahore'],
                'destination' => ['id' => 2, 'name' => 'Islamabad'],
                'departure_at' => $date . 'T08:00:00',
                'arrival_at' => null,
                'bus_class' => ['id' => 3, 'name' => 'Business'],
                'available_seats' => 12,
                'total_seats' => 36,
                'fares' => [['class_id' => 3, 'amount' => 2500]],
            ],
        ]));
        $this->app->instance(MobileTravelService::class, $travel);

        $this->getJson("/api/mobile/v1/schedules?origin_id=1&destination_id=2&date={$date}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [[
                    'schedule_detail_id' => 19,
                    'available_seats' => 12,
                ]],
            ]);
    }

    public function test_authenticated_seat_availability_preserves_backend_statuses()
    {
        $this->authenticatePassenger();
        $date = now()->addDay()->toDateString();
        $travel = Mockery::mock(MobileTravelService::class);
        $travel->shouldReceive('seatLayout')->once()->with(19, 1, 2, $date)->andReturn([
            'schedule_id' => 9,
            'schedule_detail_id' => 19,
            'maximum_selectable_seats' => 5,
            'seats' => [
                ['number' => '1', 'row' => 0, 'column' => 0, 'status' => 'available', 'price' => 2500],
                ['number' => '2', 'row' => 0, 'column' => 1, 'status' => 'booked', 'price' => 2500],
            ],
        ]);
        $this->app->instance(MobileTravelService::class, $travel);

        $this->getJson("/api/mobile/v1/schedules/19/seats?origin_id=1&destination_id=2&date={$date}")
            ->assertOk()
            ->assertJsonPath('data.maximum_selectable_seats', 5)
            ->assertJsonPath('data.seats.1.status', 'booked');
    }

    public function test_fare_quote_uses_server_totals_and_payment_methods()
    {
        $this->authenticatePassenger();
        $date = now()->addDay()->toDateString();
        $quote = new MobileBookingQuote([
            'token' => str_repeat('q', 64),
            'expires_at' => now()->addMinutes(10),
            'base_fare' => 2700,
            'discount' => 200,
            'taxes' => 0,
            'fees' => 0,
            'total' => 2500,
        ]);
        $bookings = Mockery::mock(MobileBookingService::class);
        $bookings->shouldReceive('quote')->once()->andReturn($quote);
        $bookings->shouldReceive('paymentMethods')->once()->andReturn(['counter']);
        $this->app->instance(MobileBookingService::class, $bookings);

        $this->postJson('/api/mobile/v1/bookings/quote', [
            'origin_id' => 1,
            'destination_id' => 2,
            'date' => $date,
            'schedule_detail_id' => 19,
            'seats' => ['1'],
            'passengers' => [[
                'seat_number' => '1',
                'full_name' => 'Test Passenger',
                'cnic' => '3520212345671',
                'mobile' => '03001234567',
                'gender' => 'male',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.base_fare', 2700)
            ->assertJsonPath('data.discount', 200)
            ->assertJsonPath('data.total', 2500)
            ->assertJsonPath('data.payment_methods.0', 'counter');
    }

    public function test_booking_creation_rejects_missing_quote_and_payment_method()
    {
        $this->authenticatePassenger();

        $this->postJson('/api/mobile/v1/bookings', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quote_token', 'payment_method']]);
    }

    public function test_passenger_can_retrieve_an_owned_ticket_contract()
    {
        $this->authenticatePassenger();
        $bookings = Mockery::mock(MobileBookingService::class);
        $bookings->shouldReceive('findFor')->once()->withArgs(function ($account, $invoiceId) {
            return $account instanceof PassengerAccount && $invoiceId === 42;
        })->andReturn([
            'id' => 42,
            'reference' => 'KT-00000042',
            'status' => 'pending',
            'payment_status' => 'pending',
            'origin_name' => 'Lahore',
            'destination_name' => 'Islamabad',
            'departure_at' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'seats' => ['1'],
            'total' => 2500,
            'qr_value' => 'KAINAT:42',
            'passengers' => [],
        ]);
        $this->app->instance(MobileBookingService::class, $bookings);

        $this->getJson('/api/mobile/v1/bookings/42')
            ->assertOk()
            ->assertJsonPath('data.reference', 'KT-00000042')
            ->assertJsonPath('data.qr_value', 'KAINAT:42');
    }

    private function authenticatePassenger(): PassengerAccount
    {
        $passenger = new PassengerAccount([
            'id' => 99,
            'company_id' => 1,
            'mobile' => '03001234567',
            'mobile_verified_at' => now(),
        ]);
        Sanctum::actingAs($passenger);

        return $passenger;
    }
}
