<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBookkaruCancellationPermissionsToRoles extends Migration
{
    public function up()
    {
        DB::table('companies')->orderBy('id')->each(function ($company) {
            $modules = json_decode($company->modules, true);

            if (!is_array($modules)) {
                return;
            }

            $modules = $this->addOnlineTerminalCancellationSubmenu($modules, true);

            DB::table('companies')->where('id', $company->id)->update([
                'modules' => json_encode($modules),
            ]);
        });

        DB::table('roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true);

            if (!is_array($permissions)) {
                return;
            }

            $permissions = $this->addOnlineTerminalCancellationSubmenu($permissions, false);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }

    public function down()
    {
        DB::table('companies')->orderBy('id')->each(function ($company) {
            $modules = json_decode($company->modules, true);

            if (!is_array($modules)) {
                return;
            }

            $modules = $this->removeOnlineTerminalCancellationSubmenu($modules);

            DB::table('companies')->where('id', $company->id)->update([
                'modules' => json_encode($modules),
            ]);
        });

        DB::table('roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true);

            if (!is_array($permissions)) {
                return;
            }

            $permissions = $this->removeOnlineTerminalCancellationSubmenu($permissions);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }

    private function addOnlineTerminalCancellationSubmenu(array $permissions, bool $allow): array
    {
        foreach ($permissions as &$menu) {
            if (($menu['name'] ?? null) !== 'ticketing') {
                continue;
            }

            $menu['childs'] = $menu['childs'] ?? [];

            $onlineTerminalIndex = null;
            foreach ($menu['childs'] as $index => $submenu) {
                if (($submenu['name'] ?? null) === 'online-terminal-cancellation') {
                    $onlineTerminalIndex = $index;
                    break;
                }
            }

            $onlineTerminalSubmenu = [
                'name' => 'online-terminal-cancellation',
                'allow' => $allow,
                'buttons' => [
                    ['name' => 'view-request', 'allow' => $allow],
                    ['name' => 'approve-request', 'allow' => $allow],
                    ['name' => 'reject-request', 'allow' => $allow],
                ],
            ];

            if ($onlineTerminalIndex === null) {
                $menu['childs'][] = $onlineTerminalSubmenu;
                continue;
            }

            $menu['childs'][$onlineTerminalIndex]['buttons'] = $menu['childs'][$onlineTerminalIndex]['buttons'] ?? [];
            $existingButtons = array_column($menu['childs'][$onlineTerminalIndex]['buttons'], 'name');

            foreach ($onlineTerminalSubmenu['buttons'] as $button) {
                if (!in_array($button['name'], $existingButtons, true)) {
                    $menu['childs'][$onlineTerminalIndex]['buttons'][] = $button;
                }
            }
        }
        unset($menu);

        return $permissions;
    }

    private function removeOnlineTerminalCancellationSubmenu(array $permissions): array
    {
        foreach ($permissions as &$menu) {
            if (($menu['name'] ?? null) !== 'ticketing' || !isset($menu['childs']) || !is_array($menu['childs'])) {
                continue;
            }

            $menu['childs'] = array_values(array_filter($menu['childs'], function ($submenu) {
                return ($submenu['name'] ?? null) !== 'online-terminal-cancellation';
            }));
        }
        unset($menu);

        return $permissions;
    }
}
