<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckJazzcashPendingPayment extends Command
{
    protected $signature = 'jazzcash:check-pending-payment';
    protected $description = 'Check JazzCash pending payment and process DB actions if confirmed';

    public function handle(): int
{
    Log::info('JazzCash Sync: Start');

    try {
        // Fetch unique transaction IDs to check
        $tickets = Ticket::where([
                "terminal_id" => 14,
                "type" => "advance booking",
                "online_terminal" => 1
            ])
            ->whereNotNull("transaction_id")
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('transaction_id')
            ->map(fn($items) => $items->first())
            ->values();

        foreach ($tickets as $ticket) {
            // 1. External API call (Keep this OUTSIDE the transaction)
            $jazzcashRespons = confirmJazzcashPendingPayment($ticket->transaction_id);

            if (!$jazzcashRespons->status) {
                Log::info("Payment not confirmed for TXN: {$ticket->transaction_id}");
                continue;
            }

            $response = $jazzcashRespons->response;

            // 2. Local Database Operations (INSIDE individual transactions)
            DB::transaction(function () use ($ticket, $response) {
                // Fare validation
                $checkTotal = Ticket::where("invoice_id", $ticket->invoice_id)
                    ->where(['company_id' => $ticket->company_id, "type" => "advance booking"])
                    ->selectRaw('(SUM(seat_fare) - SUM(discount)) as amount')
                    ->first()->amount;

                $jcAmount = ($response->pp_Amount / 100);

                if ($jcAmount != $checkTotal) {
                    Log::warning("Amount mismatch for TXN: {$ticket->transaction_id}. Expected: $checkTotal, Got: $jcAmount");
                    return; // Exits the closure, effectively skipping this ticket
                }

                // Update Ticket
                Ticket::where("invoice_id", $ticket->invoice_id)->update([
                    'type' => 'booked',
                    'updated_by' => 0,
                    'booked_time' => now(),
                ]);

                // Log Activity
                ActivityLog::create([
                    "activity_by" => 0,
                    "message" => "Server Automation | Confirmed Invoice: {$ticket->invoice_id} | Amount: {$jcAmount}",
                    "requested_host" => 0,
                    "company_id" => $ticket->company_id
                ]);

                // Send Message
                ticketConfirmedMessage($ticket->invoice_id);

                Log::info("Ticket {$ticket->invoice_id} updated successfully.");
            });
        }

        return self::SUCCESS;
    } catch (\Throwable $e) {
        // Critical: The DB::transaction() helper auto-rolls back,
        // but if you use manual BeginTransaction, you MUST call rollBack() here.
        Log::error('JazzCash Sync Failed: ' . $e->getMessage());
        return self::FAILURE;
    }
}
}
