<?php
namespace App\Console\Commands;

use App\Models\{ActivityLog, Ticket, TicketAdvancedBooked};
use App\Models\Booking\TicketAdvancedBooked as BookingTicketAdvancedBooked;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Log, DB};

class CheckJazzcashPendingPayment extends Command
{
    protected $signature = 'jazzcash:check-pending-payment';

    public function handle(): int
    {
        Log::info('JazzCash Sync: Start');

        try {
            $tickets = Ticket::where([
                    "terminal_id" => 14,
                    "type" => "advance booking",
                    "online_terminal" => 1
                ])
                ->whereNotNull("transaction_id")
                ->get()
                ->groupBy('transaction_id')
                ->map(fn($items) => $items->first());

            foreach ($tickets as $ticket) {
                $jcCheck = confirmJazzcashPendingPayment($ticket->transaction_id);

                if ($jcCheck->status) {
                    $response = $jcCheck->response;

                    DB::transaction(function () use ($ticket, $response) {
                        $jcAmount = ($response->pp_Amount / 100);

                        // 1. Update status to 'booked'
                        Ticket::where("invoice_id", $ticket->invoice_id)->update([
                            'type' => 'booked',
                            'updated_by' => 0,
                            'booked_time' => now(),
                        ]);

                        // 2. Remove from Advanced Table as it is now confirmed
                        BookingTicketAdvancedBooked::where('ticket_id', $ticket->id)->delete();

                        // 3. Log Activity
                        ActivityLog::create([
                            "activity_by" => 0,
                            "message" => "Auto-Confirmed via Server | Invoice: {$ticket->invoice_id} | Amount: {$jcAmount}",
                            "requested_host" => '127.0.0.1',
                            "company_id" => $ticket->company_id
                        ]);

                        ticketConfirmedMessage($ticket->invoice_id);
                        Log::info("Ticket {$ticket->invoice_id} confirmed via automation.");
                    });
                }
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('JazzCash Command Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
