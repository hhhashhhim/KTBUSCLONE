<?php

namespace App\Console\Commands;

use App\Models\MobileBookingQuote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneMobileBookingQuotes extends Command
{
    protected $signature = 'mobile:prune-booking-quotes';
    protected $description = 'Remove abandoned fare quotes while retaining booked passenger snapshots';

    public function handle(): int
    {
        if (!Schema::hasTable('mobile_booking_quotes')) { return 0; }
        MobileBookingQuote::whereNull('used_at')->where('expires_at', '<', now()->subDay())
            ->select('id')->chunkById(500, function ($quotes) {
                MobileBookingQuote::whereIn('id', $quotes->pluck('id'))->whereNull('used_at')
                    ->where('expires_at', '<', now()->subDay())->delete();
            });
        return 0;
    }
}
