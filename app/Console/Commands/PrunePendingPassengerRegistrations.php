<?php

namespace App\Console\Commands;

use App\Models\PendingPassengerRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrunePendingPassengerRegistrations extends Command
{
    protected $signature = 'mobile:prune-pending-registrations';
    protected $description = 'Remove expired temporary signups and their tokens; preserve customer records.';

    public function handle()
    {
        if (!Schema::hasTable('pending_passenger_registrations')) { return 0; }
        PendingPassengerRegistration::where('expires_at', '<=', now())->select('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::transaction(function () use ($row) {
                    $pending = PendingPassengerRegistration::whereKey($row->id)->lockForUpdate()->first();
                    if ($pending && $pending->expires_at->lte(now())) {
                        $pending->tokens()->delete();
                        $pending->delete();
                    }
                });
            }
        });
        return 0;
    }
}
