<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceMobileAppPassengerControls;
use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\MobileAppPermissions;
use App\Support\MobileAppScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileAppAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mobile_app_access_test', 'database.connections.mobile_app_access_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'mobile.company_id' => 1]);
        DB::purge('mobile_app_access_test');
        Schema::clearResolvedInstance('db.schema');
    }

    private function staff(array $company, array $role): User
    {
        $user = new User(['id' => 1, 'company_id' => 1, 'terminal_id' => 10, 'route_ids' => '[3]', 'hide' => 0]);
        $user->setRelation('company', new Company(['modules' => $company]));
        $user->setRelation('role', new Role(['company_id' => 1, 'name' => 'Clerk', 'permissions' => $role]));
        return $user;
    }

    public function test_new_privileges_default_deny_and_disabled_parent_or_submodule_overrides_button(): void
    {
        $company = MobileAppPermissions::append([], true);
        $role = MobileAppPermissions::append([]);
        $user = $this->staff($company, $role);
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-view-users'));
        $role = MobileAppPermissions::append([], true);
        $role[0]['allow'] = false;
        $user->role->permissions = $role;
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-view-users'));
        $role[0]['allow'] = true;
        foreach ($role[0]['childs'] as &$child) {
            if ($child['name'] === 'mobile-app-users') { $child['allow'] = false; }
        }
        unset($child);
        $user->role->permissions = $role;
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-view-users'));
        $this->assertTrue(MobileAppScope::allows($user, 'mobile-app-view-bookings'));
        $user->role->company_id = 2;
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-view-bookings'));
    }

    public function test_permission_normalization_preserves_existing_denials_and_unrelated_modules(): void
    {
        $unrelated = ['name' => 'inventory', 'allow' => false, 'childs' => []];
        $modules = MobileAppPermissions::append([$unrelated]);
        $modules[1]['allow'] = true;
        $modules[1]['buttons'][0]['allow'] = true;
        $new = MobileAppPermissions::append($modules);
        $this->assertSame($modules, $new);
        $this->assertFalse($new[1]['buttons'][1]['allow']);
        $admin = MobileAppPermissions::forCompanyAdmin($modules);
        $this->assertSame($unrelated, $admin[0]);
        foreach ($admin[1]['buttons'] as $button) { $this->assertTrue($button['allow']); }
        foreach ($admin[1]['childs'] as $child) { $this->assertTrue($child['allow']); }
        $company = $admin;
        $company[1]['allow'] = false;
        $staff = $this->staff($company, $admin);
        $staff->role->name = 'Admin';
        $this->assertFalse(MobileAppScope::allows($staff, 'mobile-app-view-users'));
    }

    public function test_scoped_install_only_creates_admin_tables_and_preserves_role_permissions(): void
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $name => $field) {
            Schema::create($name, function (Blueprint $table) use ($field) { $table->id(); $table->string('name')->nullable(); $table->text($field); });
        }
        $unrelated = [['name' => 'ticketing', 'allow' => false, 'childs' => []]];
        DB::table('companies')->insert(['id' => 1, 'modules' => json_encode($unrelated)]);
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Clerk', 'permissions' => json_encode($unrelated)],
            ['id' => 2, 'name' => 'Admin', 'permissions' => json_encode($unrelated)],
        ]);
        $this->assertSame(0, Artisan::call('mobile-app:install', ['--force' => true]));
        $this->assertTrue(Schema::hasTable('mobile_app_admin_audits'));
        $this->assertTrue(Schema::hasTable('mobile_app_passenger_controls'));
        $this->assertTrue(Schema::hasTable('mobile_app_login_events'));
        $this->assertTrue(Schema::hasTable('mobile_app_runtime'));
        $this->assertFalse(Schema::hasTable('passenger_accounts'));
        $this->assertFalse(Schema::hasTable('mobile_app_configs'));
        $this->assertSame(7, DB::table('migrations')->count());
        $this->assertTrue(Schema::hasTable('mobile_notifications'));
        $clerk = json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true);
        $this->assertSame($unrelated[0], $clerk[0]);
        $this->assertFalse($clerk[1]['allow']);
        foreach ($clerk[1]['buttons'] as $button) { $this->assertFalse($button['allow']); }
        $admin = json_decode(DB::table('roles')->where('id', 2)->value('permissions'), true);
        foreach ($admin[1]['buttons'] as $button) { $this->assertTrue($button['allow']); }
        $before = DB::table('roles')->where('id', 1)->value('permissions');
        $this->assertSame(0, Artisan::call('mobile-app:install', ['--force' => true]));
        $this->assertSame($before, DB::table('roles')->where('id', 1)->value('permissions'));
        $this->assertSame(7, DB::table('migrations')->count());
        $this->assertTrue(Schema::hasTable('mobile_notifications'));
    }

    private function controlSchema(): void
    {
        require_once database_path('migrations/2026_10_20_000001_create_mobile_app_admin_tables.php');
        (new \CreateMobileAppAdminTables())->up();
        Schema::create('passenger_accounts', function (Blueprint $table) { $table->id(); $table->integer('company_id'); $table->string('mobile'); });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->string('tokenable_type'); $table->integer('tokenable_id'); $table->string('token'); $table->text('abilities')->nullable();
        });
        DB::table('passenger_accounts')->insert([
            ['id' => 1, 'company_id' => 1, 'mobile' => '03001234567'],
            ['id' => 2, 'company_id' => 2, 'mobile' => '03001234567'],
        ]);
        DB::table('mobile_app_passenger_controls')->insert(['company_id' => 1, 'passenger_account_id' => 1, 'status' => 'suspended']);
    }

    private function controlled(Request $request): int
    {
        return app(EnforceMobileAppPassengerControls::class)->handle($request, function () { return response('allowed', 200); })->getStatusCode();
    }

    public function test_suspension_blocks_normalized_login_and_existing_passenger_tokens_only(): void
    {
        $this->controlSchema();
        $this->assertSame(403, $this->controlled(Request::create('/api/mobile/v1/auth/login', 'POST', ['mobile' => '0300-1234567'])));
        config(['mobile.company_id' => 2]);
        $this->assertSame(200, $this->controlled(Request::create('/api/mobile/v1/auth/login', 'POST', ['mobile' => '03001234567'])));
        DB::table('personal_access_tokens')->insert([
            ['id' => 1, 'tokenable_type' => 'App\\Models\\PassengerAccount', 'tokenable_id' => 1, 'token' => hash('sha256', 'passenger-secret')],
            ['id' => 2, 'tokenable_type' => 'App\\Models\\User', 'tokenable_id' => 1, 'token' => hash('sha256', 'staff-secret')],
        ]);
        $passenger = Request::create('/api/mobile/v1/bookings', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 1|passenger-secret']);
        $this->assertSame(403, $this->controlled($passenger));
        $staff = Request::create('/api/mobile/v1/bookings', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 2|staff-secret']);
        $this->assertSame(200, $this->controlled($staff));
        $staffRoute = Request::create('/api/web/v1/mobile-app/users', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 1|passenger-secret']);
        $this->assertSame(200, $this->controlled($staffRoute));
        $passenger->headers->set('Authorization', 'Bearer 1|wrong-token');
        $this->assertSame(200, $this->controlled($passenger));
    }

    public function test_missing_mobile_schema_does_not_break_existing_api(): void
    {
        $this->assertSame(200, $this->controlled(Request::create('/api/mobile/v1/auth/login', 'POST', ['mobile' => '03001234567'])));
    }

    public function test_ticket_scope_keeps_route_terminal_company_and_mobile_invoice_identity(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('terminal_id'); $table->integer('route_id'); $table->integer('invoice_id'); $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) { $table->id(); $table->integer('company_id'); $table->integer('passenger_account_id')->nullable(); });
        DB::table('invoices')->insert([
            ['id' => 1, 'company_id' => 1, 'passenger_account_id' => 1],
            ['id' => 2, 'company_id' => 1, 'passenger_account_id' => null],
            ['id' => 3, 'company_id' => 2, 'passenger_account_id' => 2],
        ]);
        DB::table('tickets')->insert([
            ['id' => 1, 'company_id' => 1, 'terminal_id' => 10, 'route_id' => 3, 'invoice_id' => 1],
            ['id' => 2, 'company_id' => 1, 'terminal_id' => 11, 'route_id' => 3, 'invoice_id' => 1],
            ['id' => 3, 'company_id' => 1, 'terminal_id' => 10, 'route_id' => 4, 'invoice_id' => 1],
            ['id' => 4, 'company_id' => 1, 'terminal_id' => 10, 'route_id' => 3, 'invoice_id' => 2],
            ['id' => 5, 'company_id' => 1, 'terminal_id' => 10, 'route_id' => 3, 'invoice_id' => 3],
        ]);
        $modules = MobileAppPermissions::append([], true);
        $role = $modules;
        foreach ($role[0]['buttons'] as &$button) { if ($button['name'] === 'mobile-app-all-terminals') { $button['allow'] = false; } }
        unset($button);
        $staff = $this->staff($modules, $role);
        $request = new Request();
        $request->setUserResolver(function () use ($staff) { return $staff; });
        $this->assertSame([1], MobileAppScope::tickets($request)->pluck('tickets.id')->all());
        $staff->role->permissions = $modules;
        $this->assertSame([1, 2], MobileAppScope::tickets($request)->orderBy('tickets.id')->pluck('tickets.id')->all());
    }
}
