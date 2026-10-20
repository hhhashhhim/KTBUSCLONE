<?php

use App\Support\CallCenterPermissions;
use App\Support\MobileAppPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMobileAppPermissions extends Migration
{
    public function up()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                $modules = $table === 'roles' && CallCenterPermissions::isAdminRole($row->name ?? null)
                    ? MobileAppPermissions::forCompanyAdmin($modules)
                    : MobileAppPermissions::append($modules, $table === 'companies');
                DB::table($table)->where('id', $row->id)->update([$field => json_encode($modules)]);
            });
        }
    }

    public function down()
    {
        // Keep configured grants and audit ownership when reverting application code.
    }
}
