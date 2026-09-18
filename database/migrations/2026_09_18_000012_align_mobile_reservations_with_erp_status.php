<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlignMobileReservationsWithErpStatus extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_payments')) { return; }

        $reservations = function () {
            return DB::table('tickets')->where('type', 'pending booking')->whereNull('deleted_at')
                ->whereExists(function ($payment) {
                    $payment->selectRaw('1')->from('mobile_payments')
                        ->whereColumn('mobile_payments.invoice_id', 'tickets.invoice_id')
                        ->whereColumn('mobile_payments.company_id', 'tickets.company_id')
                        ->where('mobile_payments.status', 'pending');
                });
        };

        $reservations()->select('tickets.id')->chunkById(200, function ($rows) use ($reservations) {
            DB::transaction(function () use ($rows, $reservations) {
                // Recheck under a row lock so a concurrent staff cancellation is preserved.
                $ids = $reservations()->whereIn('tickets.id', $rows->pluck('id'))
                    ->lockForUpdate()->pluck('tickets.id');
                DB::table('tickets')->whereIn('id', $ids)->update(['type' => 'advance booking']);
                foreach (['ticket_advanced_bookeds', 'ticket_is_partials'] as $table) {
                    DB::table($table)->whereIn('ticket_id', $ids)->where('type', 'pending booking')
                        ->when($table === 'ticket_is_partials', function ($query) { $query->whereNull('deleted_at'); })
                        ->update(['type' => 'advance booking']);
                }
            });
        });
    }

    public function down()
    {
        // Data repair only: never reintroduce incompatible statuses or revive seats.
    }
}
