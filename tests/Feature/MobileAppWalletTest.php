<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MobileAppAdminData;
use App\Support\MobileAppPermissions;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppWalletTest extends TestCase
{
    private const URL = '/api/web/v1/mobile-app/users/1/wallet';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'database.default' => 'wallet_test',
            'database.connections.wallet_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'session.driver' => 'array']);
        $this->app['url']->forceRootUrl('http://localhost');
        DB::purge('wallet_test');
        Schema::clearResolvedInstance('db.schema');
        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00', 'Asia/Karachi'));
        foreach ([
            'companies' => ['name', 'modules'], 'roles' => ['name', 'company_id', 'permissions'],
            'users' => ['name', 'email', 'company_id', 'terminal_id', 'role_id', 'route_ids', 'hide'],
            'customers' => ['name', 'company_id', 'cnic'],
            'passenger_accounts' => ['company_id', 'customer_id', 'mobile', 'email', 'mobile_verified_at'],
            'invoices' => ['company_id', 'passenger_account_id', 'terminal_id', 'route_id'],
            'tickets' => ['company_id'], 'mobile_app_configs' => ['company_id', 'features'],
            'card_assigns' => ['company_id', 'customer_id', 'starting_points', 'expiry_date', 'updated_by'],
            'mobile_app_admin_audits' => ['company_id', 'terminal_id', 'route_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'reason', 'changes'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id(); foreach ($columns as $column) { $table->text($column)->nullable(); }
                $table->timestamps(); $table->softDeletes();
            });
        }
        $modules = json_encode(MobileAppPermissions::append([], true));
        DB::table('companies')->insert(['id' => 1, 'name' => 'Kainat', 'modules' => $modules]);
        DB::table('customers')->insert(['id' => 7, 'company_id' => 1, 'name' => 'Passenger']);
        foreach ([[1, 1, 7], [2, 2, 8], [3, 1, 9]] as [$id, $company, $customer]) {
            DB::table('passenger_accounts')->insert(['id' => $id, 'company_id' => $company, 'customer_id' => $customer]);
        }
        DB::table('invoices')->insert(['company_id' => 1, 'passenger_account_id' => 1, 'terminal_id' => 10, 'route_id' => 3]);
        DB::table('mobile_app_configs')->insert(['company_id' => 1, 'features' => '{"wallet":true}']);
        DB::table('card_assigns')->insert(['id' => 12, 'company_id' => 1, 'customer_id' => 7, 'starting_points' => 100, 'expiry_date' => '2026-10-20']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function login(array $deny = [], bool $global = true, string $roleName = 'Operator'): User
    {
        $permissions = MobileAppPermissions::append([], true);
        foreach ($permissions[0]['buttons'] as &$button) {
            if (in_array($button['name'], $deny, true) || (!$global && $button['name'] === 'mobile-app-all-terminals')) { $button['allow'] = false; }
        }
        unset($button);
        DB::table('roles')->updateOrInsert(['id' => 1], ['company_id' => 1, 'name' => $roleName, 'permissions' => json_encode($permissions)]);
        $user = User::updateOrCreate(['id' => 1], ['company_id' => 1, 'terminal_id' => 10, 'role_id' => 1,
            'name' => 'Operator', 'hide' => 0, 'route_ids' => $global ? 'all' : '[3]']);
        $user->unsetRelations(); Sanctum::actingAs($user, ['*']);
        return $user;
    }

    private function payload(array $changes = []): array
    {
        return array_merge(['points_available' => 150, 'reason' => 'Correct loyalty credit',
            'revision' => $this->getJson(self::URL)->assertOk()->json('data.revision')], $changes);
    }

    public function test_set_increase_decrease_and_zero_update_the_existing_card_with_atomic_audits(): void
    {
        $this->login();
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.enabled', true)->assertJsonPath('data.card_id', 12)
            ->assertJsonPath('data.points_available', 100);
        foreach ([150, 80, 0] as $points) {
            $this->putJson(self::URL, $this->payload(['points_available' => $points]))->assertOk()->assertJsonPath('data.points_available', $points);
            $this->assertSame($points, (int) DB::table('card_assigns')->where('id', 12)->value('starting_points'));
        }
        $this->assertSame(1, DB::table('card_assigns')->count());
        $this->assertSame(3, DB::table('mobile_app_admin_audits')->count());
        $audit = DB::table('mobile_app_admin_audits')->first();
        $this->assertSame('wallet_points_adjusted', $audit->action);
        $this->assertSame('passenger', $audit->subject_type);
        $this->assertSame('1', $audit->subject_id);
        $this->assertSame('Correct loyalty credit', $audit->reason);
        $this->assertSame(['card_id' => 12, 'customer_id' => 7, 'old_points' => 100, 'new_points' => 150, 'points_change' => 50], json_decode($audit->changes, true));
        $this->assertSame(1, (int) DB::table('card_assigns')->value('updated_by'));
    }

    public function test_spending_or_another_adjustment_invalidates_stale_saves_and_duplicate_requests(): void
    {
        $this->login(); $payload = $this->payload();
        DB::table('card_assigns')->where('id', 12)->decrement('starting_points', 20);
        $this->putJson(self::URL, $payload)->assertStatus(409);
        $this->assertSame(80, (int) DB::table('card_assigns')->value('starting_points'));
        $fresh = $this->payload();
        $this->putJson(self::URL, $fresh)->assertOk();
        $this->putJson(self::URL, $fresh)->assertStatus(409);
        $this->assertSame(1, DB::table('mobile_app_admin_audits')->count());
    }

    public function test_disabled_wallet_setting_blocks_direct_writes_and_overrides_enabled_default(): void
    {
        $this->login(); $payload = $this->payload();
        config(['mobile.features.wallet' => true]);
        foreach (['{"wallet":false}', '{"wallet":0}'] as $features) {
            DB::table('mobile_app_configs')->update(['features' => $features]);
            $this->getJson(self::URL)->assertOk()->assertJsonPath('data.enabled', false);
            $this->putJson(self::URL, $payload)->assertStatus(409);
        }
        config(['mobile.features.wallet' => false]);
        DB::table('mobile_app_configs')->delete();
        $this->putJson(self::URL, $payload)->assertStatus(409);
        $this->assertSame(100, (int) DB::table('card_assigns')->value('starting_points'));
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
    }

    public function test_wallet_defaults_match_app_settings_and_explicit_enable_overrides_default(): void
    {
        $this->login();
        DB::table('mobile_app_configs')->delete();
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.enabled', true);
        $this->putJson(self::URL, $this->payload())->assertOk()->assertJsonPath('data.points_available', 150);
        config(['mobile.features.wallet' => false]);
        DB::table('mobile_app_configs')->insert(['company_id' => 1, 'features' => '{"wallet":true}']);
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.enabled', true);
    }

    public function test_permission_and_company_route_scope_are_required_even_for_direct_requests(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
        $this->putJson(self::URL, [])->assertUnauthorized();
        $this->login(); $payload = $this->payload();
        foreach (['mobile-app-manage-wallet', 'mobile-app-view-users'] as $denied) {
            $this->login([$denied]);
            $this->putJson(self::URL, $payload)->assertForbidden();
        }
        $this->login([], false);
        $this->getJson(self::URL)->assertOk();
        $this->putJson(self::URL, $payload)->assertForbidden();
        $this->login([], false, 'Admin');
        $this->putJson(self::URL, $payload)->assertOk();
        foreach ([2, 3, 999] as $id) {
            $this->getJson("/api/web/v1/mobile-app/users/$id/wallet")->assertNotFound();
            $this->putJson("/api/web/v1/mobile-app/users/$id/wallet", $payload)->assertNotFound();
        }
        $modules = MobileAppPermissions::append([], true); $modules[0]['allow'] = false;
        DB::table('companies')->update(['modules' => json_encode($modules)]);
        auth('sanctum')->user()->unsetRelations();
        $this->putJson(self::URL, $payload)->assertForbidden();
    }

    public function test_invalid_points_reasons_and_unchanged_balances_never_write(): void
    {
        $this->login(); $payload = $this->payload();
        foreach ([-1, 1.5, 2147483648, 'text', null, ''] as $points) {
            $this->putJson(self::URL, array_merge($payload, ['points_available' => $points]))->assertStatus(422);
        }
        foreach (['', '  ', 'ab', str_repeat('x', 1001)] as $reason) {
            $this->putJson(self::URL, array_merge($payload, ['reason' => $reason]))->assertStatus(422);
        }
        $this->putJson(self::URL, array_merge($payload, ['points_available' => 100]))->assertStatus(422);
        $this->putJson(self::URL, ['points_available' => 150, 'reason' => 'Correction'])->assertStatus(422);
        $this->assertSame(100, (int) DB::table('card_assigns')->value('starting_points'));
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
    }

    public function test_missing_expired_deleted_foreign_or_ambiguous_cards_cannot_be_adjusted(): void
    {
        $this->login(); $payload = $this->payload();
        foreach ([
            ['expiry_date' => '2026-10-19'], ['expiry_date' => null], ['deleted_at' => now()],
            ['company_id' => 2], ['customer_id' => 999],
        ] as $change) {
            DB::table('card_assigns')->where('id', 12)->update($change);
            $this->getJson(self::URL)->assertOk()->assertJsonPath('data.active', false);
            $this->putJson(self::URL, $payload)->assertStatus(409);
            DB::table('card_assigns')->where('id', 12)->update(['company_id' => 1, 'customer_id' => 7, 'expiry_date' => '2026-10-20', 'deleted_at' => null]);
        }
        DB::table('card_assigns')->insert(['id' => 13, 'company_id' => 1, 'customer_id' => 7, 'starting_points' => 500, 'expiry_date' => '2026-10-21']);
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.active', false);
        $this->putJson(self::URL, $payload)->assertStatus(409);
        DB::table('card_assigns')->delete();
        $this->putJson(self::URL, $payload)->assertStatus(409);
        $this->assertSame(0, DB::table('card_assigns')->count());
    }

    public function test_missing_wallet_schema_is_reported_and_never_created_by_a_request(): void
    {
        $this->login(); $payload = $this->payload(); Schema::drop('card_assigns');
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.available', false);
        $this->putJson(self::URL, $payload)->assertStatus(503);
        $this->assertFalse(Schema::hasTable('card_assigns'));
    }

    public function test_audit_failure_rolls_back_the_balance_and_staff_metadata(): void
    {
        $this->login(); $payload = $this->payload();
        $this->partialMock(MobileAppAdminData::class, function ($mock) {
            $mock->shouldReceive('audit')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        });
        $this->putJson(self::URL, $payload)->assertStatus(500);
        $this->assertSame(100, (int) DB::table('card_assigns')->value('starting_points'));
        $this->assertNull(DB::table('card_assigns')->value('updated_by'));
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
    }

    public function test_permission_upgrade_preserves_disabled_modules_existing_choices_and_other_privileges(): void
    {
        $this->login();
        $modules = MobileAppPermissions::append([], true);
        $modules[0]['buttons'] = array_values(array_filter($modules[0]['buttons'], function ($button) { return $button['name'] !== 'mobile-app-manage-wallet'; }));
        $modules[0]['allow'] = false;
        DB::table('companies')->update(['modules' => json_encode($modules)]);
        DB::table('roles')->update(['permissions' => json_encode($modules)]);
        require_once database_path('migrations/2026_10_20_000010_add_mobile_wallet_permission.php');
        $migration = new \AddMobileWalletPermission();
        $migration->up(); $migration->up();
        $company = json_decode(DB::table('companies')->value('modules'), true);
        $role = json_decode(DB::table('roles')->value('permissions'), true);
        $this->assertFalse($company[0]['allow']);
        $this->assertFalse($role[0]['allow']);
        $this->assertTrue(collect($company[0]['buttons'])->firstWhere('name', 'mobile-app-manage-wallet')['allow']);
        $this->assertFalse(collect($role[0]['buttons'])->firstWhere('name', 'mobile-app-manage-wallet')['allow']);
        $this->assertSame(count($modules[0]['buttons']) + 1, count($role[0]['buttons']));
        $this->assertSame($modules[0]['buttons'], array_slice($role[0]['buttons'], 0, -1));
    }
}
