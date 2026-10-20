<?php

use App\Support\CallCenterPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMobileWalletPermission extends Migration
{
    public function up()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                foreach ($modules as &$module) {
                    if (($module['name'] ?? '') !== 'mobile-app') { continue; }
                    $buttons = $module['buttons'] ?? [];
                    if (!in_array('mobile-app-manage-wallet', array_column($buttons, 'name'), true)) {
                        $buttons[] = ['name' => 'mobile-app-manage-wallet',
                            'allow' => $table === 'companies' || CallCenterPermissions::isAdminRole($row->name ?? null)];
                        $module['buttons'] = $buttons;
                    }
                }
                unset($module);
                DB::table($table)->where('id', $row->id)->update([$field => json_encode($modules)]);
            });
        }
    }

    public function down() { /* Preserve explicit grants and audit history. */ }
}
