<?php

use App\Support\CallCenterPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCallCenterPermissions extends Migration
{
    public function up()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                DB::table($table)->where('id', $row->id)->update([
                    $field => json_encode(CallCenterPermissions::append($modules, $table === 'companies')),
                ]);
            });
        }
    }

    public function down()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                $modules = array_values(array_filter($modules, function ($menu) { return ($menu['name'] ?? '') !== 'call-center'; }));
                foreach ($modules as &$menu) {
                    if (($menu['name'] ?? '') === 'ticketing') {
                        $menu['childs'] = array_values(array_filter($menu['childs'] ?? [], function ($child) {
                            return ($child['name'] ?? '') !== 'call-center';
                        }));
                    }
                }
                unset($menu);
                DB::table($table)->where('id', $row->id)->update([$field => json_encode($modules)]);
            });
        }
    }
}
