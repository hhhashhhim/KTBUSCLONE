<?php

namespace Tests\Feature;

use App\Http\Controllers\Mobile\MobileNotificationController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\MobileSavedPassengerController;
use App\Http\Requests\Mobile\SavePassengerRequest;
use App\Http\Requests\Mobile\UpdatePassengerProfileRequest;
use App\Models\PassengerAccount;
use App\Models\User;
use App\Services\Mobile\MobileAccountDataDeletion;
use App\Services\Mobile\MobileNotificationInbox;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MobileAccountDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.account_deletion_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        config()->set('database.default', 'account_deletion_testing');
        config()->set('cache.default', 'array');
        config()->set('mobile.company_id', 1);
        config()->set('mobile_notifications.enabled', true);
        foreach ([
            '2026_07_18_000001_create_passenger_accounts_table.php' => 'CreatePassengerAccountsTable',
            '2026_07_18_000003_create_mobile_booking_quotes_table.php' => 'CreateMobileBookingQuotesTable',
            '2026_07_18_000005_create_saved_passengers_table.php' => 'CreateSavedPassengersTable',
            '2026_09_15_000010_create_mobile_account_deletions_table.php' => 'CreateMobileAccountDeletionsTable',
            '2026_10_20_000005_create_mobile_passenger_control_policies.php' => 'CreateMobilePassengerControlPolicies',
            '2026_10_20_000006_create_mobile_app_monitoring.php' => 'CreateMobileAppMonitoring',
            '2026_10_20_000007_create_mobile_biometric_sessions.php' => 'CreateMobileBiometricSessions',
            '2026_10_20_000008_create_mobile_notifications.php' => 'CreateMobileNotifications',
            '2026_10_29_000001_link_mobile_booking_snapshots.php' => 'LinkMobileBookingSnapshots',
        ] as $file => $class) {
            require_once database_path('migrations/' . $file);
            (new $class)->up();
        }
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamps();
        });
        // Sentinel records: deletion must never mutate shared ERP tables.
        foreach (['customers', 'invoices', 'tickets', 'mobile_payments', 'card_assigns'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id(); $table->string('retained_value');
                if ($name === 'customers') {
                    $table->unsignedBigInteger('company_id')->default(1);
                    $table->string('cnic')->nullable();
                }
            });
            DB::table($name)->insert(['id' => 1, 'retained_value' => 'unchanged']);
        }
        $travel = Mockery::mock(MobileTravelService::class);
        $travel->shouldReceive('companyId')->andReturn(1);
        $this->app->instance(MobileTravelService::class, $travel);
    }

    protected function tearDown(): void
    {
        DB::purge('account_deletion_testing');
        parent::tearDown();
    }

    private function account(int $id = 1, int $company = 1): PassengerAccount
    {
        $account = PassengerAccount::create([
            'id' => $id, 'customer_id' => $id, 'company_id' => $company,
            'mobile' => '0300123456' . $id, 'password' => Hash::make('current-password'),
            'mobile_verified_at' => now(),
            'otp_digest' => 'private-code', 'email' => 'test@example.com',
        ]);
        $account->savedPassengers()->create([
            'company_id' => $company, 'full_name' => 'Saved Passenger',
            'cnic' => '3520212345671', 'mobile' => $account->mobile, 'gender' => 'male',
        ]);
        DB::table('mobile_booking_quotes')->insert([
            'token' => 'quote-' . $id, 'passenger_account_id' => $id, 'company_id' => $company,
            'schedule_detail_id' => 1, 'payload' => '{}', 'base_fare' => 100,
            'total' => 100, 'expires_at' => now()->addMinutes(10),
        ]);
        return $account;
    }

    private function input(): array
    {
        return ['password' => 'current-password', 'confirmation' => 'DELETE'];
    }

    private function privateData(int $account, int $company = 1): array
    {
        $owner = ['company_id' => $company, 'passenger_account_id' => $account];
        $ids = [];
        $ids['mobile_notifications'] = DB::table('mobile_notifications')->insertGetId($owner + [
            'event_key' => 'notice', 'kind' => 'announcement', 'title' => 'Update', 'body' => 'Private message', 'created_at' => now(),
        ]);
        $ids['mobile_push_devices'] = DB::table('mobile_push_devices')->insertGetId($owner + [
            'access_token_id' => $company * 100 + $account, 'token_hash' => hash('sha256', "$company-$account-device"),
            'token' => 'encrypted-device-token', 'platform' => 'android', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids['mobile_notification_deliveries'] = DB::table('mobile_notification_deliveries')->insertGetId([
            'notification_id' => $ids['mobile_notifications'], 'device_id' => $ids['mobile_push_devices'],
        ]);
        $ids['mobile_biometric_sessions'] = DB::table('mobile_biometric_sessions')->insertGetId($owner + [
            'access_token_id' => $company * 100 + $account, 'expires_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ids['mobile_biometric_tokens'] = DB::table('mobile_biometric_tokens')->insertGetId([
            'session_id' => $ids['mobile_biometric_sessions'], 'token_hash' => hash('sha256', "$company-$account-refresh"), 'created_at' => now(),
        ]);
        $ids['mobile_app_login_events'] = DB::table('mobile_app_login_events')->insertGetId($owner + [
            'kind' => 'password', 'first_login' => true, 'occurred_at' => now('UTC'),
        ]);
        $ids['mobile_app_passenger_controls'] = DB::table('mobile_app_passenger_controls')->insertGetId($owner + ['status' => 'active']);
        $ids['mobile_app_seat_usage'] = DB::table('mobile_app_seat_usage')->insertGetId($owner + [
            'invoice_id' => $company * 100 + $account, 'seats' => 2, 'booked_at' => now(),
        ]);
        DB::table('mobile_app_blacklist_entries')->insert($owner + [
            'kind' => 'mobile', 'identity_hash' => hash('sha256', "$company-$account-security-evidence"), 'created_at' => now(),
        ]);
        return $ids;
    }

    public function test_deletion_removes_private_mobile_data_revokes_every_token_and_preserves_erp()
    {
        $account = $this->account();
        $other = $this->account(2);
        $removed = $this->privateData(1);
        $retained = [$this->privateData(2), $this->privateData(1, 2)];
        $token = $account->createToken('phone')->plainTextToken;
        $account->createToken('tablet');
        $other->createToken('other-phone');
        $this->withToken($token)->deleteJson('/api/mobile/v1/account', $this->input())
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data', null);
        $this->assertDatabaseMissing('passenger_accounts', ['id' => 1]);
        $this->assertDatabaseMissing('saved_passengers', ['passenger_account_id' => 1]);
        $this->assertDatabaseMissing('mobile_booking_quotes', ['passenger_account_id' => 1]);
        $this->assertSame(0, $account->tokens()->count());
        $this->assertSame(1, $other->tokens()->count());
        $this->assertDatabaseHas('passenger_accounts', ['id' => 2]);
        $this->assertDatabaseHas('saved_passengers', ['passenger_account_id' => 2]);
        $this->assertDatabaseHas('mobile_booking_quotes', ['passenger_account_id' => 2]);
        $this->assertDatabaseHas('mobile_account_deletions', ['passenger_account_id' => 1, 'company_id' => 1]);
        foreach ($removed as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }
        foreach ($retained as $records) {
            foreach ($records as $table => $id) {
                $this->assertDatabaseHas($table, ['id' => $id]);
            }
        }
        $this->assertDatabaseHas('mobile_app_blacklist_entries', ['company_id' => 1, 'passenger_account_id' => 1]);
        foreach (['customers', 'invoices', 'tickets', 'mobile_payments', 'card_assigns'] as $name) {
            $this->assertDatabaseHas($name, ['id' => 1, 'retained_value' => 'unchanged']);
        }
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/mobile/v1/account', $this->input())->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/mobile/v1/auth/login', [
            'mobile' => $account->mobile, 'password' => 'current-password',
        ])->assertUnauthorized();
    }

    public function test_password_and_explicit_confirmation_are_required_without_changing_the_account()
    {
        $account = $this->account();
        $account->createToken('phone');
        Sanctum::actingAs($account);
        foreach ([[], ['password' => 'current-password'],
            ['password' => 'wrong', 'confirmation' => 'DELETE'],
            ['password' => 'current-password', 'confirmation' => 'KEEP']] as $input) {
            $this->deleteJson('/api/mobile/v1/account', $input)->assertStatus(422)
                ->assertJsonPath('success', false);
        }
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1]);
        $this->assertDatabaseHas('saved_passengers', ['passenger_account_id' => 1]);
        $this->assertSame(1, $account->tokens()->count());
        $this->assertSame(0, DB::table('mobile_account_deletions')->count());
    }

    public function test_guest_staff_and_other_company_cannot_delete_a_passenger()
    {
        $account = $this->account(1, 2);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertUnauthorized();
        Sanctum::actingAs($account);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertForbidden();
        $staff = new User();
        $staff->id = 1;
        Sanctum::actingAs($staff);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertForbidden();
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1]);
    }

    public function test_failed_deletion_rolls_back_tokens_private_data_and_audit()
    {
        $account = $this->account();
        $private = $this->privateData(1);
        $account->createToken('phone');
        Sanctum::actingAs($account);
        PassengerAccount::deleting(function () { throw new \RuntimeException('Simulated database failure'); });
        try {
            $this->deleteJson('/api/mobile/v1/account', $this->input())->assertStatus(500);
            $this->assertDatabaseHas('passenger_accounts', ['id' => 1]);
            $this->assertDatabaseHas('saved_passengers', ['passenger_account_id' => 1]);
            $this->assertDatabaseHas('mobile_booking_quotes', ['passenger_account_id' => 1]);
            $this->assertSame(1, $account->tokens()->count());
            $this->assertSame(0, DB::table('mobile_account_deletions')->count());
            foreach ($private as $table => $id) {
                $this->assertDatabaseHas($table, ['id' => $id]);
            }
        } finally { PassengerAccount::flushEventListeners(); }
    }

    public function test_password_attempts_are_rate_limited()
    {
        Sanctum::actingAs($this->account());
        for ($i = 0; $i < 5; $i++) {
            $this->deleteJson('/api/mobile/v1/account', ['password' => 'wrong', 'confirmation' => 'DELETE'])
                ->assertStatus(422);
        }
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertStatus(429);
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1]);
    }

    public function test_deletion_retains_booked_snapshots_and_other_company_drafts()
    {
        $account = $this->account();
        $draft = (array) DB::table('mobile_booking_quotes')->first();
        unset($draft['id']);
        foreach ([
            ['token' => 'used-legacy', 'used_at' => now()],
            ['token' => 'linked-booking', 'invoice_id' => 99],
            ['token' => 'other-company', 'company_id' => 2],
        ] as $changes) {
            DB::table('mobile_booking_quotes')->insert(array_replace($draft, $changes));
        }
        $saved = (array) DB::table('saved_passengers')->first();
        unset($saved['id']);
        DB::table('saved_passengers')->insert(array_replace($saved, ['company_id' => 2, 'cnic' => '3520212345672']));
        Sanctum::actingAs($account);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertOk();
        $this->assertDatabaseMissing('mobile_booking_quotes', ['token' => 'quote-1']);
        foreach (['used-legacy', 'linked-booking', 'other-company'] as $token) {
            $this->assertDatabaseHas('mobile_booking_quotes', ['token' => $token]);
        }
        $this->assertDatabaseHas('saved_passengers', ['company_id' => 2, 'passenger_account_id' => 1]);
    }

    public function test_cleanup_dry_run_counts_without_changing_data()
    {
        $account = $this->account();
        $private = $this->privateData(1);
        $account->createToken('phone');
        $counts = (new MobileAccountDataDeletion)->deletePrivateData(1, 1, false);
        foreach ($private as $table => $id) {
            $this->assertSame(1, $counts[$table]);
            $this->assertDatabaseHas($table, ['id' => $id]);
        }
        $this->assertSame(1, $counts['personal_access_tokens']);
        $this->assertSame(1, $account->tokens()->count());
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1]);
    }

    public function test_stale_authenticated_account_cannot_repeat_deletion()
    {
        $account = $this->account();
        Sanctum::actingAs($account);
        $account->delete();
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertUnauthorized();
        $this->assertSame(0, DB::table('mobile_account_deletions')->count());
    }

    public function test_delayed_notifications_do_not_recreate_deleted_or_foreign_account_data()
    {
        $account = $this->account();
        MobileNotificationInbox::record(1, 1, 'before', 'announcement', 'Update', 'Message');
        MobileNotificationInbox::record(2, 1, 'foreign', 'announcement', 'Update', 'Message');
        $this->assertSame(1, DB::table('mobile_notifications')->count());
        Sanctum::actingAs($account);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertOk();
        MobileNotificationInbox::record(1, 1, 'after', 'announcement', 'Update', 'Message');
        $this->assertSame(0, DB::table('mobile_notifications')->count());
    }

    public function test_device_registration_rechecks_stale_account_and_revoked_token()
    {
        foreach (['account', 'token'] as $removed) {
            $account = $this->account($removed === 'account' ? 1 : 2);
            $token = $account->createToken('phone')->accessToken;
            $account->withAccessToken($token);
            // Simulate deletion after middleware authenticated this request.
            if ($removed === 'account') { $account->delete(); }
            else { $token->delete(); }
            $request = Request::create('/api/mobile/v1/notifications/device', 'PUT', [
                'token' => 'cExampleFirebaseInstallation1', 'platform' => 'android',
            ]);
            $request->setUserResolver(function () use ($account) { return $account; });
            try {
                (new MobileNotificationController)->registerDevice($request);
                $this->fail('A stale account or revoked token must not register a device.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(401, $exception->getStatusCode());
            }
        }
        $this->assertSame(0, DB::table('mobile_push_devices')->count());
    }

    public function test_saved_passenger_and_profile_writes_recheck_deleted_account()
    {
        $account = $this->account();
        $saved = DB::table('saved_passengers')->value('id');
        Sanctum::actingAs($account);
        $this->deleteJson('/api/mobile/v1/account', $this->input())->assertOk();
        foreach (['store', 'update', 'destroy', 'profile'] as $action) {
            $class = $action === 'profile' ? UpdatePassengerProfileRequest::class : SavePassengerRequest::class;
            $request = $class::create('/api/mobile/v1/profile', 'POST', [
                'full_name' => 'Must not be written', 'cnic' => '3520212345671', 'mobile' => '03001234567', 'gender' => 'male',
            ]);
            $request->setUserResolver(function () use ($account) { return $account; });
            try {
                if ($action === 'profile') { (new MobileProfileController)->update($request); }
                elseif ($action === 'store') { (new MobileSavedPassengerController)->store($request); }
                else { (new MobileSavedPassengerController)->$action($request, $saved); }
                $this->fail('A stale authenticated account must not change passenger data.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(401, $exception->getStatusCode());
            }
        }
        $this->assertSame(0, DB::table('saved_passengers')->count());
        $this->assertDatabaseHas('customers', ['id' => 1, 'retained_value' => 'unchanged', 'cnic' => null]);
    }

    public function test_saved_passenger_create_and_update_contract_is_preserved()
    {
        Sanctum::actingAs($this->account());
        $input = ['full_name' => 'New passenger', 'cnic' => '3520212345672', 'mobile' => '03001234567', 'gender' => 'male'];
        $created = $this->postJson('/api/mobile/v1/saved-passengers', $input)->assertCreated();
        $id = $created->json('data.id');
        $input['full_name'] = 'Updated passenger';
        $this->postJson('/api/mobile/v1/saved-passengers', $input)->assertOk()->assertJsonPath('data.id', $id);
        $this->putJson('/api/mobile/v1/saved-passengers/' . $id, $input)->assertOk()->assertJsonPath('data.full_name', 'Updated passenger');
        $this->deleteJson('/api/mobile/v1/saved-passengers/' . $id)->assertOk();
        $this->assertDatabaseMissing('saved_passengers', ['id' => $id]);
    }
}
