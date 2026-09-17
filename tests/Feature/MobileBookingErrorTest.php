<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileBookingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class MobileBookingErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mobile.company_id', 1);
        config()->set('cache.default', 'array');
        Sanctum::actingAs(new PassengerAccount([
            'id' => 99,
            'company_id' => 1,
            'mobile' => '03001234567',
            'mobile_verified_at' => now(),
        ]));
    }

    /** @dataProvider bookingActions */
    public function test_database_failures_are_logged_and_hidden_on_every_booking_action(string $action, bool $debug)
    {
        config()->set('app.debug', $debug);
        // A driver code that resembles HTTP 422 must still be treated as an internal error.
        $exception = new QueryException(
            'insert into mobile_booking_quotes (payload, token) values (?, ?)',
            ['{"full_name":"Private Passenger","cnic":"3520212345671"}', 'private-quote-token'],
            new \PDOException('SQLSTATE[42S02]: Table not found', 422)
        );
        $this->failService($action, $exception);
        $this->expectLogged($exception);

        $this->bookingRequest($action)->assertStatus(500)->assertExactJson([
            'success' => false,
            'message' => 'Something went wrong. Please try again.',
            'data' => null,
            'errors' => [],
        ])->assertSee('"errors":{}', false);
    }

    public static function bookingActions(): array
    {
        $cases = [];
        foreach (['quote', 'create', 'listFor', 'findFor'] as $action) {
            foreach ([false, true] as $debug) {
                $cases[$action . ($debug ? ' debug' : ' production')] = [$action, $debug];
            }
        }
        return $cases;
    }

    /** @dataProvider internalFailures */
    public function test_unexpected_failures_are_logged_and_hidden(Throwable $exception, int $status)
    {
        config()->set('app.debug', true);
        $this->failService('quote', $exception);
        $this->expectLogged($exception);

        $this->bookingRequest('quote')->assertStatus($status)->assertExactJson([
            'success' => false,
            'message' => 'Something went wrong. Please try again.',
            'data' => null,
            'errors' => [],
        ]);
    }

    public static function internalFailures(): array
    {
        return [
            'missing table' => [new QueryException('select * from mobile_booking_quotes', [], new \PDOException('SQLSTATE[42S02]: Table not found')), 500],
            'runtime failure' => [new RuntimeException('Internal configuration secret'), 500],
            'service unavailable' => [new RuntimeException('Internal service configuration', 503), 503],
            'programming error' => [new \TypeError('Internal implementation details'), 500],
            'wrapped internal error' => [new RuntimeException('Internal database details', 422, new \PDOException('SQL details')), 500],
        ];
    }

    /** @dataProvider businessFailures */
    public function test_expected_business_errors_keep_their_status_and_message(int $status, string $message)
    {
        $this->failService('quote', new RuntimeException($message, $status));
        Log::shouldReceive('error')->never();

        $this->bookingRequest('quote')->assertStatus($status)->assertExactJson([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ]);
    }

    public static function businessFailures(): array
    {
        return [
            [403, 'Verify your mobile number before booking.'],
            [404, 'The fare quote was not found.'],
            [409, 'One or more selected seats are no longer available.'],
            [422, 'A passenger is required for every selected seat.'],
        ];
    }

    private function failService(string $action, Throwable $exception): void
    {
        $bookings = Mockery::mock(MobileBookingService::class);
        $bookings->shouldReceive($action)->once()->andThrow($exception);
        $this->app->instance(MobileBookingService::class, $bookings);
    }

    private function expectLogged(Throwable $exception): void
    {
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) use ($exception) {
            return $message === $exception->getMessage()
                && ($context['exception'] ?? null) === $exception;
        });
    }

    private function bookingRequest(string $action)
    {
        if ($action === 'listFor') {
            return $this->getJson('/api/mobile/v1/bookings');
        }
        if ($action === 'findFor') {
            return $this->getJson('/api/mobile/v1/bookings/42');
        }
        if ($action === 'create') {
            return $this->postJson('/api/mobile/v1/bookings', [
                'quote_token' => str_repeat('q', 64),
                'payment_method' => 'counter',
            ]);
        }
        return $this->postJson('/api/mobile/v1/bookings/quote', [
            'origin_id' => 1,
            'destination_id' => 2,
            'date' => now()->addDay()->toDateString(),
            'schedule_detail_id' => 19,
            'seats' => ['1'],
            'passengers' => [[
                'seat_number' => '1',
                'full_name' => 'Test Passenger',
                'cnic' => '3520212345671',
                'mobile' => '03001234567',
                'gender' => 'male',
            ]],
        ]);
    }
}
