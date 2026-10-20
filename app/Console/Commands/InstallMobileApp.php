<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallMobileApp extends Command
{
    protected $signature = 'mobile-app:install {--force : Run migrations in production}';
    protected $description = 'Install only the Mobile App administration tables and role privileges';

    public function handle(): int
    {
        $paths = [database_path('migrations/2026_10_20_000001_create_mobile_app_admin_tables.php'),
            database_path('migrations/2026_10_20_000002_add_mobile_app_permissions.php'),
            database_path('migrations/2026_10_20_000004_add_mobile_user_controls.php'),
            database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php'),
            database_path('migrations/2026_10_20_000008_create_mobile_notifications.php'),
            database_path('migrations/2026_10_20_000009_add_mobile_notification_permissions.php'),
            database_path('migrations/2026_10_20_000010_add_mobile_wallet_permission.php')];
        if (Schema::hasTable('mobile_fleet_media')) {
            $paths[] = database_path('migrations/2026_10_20_000003_add_mobile_media_placements.php');
        } else {
            $this->warn('Image uploads remain unavailable until the mobile fleet-media table is installed. Rerun this installer afterwards.');
        }
        foreach ($paths as $path) {
            if (!is_file($path)) { $this->error('A Mobile App administration migration is missing.'); return 1; }
        }
        $result = $this->call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => (bool) $this->option('force')]);
        if ($result === 0) {
            $this->info('Mobile App administration is installed. Configure staff access under Roles. Existing passenger/mobile tables are required for live monitoring.');
        }
        return $result;
    }
}
