<?php

use App\Support\CallCenterPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GrantCallCenterAdminPermissions extends Migration
{
    public function up()
    {
        DB::table('roles')->whereRaw('LOWER(TRIM(name)) = ?', ['admin'])->orderBy('id')->each(function ($role) {
            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(CallCenterPermissions::forCompanyAdmin(json_decode($role->permissions ?? '[]', true) ?: [])),
            ]);
        });
    }

    public function down()
    {
        // Do not revoke administrator access or erase subsequently configured permissions.
    }
}
