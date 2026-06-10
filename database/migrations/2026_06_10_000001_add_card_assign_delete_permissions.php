<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCardAssignDeletePermissions extends Migration
{
    public function up()
    {
        $permissionsBySubmenu = [
            'loyaltyCardAssign' => ['delete-assign-card'],
            'discountCardAssign' => ['delete-assign-discount'],
        ];

        $this->addPermissionsToJsonColumn('companies', 'modules', $permissionsBySubmenu, true);
        $this->addPermissionsToJsonColumn('roles', 'permissions', $permissionsBySubmenu, false);
    }

    public function down()
    {
        $permissionNames = ['delete-assign-card', 'delete-assign-discount'];

        $this->removePermissionsFromJsonColumn('companies', 'modules', $permissionNames);
        $this->removePermissionsFromJsonColumn('roles', 'permissions', $permissionNames);
    }

    private function addPermissionsToJsonColumn(string $table, string $column, array $permissionsBySubmenu, bool $allow): void
    {
        DB::table($table)->orderBy('id')->each(function ($record) use ($table, $column, $permissionsBySubmenu, $allow) {
            $modules = json_decode($record->{$column}, true);

            if (!is_array($modules)) {
                return;
            }

            foreach ($modules as &$module) {
                if (!isset($module['childs']) || !is_array($module['childs'])) {
                    continue;
                }

                foreach ($module['childs'] as &$submenu) {
                    if (!isset($permissionsBySubmenu[$submenu['name'] ?? null])) {
                        continue;
                    }

                    $submenu['buttons'] = $submenu['buttons'] ?? [];
                    $existingNames = array_column($submenu['buttons'], 'name');

                    foreach ($permissionsBySubmenu[$submenu['name']] as $permission) {
                        if (!in_array($permission, $existingNames, true)) {
                            $submenu['buttons'][] = [
                                'name' => $permission,
                                'allow' => $allow,
                            ];
                        }
                    }
                }
                unset($submenu);
            }
            unset($module);

            DB::table($table)->where('id', $record->id)->update([
                $column => json_encode($modules),
            ]);
        });
    }

    private function removePermissionsFromJsonColumn(string $table, string $column, array $permissionNames): void
    {
        DB::table($table)->orderBy('id')->each(function ($record) use ($table, $column, $permissionNames) {
            $modules = json_decode($record->{$column}, true);

            if (!is_array($modules)) {
                return;
            }

            foreach ($modules as &$module) {
                if (!isset($module['childs']) || !is_array($module['childs'])) {
                    continue;
                }

                foreach ($module['childs'] as &$submenu) {
                    if (!isset($submenu['buttons']) || !is_array($submenu['buttons'])) {
                        continue;
                    }

                    $submenu['buttons'] = array_values(array_filter(
                        $submenu['buttons'],
                        fn ($button) => !in_array($button['name'] ?? null, $permissionNames, true)
                    ));
                }
                unset($submenu);
            }
            unset($module);

            DB::table($table)->where('id', $record->id)->update([
                $column => json_encode($modules),
            ]);
        });
    }
}
