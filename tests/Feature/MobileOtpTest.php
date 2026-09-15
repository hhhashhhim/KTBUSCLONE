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
}
