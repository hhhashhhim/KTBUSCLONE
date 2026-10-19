<?php

use App\Support\CallCenterPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCallCenterComplaintUpdatesPermission extends Migration
{
    public function up()
    {
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                $old = [];
                foreach ($modules as $module) {
                    if (($module['name'] ?? '') === 'call-center') { $old = array_column($module['buttons'] ?? [], 'allow', 'name'); break; }
                }
                $modules = CallCenterPermissions::append($modules, $table === 'companies');
                foreach ($modules as &$module) {
                    if (($module['name'] ?? '') !== 'call-center') { continue; }
                    foreach ($module['buttons'] as &$button) {
                        if ($button['name'] === 'call-center-update-complaints' && !array_key_exists($button['name'], $old)) {
                            // Existing complaint closers can also leave progress notes. Explicit denials stay denied.
                            $button['allow'] = $table === 'companies' || (!empty($old['call-center-view-complaints']) && !empty($old['call-center-close-complaints']));
                        }
                    }
                    unset($button);
                }
                unset($module);
                DB::table($table)->where('id', $row->id)->update([$field => json_encode($modules)]);
            });
        }
    }

    public function down()
    {
        // Keep configured grants and denials when rolling back application code.
    }
}
