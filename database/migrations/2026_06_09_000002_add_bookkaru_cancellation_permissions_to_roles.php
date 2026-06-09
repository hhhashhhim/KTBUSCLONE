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

            $modules = $this->addBookkaruCancellationSubmenu($modules, true);

            DB::table('companies')->where('id', $company->id)->update([
                'modules' => json_encode($modules),
            ]);
        });

        DB::table('roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true);

            if (!is_array($permissions)) {
                return;
            }

            $permissions = $this->addBookkaruCancellationSubmenu($permissions, false);

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

            $modules = $this->removeBookkaruCancellationSubmenu($modules);

            DB::table('companies')->where('id', $company->id)->update([
                'modules' => json_encode($modules),
            ]);
        });

        DB::table('roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true);

            if (!is_array($permissions)) {
                return;
            }

            $permissions = $this->removeBookkaruCancellationSubmenu($permissions);

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }

    private function addBookkaruCancellationSubmenu(array $permissions, bool $allow): array
    {
        foreach ($permissions as &$menu) {
            if (($menu['name'] ?? null) !== 'ticketing') {
                continue;
            }

            $menu['childs'] = $menu['childs'] ?? [];

            $bookkaruIndex = null;
            foreach ($menu['childs'] as $index => $submenu) {
                if (($submenu['name'] ?? null) === 'bookkaru-cancellation') {
                    $bookkaruIndex = $index;
                    break;
                }
            }

            $bookkaruSubmenu = [
                'name' => 'bookkaru-cancellation',
                'allow' => $allow,
                'buttons' => [
                    ['name' => 'view-request', 'allow' => $allow],
                    ['name' => 'approve-request', 'allow' => $allow],
                    ['name' => 'reject-request', 'allow' => $allow],
                ],
            ];

            if ($bookkaruIndex === null) {
                $menu['childs'][] = $bookkaruSubmenu;
                continue;
            }

            $menu['childs'][$bookkaruIndex]['buttons'] = $menu['childs'][$bookkaruIndex]['buttons'] ?? [];
            $existingButtons = array_column($menu['childs'][$bookkaruIndex]['buttons'], 'name');

            foreach ($bookkaruSubmenu['buttons'] as $button) {
                if (!in_array($button['name'], $existingButtons, true)) {
                    $menu['childs'][$bookkaruIndex]['buttons'][] = $button;
                }
            }
        }
        unset($menu);

        return $permissions;
    }

    private function removeBookkaruCancellationSubmenu(array $permissions): array
    {
        foreach ($permissions as &$menu) {
            if (($menu['name'] ?? null) !== 'ticketing' || !isset($menu['childs']) || !is_array($menu['childs'])) {
                continue;
            }

            $menu['childs'] = array_values(array_filter($menu['childs'], function ($submenu) {
                return ($submenu['name'] ?? null) !== 'bookkaru-cancellation';
            }));
        }
        unset($menu);

        return $permissions;
    }
}
