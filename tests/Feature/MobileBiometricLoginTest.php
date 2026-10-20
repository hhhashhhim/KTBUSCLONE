<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileOtpService;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MobileBiometricLoginTest extends TestCase
{
    private $account;
    private $access;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.biometric_testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        config()->set('database.default', 'biometric_testing');
        config()->set('cache.default', 'array');
        config()->set('mobile.company_id', 1);
        config()->set('mobile.biometric_login_enabled', true);
        config()->set('mobile.biometric_session_days', 30);
        config()->set('mobile.otp.driver', 'log');
        config()->set('sanctum.expiration', 600);
        $travel = Mockery::mock(MobileTravelService::class);
        $travel->shouldReceive('companyId')->andReturn(1);
        $this->app->instance(MobileTravelService::class, $travel);
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->string('cnic'); $t->string('name'); $t->softDeletes();
        });
        Schema::create('passenger_accounts', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->integer('customer_id')->nullable();
            $t->string('mobile'); $t->string('password'); $t->timestamp('mobile_verified_at')->nullable();
            $t->string('password_reset_token_digest')->nullable(); $t->timestamp('password_reset_token_expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token', 64)->unique();
            $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamps();
        });
        require_once database_path('migrations/2026_10_20_000005_create_mobile_passenger_control_policies.php');
        (new \CreateMobilePassengerControlPolicies())->up();
        require_once database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php');
        (new \CreateMobileAppMonitoring())->up();
        require_once database_path('migrations/2026_10_20_000007_create_mobile_biometric_sessions.php');
        (new \CreateMobileBiometricSessions())->up();
        $this->account = PassengerAccount::create([
            'company_id' => 1, 'mobile' => '03001234567',
            'password' => Hash::make('password-123'), 'mobile_verified_at' => now(),
        ]);
        $this->access = $this->account->createToken('passenger-mobile')->plainTextToken;
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('biometric_testing');
        parent::tearDown();
    }

    private function request(string $method, string $path, array $data = [], ?string $access = null)
    {
        $this->app['auth']->forgetGuards();
        return $this->json($method, '/api/mobile/v1/' . $path, $data,
            $access ? ['Authorization' => 'Bearer ' . $access] : []);
    }

    private function enroll(): string
    {
        return $this->request('POST', 'auth/biometric', ['password' => 'password-123'], $this->access)
            ->assertOk()->json('data.refresh_token');
    }

    private function renew(string $secret)
    {
        return $this->request('POST', 'auth/biometric/renew', ['refresh_token' => $secret]);
    }

    public function test_renewal_after_access_expiry_rotates_both_credentials_and_records_login()
    {
        $secret = $this->enroll();
        $this->assertSame(96, strlen($secret));
        $this->assertNotSame($secret, DB::table('mobile_biometric_tokens')->value('token_hash'));
        $expires = DB::table('mobile_biometric_sessions')->value('expires_at');
        $this->travel(11)->hours();
        $this->request('GET', 'profile', [], $this->access)->assertUnauthorized();
        $response = $this->renew($secret)->assertOk()->assertJsonPath('data.passenger.id', $this->account->id);
        $this->assertNotSame($secret, $response->json('data.refresh_token'));
        $this->assertNotSame($this->access, $response->json('data.token'));
        $this->assertSame($expires, DB::table('mobile_biometric_sessions')->value('expires_at'));
        $this->request('GET', 'profile', [], $response->json('data.token'))->assertOk();
        $this->request('GET', 'profile', [], $this->access)->assertUnauthorized();
        $this->assertSame('biometric', DB::table('mobile_app_login_events')->value('kind'));
    }

    public function test_replaying_any_consumed_credential_revokes_current_session()
    {
        $secret = $this->enroll();
        $one = $this->renew($secret)->assertOk();
        $two = $this->renew($one->json('data.refresh_token'))->assertOk();
        $this->renew($secret)->assertUnauthorized();
        $this->request('GET', 'profile', [], $two->json('data.token'))->assertUnauthorized();
        $this->renew($two->json('data.refresh_token'))->assertUnauthorized();
        $this->assertSame(0, DB::table('mobile_biometric_sessions')->count());
    }

    public function test_erp_token_deletion_prevents_renewal()
    {
        $secret = $this->enroll();
        $this->account->tokens()->delete();
        $this->renew($secret)->assertUnauthorized();
        $this->assertSame(0, $this->account->tokens()->count());
    }

    public function test_absolute_expiry_cannot_be_extended_by_repeated_use()
    {
        $secret = $this->enroll();
        $this->travel(29)->days();
        $next = $this->renew($secret)->assertOk()->json('data.refresh_token');
        $this->travel(2)->days();
        $this->renew($next)->assertUnauthorized();
    }

    public function test_enrollment_requires_valid_passenger_session_password_and_verified_mobile()
    {
        $this->request('POST', 'auth/biometric', ['password' => 'password-123'])->assertUnauthorized();
        $this->request('POST', 'auth/biometric', ['password' => 'wrong'], $this->access)->assertStatus(422);
        $this->account->update(['mobile_verified_at' => null]);
        $this->request('POST', 'auth/biometric', ['password' => 'password-123'], $this->access)->assertForbidden();
        $this->assertSame(0, DB::table('mobile_biometric_sessions')->count());
    }

    /** @dataProvider restrictions */
    public function test_erp_account_restrictions_are_enforced_on_renewal(array $control)
    {
        $secret = $this->enroll();
        DB::table('mobile_app_passenger_controls')->insert(array_merge([
            'company_id' => 1, 'passenger_account_id' => $this->account->id,
        ], $control));
        $this->renew($secret)->assertForbidden();
        $this->assertNull(DB::table('mobile_biometric_tokens')->value('used_at'));
    }

    public function restrictions(): array
    {
        return [[['status' => 'blocked']], [['status' => 'blacklisted']], [['password_reset_required' => true]]];
    }

    public function test_identity_blacklist_and_global_signin_switch_are_enforced()
    {
        $secret = $this->enroll();
        DB::table('mobile_app_blacklist_entries')->insert([
            'company_id' => 1, 'passenger_account_id' => $this->account->id, 'kind' => 'mobile',
            'identity_hash' => \App\Services\Mobile\MobilePassengerControls::identity('mobile', $this->account->mobile),
            'created_at' => now(),
        ]);
        $this->renew($secret)->assertForbidden();
        DB::table('mobile_app_blacklist_entries')->delete();
        DB::table('mobile_app_policies')->insert(['company_id' => 1, 'settings' => json_encode(['logins_enabled' => false])]);
        $this->renew($secret)->assertForbidden();
    }

    public function test_company_scope_and_malformed_secrets_are_rejected()
    {
        $secret = $this->enroll();
        config()->set('mobile.company_id', 2);
        $this->renew($secret)->assertUnauthorized();
        $this->request('POST', 'auth/biometric', ['password' => 'password-123'], $this->access)->assertUnauthorized();
        $this->renew('short')->assertUnprocessable();
        $this->renew(str_repeat('a', 96))->assertUnauthorized();
    }

    public function test_disabling_quick_login_preserves_current_access_but_revokes_renewal()
    {
        $secret = $this->enroll();
        $this->request('DELETE', 'auth/biometric', [], $this->access)->assertOk();
        $this->renew($secret)->assertUnauthorized();
        $this->request('GET', 'profile', [], $this->access)->assertOk();
    }

    public function test_logout_revokes_biometric_renewal()
    {
        $secret = $this->enroll();
        $this->request('POST', 'auth/logout', [], $this->access)->assertOk();
        $this->renew($secret)->assertUnauthorized();
    }

    public function test_password_login_on_another_device_revokes_biometric_renewal()
    {
        $secret = $this->enroll();
        $this->request('POST', 'auth/login', ['mobile' => $this->account->mobile, 'password' => 'password-123'])->assertOk();
        $this->renew($secret)->assertUnauthorized();
    }

    public function test_password_reset_revokes_biometric_renewal()
    {
        $secret = $this->enroll();
        $this->account->update(['password_reset_token_digest' => Hash::make('reset-secret'),
            'password_reset_token_expires_at' => now()->addMinutes(15)]);
        $this->assertTrue(app(MobileOtpService::class)->resetPassword($this->account, 'reset-secret', 'new-password-123'));
        $this->renew($secret)->assertUnauthorized();
    }

    public function test_account_deletion_cannot_be_renewed()
    {
        $secret = $this->enroll();
        $this->account->delete();
        $this->renew($secret)->assertUnauthorized();
    }

    public function test_pruning_keeps_active_families_and_removes_only_expired_or_revoked_credentials()
    {
        $secret = $this->enroll();
        $this->artisan('mobile:prune-biometric-sessions')->assertExitCode(0);
        $this->assertSame(1, DB::table('mobile_biometric_sessions')->count());
        $this->renew($secret)->assertOk();
        $this->assertSame(2, DB::table('mobile_biometric_tokens')->count());
        $this->travel(31)->days();
        $this->artisan('mobile:prune-biometric-sessions')->assertExitCode(0);
        $this->assertSame(0, DB::table('mobile_biometric_tokens')->count());
        $this->assertSame(0, DB::table('mobile_biometric_sessions')->count());
        // The cleanup never deletes a passenger's still-existing access token.
        $this->assertSame(1, $this->account->tokens()->count());
    }

    public function test_enrollment_password_attempts_share_an_account_limit()
    {
        DB::table('mobile_app_policies')->insert(['company_id' => 1, 'settings' => json_encode(['login_per_minute' => 1])]);
        $this->request('POST', 'auth/biometric', ['password' => 'wrong'], $this->access)->assertStatus(422);
        $this->request('POST', 'auth/biometric', ['password' => 'password-123'], $this->access)->assertStatus(429);
        $this->assertSame(0, DB::table('mobile_biometric_sessions')->count());
        $this->request('GET', 'profile', [], $this->access)->assertOk();
    }

    public function test_feature_switch_missing_migration_and_account_attempt_limit()
    {
        $secret = $this->enroll();
        config()->set('mobile.biometric_login_enabled', false);
        $this->renew($secret)->assertStatus(503);
        config()->set('mobile.biometric_login_enabled', true);
        DB::table('mobile_app_policies')->insert(['company_id' => 1, 'settings' => json_encode(['login_per_minute' => 1])]);
        $next = $this->renew($secret)->assertOk()->json('data.refresh_token');
        $this->renew($next)->assertStatus(429);
        Schema::drop('mobile_biometric_tokens');
        $this->renew($next)->assertStatus(503);
    }
}
