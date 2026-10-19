<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallCallCenter extends Command
{
    protected $signature = 'call-center:install {--force : Run migrations in production}';
    protected $description = 'Run only the Call Center module migrations, preserving unrelated pending migrations';

    public function handle(): int
    {
        $paths = glob(database_path('migrations/*_call_center_*.php'));
        if (!$paths) {
            $this->error('Call Center migration files were not found.');
            return 1;
        }
        sort($paths);
        $result = $this->call('migrate', [
            '--path' => $paths,
            '--realpath' => true,
            '--force' => (bool) $this->option('force'),
        ]);
        if ($result === 0) {
            $this->info('Call Center is installed. Admin roles have all Call Center permissions. Configure other roles under Users > Roles > Permissions.');
        }
        return $result;
    }
}
