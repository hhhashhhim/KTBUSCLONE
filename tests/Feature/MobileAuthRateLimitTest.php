<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileOtpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MobileAuthRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        Cache::flush();
        config()->set('database.connections.mobile_limits_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        config()->set('database.default', 'mobile_limits_testing');
        config()->set('mobile.company_id', 1);
        Schema::create('passenger_accounts', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->string('mobile');
        });
        $otp = Mockery::mock(MobileOtpService::class);
        $otp->shouldReceive('issue')->andReturnNull();
        $otp->shouldReceive('ensureConfigured')->andReturnNull();
        $this->app->instance(MobileOtpService::class, $otp);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('mobile_limits_testing');
        parent::tearDown();
    }

    public function test_login_budget_follows_normalized_account_across_ips_and_recovers_after_expiry()
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.' . $attempt])
                ->postJson('/api/mobile/v1/auth/login', ['mobile' => '0300 1234567'])
                ->assertStatus(422);
        }
        $blocked = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.100'])
            ->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertJsonPath('success', false)->assertJsonPath('data', null)
            ->assertJsonStructure(['message', 'errors']);
        $this->assertGreaterThan(0, (int) $blocked->headers->get('Retry-After'));
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03007654321'])->assertStatus(422);
        Carbon::setTestNow(now()->addSeconds(61));
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])->assertStatus(422);
    }

    public function test_ip_limit_stops_rotation_through_different_mobile_numbers()
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '0300123' . sprintf('%04d', $attempt)])
                ->assertStatus(422);
        }
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03009999999'])->assertStatus(429);
    }

    public function test_rotating_bearer_accounts_cannot_bypass_the_target_login_budget()
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Sanctum::actingAs(new PassengerAccount([
                'id' => $attempt, 'company_id' => 1, 'mobile' => '0300111111' . $attempt,
            ]));
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.' . $attempt])
                ->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])
                ->assertStatus(422);
        }
        Sanctum::actingAs(new PassengerAccount(['id' => 99, 'company_id' => 1, 'mobile' => '03009999999']));
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])->assertStatus(429);
    }

    public function test_registration_reset_and_authenticated_resend_share_delivery_budget()
    {
        $mobile = '03001234567';
        $this->postJson('/api/mobile/v1/auth/register', ['mobile' => $mobile])->assertStatus(422);
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $mobile])->assertStatus(202);
        // A validation failure still counts; no WhatsApp request is required.
        Sanctum::actingAs(new PassengerAccount(['id' => 1, 'company_id' => 1, 'mobile' => $mobile]));
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertStatus(429);
    }

    public function test_reset_and_signup_verification_share_attempts_and_resend_does_not_reset_them()
    {
        $mobile = '03001234567';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/mobile/v1/auth/verify-reset-otp', ['mobile' => $mobile, 'code' => 'invalid'])
                ->assertStatus(422);
        }
        Sanctum::actingAs(new PassengerAccount(['id' => 1, 'company_id' => 1, 'mobile' => $mobile]));
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/mobile/v1/auth/verify-otp', ['code' => 'invalid'])->assertStatus(422);
        }
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        $this->postJson('/api/mobile/v1/auth/verify-otp', ['code' => '123456'])->assertStatus(429);
        Carbon::setTestNow(now()->addMinutes(10)->addSecond());
        $this->postJson('/api/mobile/v1/auth/verify-otp', ['code' => 'invalid'])->assertStatus(422);
    }

    public function test_otp_hourly_budget_survives_minute_window_expiry()
    {
        Sanctum::actingAs(new PassengerAccount(['id' => 1, 'company_id' => 1, 'mobile' => '03001234567']));
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
            Carbon::setTestNow(now()->addSeconds(61));
        }
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertStatus(429);
    }

    public function test_final_password_reset_is_limited_per_account()
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/mobile/v1/auth/reset-password', ['mobile' => '03001234567'])->assertStatus(422);
        }
        $this->postJson('/api/mobile/v1/auth/reset-password', ['mobile' => '03001234567'])->assertStatus(429);
    }

    public function test_erp_login_budget_applies_immediately_and_monthly_otp_limit_survives_daily_expiry()
    {
        require_once database_path('migrations/2026_10_20_000005_create_mobile_passenger_control_policies.php');
        (new \CreateMobilePassengerControlPolicies())->up();
        DB::table('mobile_app_policies')->insert(['company_id' => 1, 'settings' => json_encode(['login_per_minute' => 1, 'otp_per_day' => 1, 'otp_per_month' => 2])]);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])->assertStatus(422);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => '03001234567'])->assertStatus(429);
        Sanctum::actingAs(new PassengerAccount(['id' => 1, 'company_id' => 1, 'mobile' => '03001234567']));
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        Carbon::setTestNow(now()->addDay()->addSecond());
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        Carbon::setTestNow(now()->addDay()->addSecond());
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertStatus(429)->assertHeader('Retry-After');
        Carbon::setTestNow(now()->addDays(30));
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
    }
}
