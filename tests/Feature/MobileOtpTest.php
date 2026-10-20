<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileOtpService;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MobileOtpTest extends TestCase
{
    private const URL = 'https://wa.sarzone.com/api/v1/whatsapp/send-template';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.mobile_otp_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        config()->set('database.default', 'mobile_otp_testing');
        config()->set('cache.default', 'array');
        config()->set('mobile.company_id', 1);
        config()->set('mobile.otp.driver', 'whatsapp');
        config()->set('mobile.otp.whatsapp.api_key', 'test-only-key');
        config()->set('mobile.otp.ttl_minutes', 10);
        $travel = Mockery::mock(MobileTravelService::class);
        $travel->shouldReceive('companyId')->andReturn(1);
        $this->app->instance(MobileTravelService::class, $travel);
        $this->fakeHttp(['*' => Http::response(['success' => true], 200)]);
        Log::spy();
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->string('name');
            $table->string('contact');
            $table->string('cnic');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('passenger_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('customer_id')->nullable();
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('password');
            $table->timestamp('mobile_verified_at')->nullable();
            foreach (['otp', 'password_reset_otp', 'password_reset_token'] as $prefix) {
                $table->string($prefix . '_digest')->nullable();
                $table->timestamp($prefix . '_expires_at')->nullable();
            }
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('mobile_otp_testing');
        parent::tearDown();
    }

    public function test_signup_delivers_template_then_verifies_and_consumes_code()
    {
        $response = $this->postJson('/api/mobile/v1/auth/register', $this->signup())
            ->assertCreated()->assertJsonPath('data.verification_pending', true)
            ->assertJsonPath('data.verification_delivery_sent', true)
            ->assertJsonPath('data.verification_message', null)
            ->assertJsonPath('data.passenger.mobile_verified', false);
        $account = PassengerAccount::firstOrFail();
        $code = $this->sentCode();
        $this->assertTrue(Hash::check($code, $account->otp_digest));
        $this->assertStringNotContainsString($code, $response->getContent());
        $this->assertNull($account->password_reset_otp_digest);
        Http::assertSent(function ($request) use ($code) {
            return $request->url() === self::URL && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-only-key')
                && $request['to'] === '923001234567'
                && $request['client_code'] === 'kainat-travels'
                && $request['template_name'] === 'otp'
                && $request['language_code'] === 'en'
                && preg_match('/^[0-9]{6}$/', $code)
                && strpos($request['reference_id'], 'KAINAT_MOBILE_signup_') === 0;
        });
        Sanctum::actingAs($account);
        $this->postJson('/api/mobile/v1/auth/verify-otp', ['code' => $code])->assertOk();
        $this->assertNotNull($account->fresh()->mobile_verified_at);
        $this->assertNull($account->fresh()->otp_digest);
        $this->postJson('/api/mobile/v1/auth/verify-otp', ['code' => $code])->assertStatus(422);
        Log::shouldNotHaveReceived('info');
    }

    public function test_signup_delivery_failure_keeps_account_and_allows_resend()
    {
        $this->fakeHttp(['*' => Http::response(['success' => false, 'message' => 'private-provider-details'], 200)]);
        $this->postJson('/api/mobile/v1/auth/register', $this->signup())
            ->assertCreated()->assertJsonPath('data.verification_delivery_sent', false)
            ->assertJsonPath('data.verification_message', 'Could not send the WhatsApp code. Please try again shortly.')
            ->assertJsonStructure(['data' => ['token', 'passenger']]);
        $account = PassengerAccount::firstOrFail();
        $this->assertNull($account->mobile_verified_at);
        Sanctum::actingAs($account);
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->fakeHttp(['*' => Http::response(['success' => true], 200)]);
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        $this->assertTrue(Hash::check($this->sentCode(), $account->fresh()->otp_digest));
    }

    public function test_password_reset_whatsapp_code_is_separate_and_revokes_sessions()
    {
        $account = $this->account();
        $account->createToken('existing-session');
        $account->forceFill(['otp_digest' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)])->save();
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])
            ->assertStatus(202);
        $code = $this->sentCode();
        $this->assertTrue(Hash::check($code, $account->fresh()->password_reset_otp_digest));
        $this->assertTrue(Hash::check('123456', $account->fresh()->otp_digest));
        $response = $this->postJson('/api/mobile/v1/auth/verify-reset-otp', ['mobile' => $account->mobile, 'code' => $code])
            ->assertOk();
        $input = ['mobile' => $account->mobile, 'reset_token' => $response->json('data.reset_token'),
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->postJson('/api/mobile/v1/auth/reset-password', $input)->assertOk();
        $this->assertTrue(Hash::check('new-password-123', $account->fresh()->password));
        $this->assertSame(0, $account->tokens()->count());
        $this->postJson('/api/mobile/v1/auth/reset-password', $input)->assertStatus(422);
        $this->postJson('/api/mobile/v1/auth/verify-reset-otp', ['mobile' => $account->mobile, 'code' => $code])->assertStatus(422);
        Log::shouldNotHaveReceived('info');
    }

    public function test_unknown_or_other_company_reset_does_not_send_a_message()
    {
        $account = $this->account();
        $account->forceFill(['company_id' => 2])->save();
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertStatus(202);
        Http::assertNothingSent();
    }

    public function test_disabled_and_missing_credentials_fail_without_sending()
    {
        foreach (['disabled', 'whatsapp'] as $driver) {
            config()->set('mobile.otp.driver', $driver);
            config()->set('mobile.otp.whatsapp.api_key', '');
            $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => '03001234567'])
                ->assertStatus(503)->assertJsonPath('success', false);
        }
        Http::assertNothingSent();
    }

    /** @dataProvider deliveryFailures */
    public function test_provider_failure_returns_safe_mobile_error($status, $body)
    {
        $this->fakeHttp(['*' => Http::response($body, $status)]);
        Sanctum::actingAs($this->account());
        $this->postJson('/api/mobile/v1/auth/resend-otp')
            ->assertStatus(503)->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Could not send the WhatsApp code. Please try again shortly.');
    }

    public function deliveryFailures(): array
    {
        return [[401, ['success' => false]], [500, ['success' => false]],
            [200, ['success' => false]], [200, []], [200, '<html>unavailable</html>']];
    }

    public function test_timeout_is_handled_without_exposing_exception_details()
    {
        $this->fakeHttp(function () { throw new ConnectionException('private-provider-details'); });
        $account = $this->account();
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])
            ->assertStatus(503)->assertJsonPath('message', 'Could not send the WhatsApp code. Please try again shortly.');
    }

    /** @dataProvider mobileNumbers */
    public function test_whatsapp_number_normalization($mobile, $valid)
    {
        $account = $this->account($mobile);
        Sanctum::actingAs($account);
        $this->postJson('/api/mobile/v1/auth/resend-otp')->assertStatus($valid ? 200 : 422);
        if ($valid) {
            Http::assertSent(function ($request) { return $request['to'] === '923001234567'; });
        } else {
            Http::assertNothingSent();
        }
    }

    public function mobileNumbers(): array
    {
        return [['03001234567', true], ['3001234567', true], ['+92 300 1234567', true],
            ['923001234567', true], ['123456789012345', false], ['0300123456', false]];
    }

    public function test_expired_and_wrong_codes_are_rejected()
    {
        $account = $this->account();
        $account->forceFill(['otp_digest' => Hash::make('123456'), 'otp_expires_at' => now()->subSecond()])->save();
        $service = app(MobileOtpService::class);
        $this->assertFalse($service->verify($account, '123456'));
        $account->otp_expires_at = now()->addMinute();
        $this->assertFalse($service->verify($account, '654321'));
        $this->assertNull($account->mobile_verified_at);
    }

    public function test_log_driver_remains_local_only()
    {
        config()->set('mobile.otp.driver', 'log');
        app(MobileOtpService::class)->issue($this->account());
        Http::assertNothingSent();
        Log::shouldHaveReceived('info')->once();
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        app(MobileOtpService::class)->ensureConfigured();
    }

    private function fakeHttp($callback): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake($callback);
    }

    private function account(string $mobile = '03001234567'): PassengerAccount
    {
        return PassengerAccount::create(['company_id' => 1, 'mobile' => $mobile, 'password' => Hash::make('password-123')]);
    }

    private function signup(): array
    {
        return ['full_name' => 'Test Passenger', 'mobile' => '03001234567', 'cnic' => '3520212345671',
            'password' => 'password-123', 'password_confirmation' => 'password-123'];
    }

    private function sentCode(): string
    {
        return Http::recorded()->last()[0]['variables']['code'];
    }

    private function installPassengerControls(): void
    {
        require_once database_path('migrations/2026_10_20_000005_create_mobile_passenger_control_policies.php');
        (new \CreateMobilePassengerControlPolicies())->up();
    }

    private function restrictAccount(PassengerAccount $account, array $values): void
    {
        DB::table('mobile_app_passenger_controls')->updateOrInsert(['company_id' => $account->company_id, 'passenger_account_id' => $account->id], $values);
    }

    public function test_erp_block_applies_to_login_existing_sessions_and_password_recovery(): void
    {
        $this->installPassengerControls(); $account = $this->account();
        $this->restrictAccount($account, ['status' => 'blocked']);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => $account->mobile, 'password' => 'password-123'])->assertForbidden();
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertForbidden();
        Sanctum::actingAs($account);
        $this->getJson('/api/mobile/v1/profile')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_blacklist_prevents_reregistration_with_phone_variants_or_cnic_but_is_company_scoped(): void
    {
        $this->installPassengerControls();
        foreach (['mobile' => '03001234567', 'cnic' => '3520212345671'] as $kind => $value) {
            DB::table('mobile_app_blacklist_entries')->insert(['company_id' => 1, 'passenger_account_id' => 99, 'kind' => $kind,
                'identity_hash' => \App\Services\Mobile\MobilePassengerControls::identity($kind, $value), 'created_at' => now()]);
        }
        foreach (['03001234567', '923001234567', '00923001234567', '3001234567'] as $mobile) {
            \Illuminate\Support\Facades\Cache::flush();
            $this->postJson('/api/mobile/v1/auth/register', array_replace($this->signup(), ['mobile' => $mobile]))->assertForbidden();
        }
        $this->postJson('/api/mobile/v1/auth/register', array_replace($this->signup(), ['mobile' => '03007654321']))->assertForbidden();
        $this->assertDatabaseCount('passenger_accounts', 0); Http::assertNothingSent();
        DB::table('mobile_app_blacklist_entries')->update(['company_id' => 2]);
        \Illuminate\Support\Facades\Cache::flush();
        $this->postJson('/api/mobile/v1/auth/register', $this->signup())->assertCreated();
    }

    public function test_identity_blacklist_restricts_an_existing_alternate_account(): void
    {
        $this->installPassengerControls(); $account = $this->account('03007654321');
        DB::table('customers')->insert(['id' => 9, 'company_id' => 1, 'name' => 'Fixture', 'contact' => $account->mobile, 'cnic' => '35202-1234567-1']);
        $account->update(['customer_id' => 9]);
        DB::table('mobile_app_blacklist_entries')->insert(['company_id' => 1, 'passenger_account_id' => 99, 'kind' => 'cnic',
            'identity_hash' => \App\Services\Mobile\MobilePassengerControls::identity('cnic', '3520212345671'), 'created_at' => now()]);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => $account->mobile, 'password' => 'password-123'])->assertForbidden();
    }

    public function test_required_password_reset_allows_verified_recovery_and_rejects_old_reset_state(): void
    {
        $this->installPassengerControls(); $account = $this->account();
        $this->restrictAccount($account, ['password_reset_required' => true]);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => $account->mobile, 'password' => 'password-123'])->assertForbidden()
            ->assertJsonPath('message', 'A password reset is required. Use Forgot password to verify your mobile number and set a new password.');
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertStatus(202);
        $token = $this->postJson('/api/mobile/v1/auth/verify-reset-otp', ['mobile' => $account->mobile, 'code' => $this->sentCode()])->assertOk()->json('data.reset_token');
        $stale = $account->fresh();
        // A later ERP action invalidates the token while a passenger request still has the old model.
        DB::table('passenger_accounts')->where('id', $account->id)->update(['password_reset_token_digest' => null, 'password_reset_token_expires_at' => null]);
        $this->assertFalse(app(MobileOtpService::class)->resetPassword($stale, $token, 'unsafe-new-password'));
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['passenger_account_id' => $account->id, 'password_reset_required' => 1]);
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertStatus(202);
        $token = $this->postJson('/api/mobile/v1/auth/verify-reset-otp', ['mobile' => $account->mobile, 'code' => $this->sentCode()])->assertOk()->json('data.reset_token');
        $this->postJson('/api/mobile/v1/auth/reset-password', ['mobile' => $account->mobile, 'reset_token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertOk();
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['passenger_account_id' => $account->id, 'password_reset_required' => 0]);
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => $account->mobile, 'password' => 'new-password-123'])->assertOk();
    }

    public function test_app_controls_pause_registration_login_and_otp_without_sending(): void
    {
        $this->installPassengerControls(); $account = $this->account();
        DB::table('mobile_app_policies')->insert(['company_id' => 1, 'settings' => json_encode(['registrations_enabled' => false, 'logins_enabled' => false, 'otp_enabled' => false])]);
        $this->postJson('/api/mobile/v1/auth/register', array_replace($this->signup(), ['mobile' => '03007654321']))->assertForbidden();
        $this->postJson('/api/mobile/v1/auth/login', ['mobile' => $account->mobile, 'password' => 'password-123'])->assertForbidden();
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertForbidden();
        $this->assertDatabaseCount('passenger_accounts', 1); Http::assertNothingSent();
    }

    public function test_personal_otp_daily_budget_is_shared_across_endpoints_ips_and_phone_formats(): void
    {
        $this->installPassengerControls(); $account = $this->account();
        $this->restrictAccount($account, ['otp_daily_limit' => 2]);
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertStatus(202);
        Sanctum::actingAs($account);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.77'])->postJson('/api/mobile/v1/auth/resend-otp')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.78'])->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => '923001234567'])->assertStatus(429)->assertHeader('Retry-After');
        Http::assertSentCount(2);
    }

    public function test_zero_otp_limit_and_personal_switch_stop_delivery(): void
    {
        $this->installPassengerControls(); $account = $this->account();
        $this->restrictAccount($account, ['otp_daily_limit' => 0]);
        $response = $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile]);
        $this->assertContains($response->status(), [403, 429]);
        $this->restrictAccount($account, ['otp_daily_limit' => null, 'otp_enabled' => false]);
        $this->postJson('/api/mobile/v1/auth/forgot-password', ['mobile' => $account->mobile])->assertForbidden();
        Http::assertNothingSent();
    }

    private function installMonitoring(): void
    {
        require_once database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php');
        (new \CreateMobileAppMonitoring())->up();
        config(['mobile.terminal_id'=>24,'mobile.booking_user_id'=>20]);
    }

    public function test_successful_logins_survive_token_replacement_and_first_login_is_counted_once(): void
    {
        $this->installMonitoring();
        $account=$this->account();
        $account->update(['password'=>Hash::make('correct-password')]);
        for ($i=0;$i<2;$i++) {
            $this->postJson('/api/mobile/v1/auth/login',['mobile'=>$account->mobile,'password'=>'correct-password'])->assertOk();
        }
        $events=DB::table('mobile_app_login_events')->orderBy('id')->get();
        $this->assertCount(2,$events);$this->assertSame([1,0],$events->pluck('first_login')->map(function($v){return (int)$v;})->all());
        $this->assertSame(1,$account->tokens()->count());
        $account->tokens()->delete();$this->assertSame(2,DB::table('mobile_app_login_events')->count());
        $this->assertSame(['id','company_id','passenger_account_id','kind','first_login','occurred_at'],Schema::getColumnListing('mobile_app_login_events'));
        $this->assertSame(24,(int)DB::table('mobile_app_runtime')->value('terminal_id'));
    }

    public function test_failed_and_blocked_logins_do_not_create_success_events(): void
    {
        $this->installMonitoring();$account=$this->account();$account->update(['password'=>Hash::make('correct-password')]);
        $this->postJson('/api/mobile/v1/auth/login',['mobile'=>$account->mobile,'password'=>'wrong-password'])->assertUnauthorized();
        $this->installPassengerControls();
        $this->restrictAccount($account,['status'=>'blocked']);
        $this->postJson('/api/mobile/v1/auth/login',['mobile'=>$account->mobile,'password'=>'correct-password'])->assertForbidden();
        $this->assertSame(0,DB::table('mobile_app_login_events')->count());
    }

    public function test_registration_records_automatic_sign_in_and_missing_migration_keeps_auth_working(): void
    {
        $this->installMonitoring();
        $this->postJson('/api/mobile/v1/auth/register',$this->signup())->assertCreated();
        $event=DB::table('mobile_app_login_events')->first();$this->assertSame('signup',$event->kind);$this->assertSame(1,(int)$event->first_login);
        Schema::drop('mobile_app_login_events');
        $account=PassengerAccount::first();$account->update(['password'=>Hash::make('correct-password')]);
        $this->postJson('/api/mobile/v1/auth/login',['mobile'=>$account->mobile,'password'=>'correct-password'])->assertOk();
    }

    public function test_runtime_reporting_keeps_original_start_and_uses_utc_without_counting_app_opens(): void
    {
        $this->installMonitoring();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-20 00:30:00','Asia/Karachi'));
        try {
            $telemetry=app(\App\Services\Mobile\MobileAppTelemetry::class);$telemetry->syncRuntime();
            $this->assertSame('2026-10-19 19:30:00',DB::table('mobile_app_runtime')->value('tracking_started_at'));
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-20 01:30:00','Asia/Karachi'));
            $telemetry->syncRuntime();
            $this->assertSame('2026-10-19 19:30:00',DB::table('mobile_app_runtime')->value('tracking_started_at'));
            $this->assertSame('2026-10-19 20:30:00',DB::table('mobile_app_runtime')->value('last_seen_at'));
            $this->assertSame(0,DB::table('mobile_app_login_events')->count());
        } finally { \Carbon\Carbon::setTestNow(); }
    }

}
