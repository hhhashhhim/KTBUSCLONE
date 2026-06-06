<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAllUserFilterPermissionToRoles extends Migration
{
    private const TARGET_SUBMENUS = [
        'confirm-cancel',
        'sales',
        'terminal-sale',
    ];

    private const PERMISSION = 'all-user-filter';

    public function up()
    {
        DB::table('roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true);
            if (!is_array($permissions)) {
                return;
            }

            foreach ($permissions as &$menu) {
                if (!isset($menu['childs']) || !is_array($menu['childs'])) {
                    continue;
                }

                foreach ($menu['childs'] as &$submenu) {
                    if (!in_array($submenu['name'] ?? null, self::TARGET_SUBMENUS, true)) {
                        continue;
                    }

                    $submenu['buttons'] = $submenu['buttons'] ?? [];
                    $existingNames = array_column($submenu['buttons'], 'name');

                    if (!in_array(self::PERMISSION, $existingNames, true)) {
                        $submenu['buttons'][] = [
                            'name' => self::PERMISSION,
                            'allow' => false,
                        ];
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

    public function down()
    {
        DB::table('roles')->orderBy('id')->each(function ($role) {
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
                        fn ($button) => ($button['name'] ?? null) !== self::PERMISSION
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
