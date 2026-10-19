<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CallCenterInstallTest extends TestCase
{
    public function test_company_availability_never_overwrites_call_center_role_grants(): void
    {
        $modules = \App\Support\CallCenterPermissions::append([['name' => 'ticketing', 'allow' => true, 'childs' => []]], true);
        $newRole = \App\Support\CallCenterPermissions::forCompanyAdmin($modules);
        $this->assertFalse($newRole[1]['allow']);
        foreach ($newRole[1]['buttons'] as $button) { $this->assertFalse($button['allow']); }
        $newRole[1]['allow'] = true;
        $newRole[1]['buttons'][0]['allow'] = true;
        $updated = \App\Support\CallCenterPermissions::forCompanyAdmin($modules, $newRole);
        $this->assertSame($newRole[1], $updated[1]);
        $this->assertSame($modules[0], $updated[0]);
    }

    public function test_scoped_install_is_repeatable_and_preserves_existing_role_permissions(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            Schema::create($table, function (Blueprint $t) use ($field) { $t->id(); $t->text($field); });
        }
        Schema::create('tickets', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->integer('customer_id'); $t->integer('terminal_id');
        });
        $existing = [['name' => 'inventory', 'allow' => false, 'childs' => []],
            ['name' => 'ticketing', 'allow' => true, 'childs' => [['name' => 'bookings', 'allow' => true]]]];
        DB::table('companies')->insert(['id' => 1, 'modules' => json_encode($existing)]);
        DB::table('roles')->insert(['id' => 1, 'permissions' => json_encode($existing)]);

        $this->assertSame(0, Artisan::call('call-center:install', ['--force' => true]));
        foreach (['call_center_calls', 'call_center_audits', 'call_center_reviews', 'call_center_notifications'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertTrue(Schema::hasColumns('call_center_calls', ['customer_response', 'resolved_by', 'inquiry_topic']));
        $this->assertSame(12, DB::table('migrations')->count());
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            $modules = json_decode(DB::table($table)->where('id', 1)->value($field), true);
            $this->assertSame($existing, array_slice($modules, 0, 2));
            $this->assertSame('call-center', $modules[2]['name']);
            $this->assertSame($table === 'companies', $modules[2]['allow']);
            foreach ($modules[2]['buttons'] as $button) {
                $this->assertSame($table === 'companies', $button['allow']);
            }
        }
        $before = DB::table('roles')->value('permissions');
        $this->assertSame(0, Artisan::call('call-center:install', ['--force' => true]));
        $this->assertSame(12, DB::table('migrations')->count());
        $this->assertSame($before, DB::table('roles')->value('permissions'));
    }
}
