<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddReportFilterPermissionsToRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissionsBySubmenu = [
            'confirm-cancel' => [
                'confirm-cancel-terminal-filter',
                'confirm-cancel-user-filter',
                'confirm-cancel-route-filter',
            ],
            'sales' => [
                'terminal-filter',
                'user-filter',
                'route-filter',
            ],
            'over-issue' => [
                'over-issue-terminal-filter',
                'over-issue-user-filter',
                'over-issue-route-filter',
            ],
        ];

        DB::table('roles')->orderBy('id')->each(function ($role) use ($permissionsBySubmenu) {
            $permissions = json_decode($role->permissions, true);
            if (!is_array($permissions)) {
                return;
            }

            foreach ($permissions as &$menu) {
                if (!isset($menu['childs']) || !is_array($menu['childs'])) {
                    continue;
                }

                foreach ($menu['childs'] as &$submenu) {
                    if (!isset($permissionsBySubmenu[$submenu['name'] ?? null])) {
                        continue;
                    }

                    $submenu['buttons'] = $submenu['buttons'] ?? [];
                    $existingNames = array_column($submenu['buttons'], 'name');
                    foreach ($permissionsBySubmenu[$submenu['name']] as $permission) {
                        if (!in_array($permission, $existingNames, true)) {
                            $submenu['buttons'][] = ['name' => $permission, 'allow' => false];
                        }
                    }
                }
                unset($submenu);
            }
            unset($menu);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permissionNames = [
            'confirm-cancel-terminal-filter',
            'confirm-cancel-user-filter',
            'confirm-cancel-route-filter',
            'user-filter',
            'route-filter',
            'over-issue-terminal-filter',
            'over-issue-user-filter',
            'over-issue-route-filter',
        ];

        DB::table('roles')->orderBy('id')->each(function ($role) use ($permissionNames) {
            $permissions = json_decode($role->permissions, true);
            if (!is_array($permissions)) {
                return;
            }

            foreach ($permissions as &$menu) {
                if (!isset($menu['childs']) || !is_array($menu['childs'])) {
                    continue;
                }

                foreach ($menu['childs'] as &$submenu) {
                    if (!isset($submenu['buttons'])) {
                        continue;
                    }

                    $submenu['buttons'] = array_values(array_filter(
                        $submenu['buttons'],
                        fn ($button) => !in_array($button['name'] ?? null, $permissionNames, true)
                    ));
                }
                unset($submenu);
            }
            unset($menu);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }
}
