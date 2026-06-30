<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameBookkaruCancellationPermissionsToOnlineTerminal extends Migration
{
    public function up()
    {
        $this->renamePermissionKey('companies', 'modules', 'bookkaru-cancellation', 'online-terminal-cancellation');
        $this->renamePermissionKey('roles', 'permissions', 'bookkaru-cancellation', 'online-terminal-cancellation');
    }

    public function down()
    {
        $this->renamePermissionKey('companies', 'modules', 'online-terminal-cancellation', 'bookkaru-cancellation');
        $this->renamePermissionKey('roles', 'permissions', 'online-terminal-cancellation', 'bookkaru-cancellation');
    }

    private function renamePermissionKey(string $table, string $column, string $from, string $to): void
    {
        DB::table($table)->orderBy('id')->each(function ($record) use ($table, $column, $from, $to) {
            $permissions = json_decode($record->{$column}, true);

            if (!is_array($permissions)) {
                return;
            }

            $changed = false;
            foreach ($permissions as &$menu) {
                if (($menu['name'] ?? null) !== 'ticketing' || empty($menu['childs']) || !is_array($menu['childs'])) {
                    continue;
                }

                foreach ($menu['childs'] as &$submenu) {
                    if (($submenu['name'] ?? null) === $from) {
                        $submenu['name'] = $to;
                        $changed = true;
                    }
                }
                unset($submenu);
            }
            unset($menu);

            if ($changed) {
                DB::table($table)->where('id', $record->id)->update([
                    $column => json_encode($permissions),
                ]);
            }
        });
    }
}
