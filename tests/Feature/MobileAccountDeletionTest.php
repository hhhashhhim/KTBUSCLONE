<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Models\User;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Database\Schema\Blueprint;
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
        foreach ([
            '2026_07_18_000001_create_passenger_accounts_table.php' => 'CreatePassengerAccountsTable',
            '2026_07_18_000003_create_mobile_booking_quotes_table.php' => 'CreateMobileBookingQuotesTable',
            '2026_07_18_000005_create_saved_passengers_table.php' => 'CreateSavedPassengersTable',
            '2026_09_15_000010_create_mobile_account_deletions_table.php' => 'CreateMobileAccountDeletionsTable',
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
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('retained_value');
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

    public function test_deletion_removes_private_mobile_data_revokes_every_token_and_preserves_erp()
    {
        $account = $this->account();
        $other = $this->account(2);
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
}
