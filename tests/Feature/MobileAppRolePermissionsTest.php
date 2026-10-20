<?php

namespace Tests\Feature;

use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\MobileAppPermissions;
use App\Support\MobileAppScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppRolePermissionsTest extends TestCase
{
    private const URL = '/api/web/v1/role/';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'database.default' => 'mobile_roles_test',
            'database.connections.mobile_roles_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'session.driver' => 'array']);
        $this->app['url']->forceRootUrl('http://localhost');
        DB::purge('mobile_roles_test');
        Schema::clearResolvedInstance('db.schema');
        // Legacy api.php uses require_once, so rebuild these real routes for
        // every fresh test application in the same PHP process.
        \Illuminate\Support\Facades\Route::middleware('api')->prefix('api')->group(function () {
            require base_path('routes/api/role.php');
        });
        foreach ([
            'companies' => ['name', 'modules'],
            'roles' => ['name', 'company_id', 'permissions'],
            'users' => ['name', 'company_id', 'role_id', 'hide', 'is_super_admin'],
            'activity_logs' => ['activity_by', 'message', 'requested_host', 'company_id'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) { $table->text($column)->nullable(); }
                $table->timestamps(); $table->softDeletes();
            });
        }
        Company::create(['id' => 1, 'name' => 'Company one', 'modules' => MobileAppPermissions::append([], true)]);
        Company::create(['id' => 2, 'name' => 'Company two', 'modules' => MobileAppPermissions::append([], true)]);
        Role::create(['id' => 2, 'name' => 'Passenger support', 'company_id' => 1, 'permissions' => []]);
        Role::create(['id' => 3, 'name' => 'Other company', 'company_id' => 2, 'permissions' => []]);
    }

    private function login(bool $edit = true, bool $roles = true): void
    {
        $permissions = [['name' => 'administration', 'allow' => true, 'childs' => [
            ['name' => 'roles', 'allow' => $roles, 'buttons' => [
                ['name' => 'assign-role', 'allow' => true], ['name' => 'edit-role', 'allow' => $edit],
            ]],
        ]]];
        Role::updateOrCreate(['id' => 1], ['name' => 'Role manager', 'company_id' => 1, 'permissions' => $permissions]);
        $user = User::updateOrCreate(['id' => 1], ['name' => 'Role manager', 'company_id' => 1, 'role_id' => 1, 'hide' => 0, 'is_super_admin' => 0]);
        Sanctum::actingAs($user->unsetRelations(), ['*']);
    }

    private function mobile(array $modules): array
    {
        return collect($modules)->firstWhere('name', 'mobile-app');
    }

    private function deny(array $modules, string $name): array
    {
        foreach ($modules[0]['buttons'] as &$button) {
            if ($button['name'] === $name) { $button['allow'] = false; }
        }
        unset($button);
        return $modules;
    }

    public function test_role_editor_lists_every_privilege_with_company_limits_and_no_implicit_grants(): void
    {
        $this->login();
        $company = Company::find(1);
        $company->update(['modules' => $this->deny($company->modules, 'mobile-app-manage-wallet')]);
        $response = $this->postJson(self::URL . 'get', ['id' => 2])->assertOk()->assertJsonPath('can_update_permissions', true);
        $this->assertSame(MobileAppPermissions::definitions(), $response->json('mobile_app_privileges'));
        $this->assertSame($company->fresh()->modules[0], $response->json('mobile_app_company_permissions'));
        $module = $this->mobile($response->json('permissions'));
        $this->assertFalse($module['allow']);
        $this->assertCount(count(MobileAppPermissions::definitions()), $module['buttons']);
        $this->assertCount(count(MobileAppPermissions::submodules()), $module['childs']);
        foreach (array_merge($module['buttons'], $module['childs']) as $item) { $this->assertFalse($item['allow']); }
        $this->assertSame([], Role::find(2)->permissions, 'Reading the editor must not grant or persist permissions.');
    }

    public function test_save_and_reload_preserves_individual_grants_denials_submodules_and_unrelated_permissions(): void
    {
        $this->login();
        $unrelated = ['name' => 'inventory', 'allow' => false, 'childs' => [
            ['name' => 'stock', 'allow' => true, 'buttons' => [['name' => 'view-stock', 'allow' => false]]],
        ]];
        $permissions = MobileAppPermissions::append([$unrelated], true);
        foreach ([0, 1] as $pass) {
            foreach ($permissions[1]['buttons'] as $i => &$button) { $button['allow'] = $i % 2 === $pass; }
            unset($button);
            foreach ($permissions[1]['childs'] as $i => &$child) { $child['allow'] = $i % 2 === $pass; }
            unset($child);
            $this->postJson(self::URL . 'update', ['id' => 2, 'name' => 'Passenger support', 'permissions' => $permissions])->assertStatus(201);
            $saved = $this->postJson(self::URL . 'get', ['id' => 2])->assertOk()->json('permissions');
            $this->assertSame($unrelated, $saved[0]);
            $this->assertSame($permissions[1], $this->mobile($saved), 'Do not automatically enable prerequisites or discard dormant selections.');
            $this->assertSame($permissions[1], $this->mobile(Role::find(2)->permissions));
        }
        $this->assertSame(2, DB::table('activity_logs')->count());
    }

    public function test_omitted_new_privilege_defaults_to_denied_and_admin_remains_subject_to_company_access(): void
    {
        $this->login();
        $modules = MobileAppPermissions::append([], true);
        $modules[0]['buttons'] = array_values(array_filter($modules[0]['buttons'], fn ($button) => $button['name'] !== 'mobile-app-manage-wallet'));
        $this->postJson(self::URL . 'update', ['id' => 2, 'name' => 'Passenger support', 'permissions' => $modules])->assertStatus(201);
        $wallet = collect($this->mobile(Role::find(2)->permissions)['buttons'])->firstWhere('name', 'mobile-app-manage-wallet');
        $this->assertFalse($wallet['allow']);

        Role::create(['id' => 4, 'name' => 'Admin', 'company_id' => 1, 'permissions' => []]);
        $this->postJson(self::URL . 'update', ['id' => 4, 'name' => 'Admin', 'permissions' => MobileAppPermissions::append([])])->assertStatus(201);
        $admin = Role::find(4);
        foreach ($this->mobile($admin->permissions)['buttons'] as $button) { $this->assertTrue($button['allow']); }
        $company = Company::find(1);
        $company->modules = $this->deny($company->modules, 'mobile-app-view-users');
        $user = new User(['company_id' => 1, 'hide' => 0]);
        $user->setRelation('company', $company)->setRelation('role', $admin);
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-manage-wallet'));
        $this->assertTrue(MobileAppScope::allows($user, 'mobile-app-view-bookings'));
    }

    public function test_role_changes_require_existing_edit_access_and_cannot_cross_company_or_save_invalid_flags(): void
    {
        $payload = ['id' => 2, 'name' => 'Passenger support', 'permissions' => MobileAppPermissions::append([], true)];
        $this->login(false);
        $this->postJson(self::URL . 'get', ['id' => 2])->assertOk()->assertJsonPath('can_update_permissions', false);
        $this->postJson(self::URL . 'update', $payload)->assertForbidden();
        $this->login(true, false);
        $this->postJson(self::URL . 'get', ['id' => 2])->assertForbidden();
        $this->postJson(self::URL . 'update', $payload)->assertForbidden();
        $this->login();
        $this->postJson(self::URL . 'get', ['id' => 3])->assertNotFound();
        $this->postJson(self::URL . 'update', array_merge($payload, ['id' => 3]))->assertNotFound();
        $payload['permissions'][0]['buttons'][0]['allow'] = 'yes';
        $this->postJson(self::URL . 'update', $payload)->assertStatus(422);
        $this->assertSame([], Role::find(2)->permissions);
        $this->assertSame([], Role::find(3)->permissions);
        $this->assertSame(0, DB::table('activity_logs')->count());
    }

    public function test_each_action_requires_company_and_role_grants_parent_submodule_and_read_prerequisites(): void
    {
        $all = MobileAppPermissions::append([], true);
        $user = new User(['company_id' => 1, 'hide' => 0]);
        $user->setRelation('company', new Company(['modules' => $all]));
        $user->setRelation('role', new Role(['name' => 'Support', 'company_id' => 1, 'permissions' => $all]));
        $definitions = collect(MobileAppPermissions::definitions())->keyBy('name');
        foreach ($definitions as $name => $definition) {
            foreach (['company', 'role'] as $side) {
                $field = $side === 'company' ? 'modules' : 'permissions';
                $this->assertTrue(MobileAppScope::allows($user, $name), $name);
                foreach (array_merge([$name], $definition['requires']) as $required) {
                    $user->$side->$field = $this->deny($all, $required);
                    $this->assertFalse(MobileAppScope::allows($user, $name), "$side: $name requires $required");
                    $user->$side->$field = $all;
                }
                $disabled = $all; $disabled[0]['allow'] = false;
                $user->$side->$field = $disabled;
                $this->assertFalse(MobileAppScope::allows($user, $name), "$side: disabled parent for $name");
                $user->$side->$field = $all;
                if ($definition['submenu']) {
                    foreach ($disabled[0]['childs'] as &$child) {
                        $child['allow'] = $child['name'] !== $definition['submenu'];
                    }
                    unset($child);
                    $disabled[0]['allow'] = true;
                    $user->$side->$field = $disabled;
                    $this->assertFalse(MobileAppScope::allows($user, $name), "$side: disabled submodule for $name");
                    $user->$side->$field = $all;
                }
            }
            foreach ($definition['requires'] as $required) {
                $this->assertArrayHasKey($required, $definitions->all());
                $this->assertNotContains($name, $definitions[$required]['requires'], 'Prerequisites cannot form a cycle.');
                $this->assertSame([], array_values(array_diff($definitions[$required]['requires'], $definition['requires'])), 'Transitive prerequisites must be included.');
            }
        }
        $this->assertFalse(MobileAppScope::allows($user, 'mobile-app-unknown'));
        $user->hide = 1;
        foreach ($definitions as $name => $definition) { $this->assertFalse(MobileAppScope::allows($user, $name)); }
        $user->hide = 0; $user->role->company_id = 2;
        foreach ($definitions as $name => $definition) { $this->assertFalse(MobileAppScope::allows($user, $name)); }
    }
}
