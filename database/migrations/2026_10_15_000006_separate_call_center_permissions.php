<?php

use App\Support\CallCenterPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeparateCallCenterPermissions extends Migration
{
    public function up()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                DB::table($table)->where('id', $row->id)->update([
                    $field => json_encode(CallCenterPermissions::append($modules, $table === 'companies', $table === 'roles')),
                ]);
            });
        }
    }

    public function down()
    {
        // Preserve independently configured access; nesting it again could change effective grants.
    }
}
