<?php

namespace App\Console\Commands;
use App\Models\ActivityLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Booking\BookingCancel;
use Illuminate\Console\Command;

class OnlineReservedCancelTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reserved:cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this proccess will free the tickets that reserved from online terminal and did not get a payment';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $query = Ticket::with("terminal:id,name,reservation_cancel")->where(["type"=>"advance booking"]);
        // Mobile online reservations use the ERP status too, but their payment
        // reconciler must verify receipts and return wallet points before release.
        // Counter and other legacy reservations keep their existing expiry path.
        if (Schema::hasTable('mobile_payments')) {
            $query->whereNotExists(function ($payment) {
                $payment->selectRaw('1')->from('mobile_payments')
                    ->whereColumn('mobile_payments.invoice_id', 'tickets.invoice_id')
                    ->whereColumn('mobile_payments.company_id', 'tickets.company_id');
            });
        }
        $tickets = $query->get();
        try {
            DB::beginTransaction();
            foreach($tickets as $ticket)
            {
                if($ticket->terminal->reservation_cancel == null || $ticket->terminal->reservation_cancel == 0) 
                {
                    continue;
                }
                if($ticket->created_at < Carbon::now()->subMinutes($ticket->terminal->reservation_cancel))
                {
                    $type = $ticket->type;
                    $ticket->update([
                        'type' => 'canceled',
                    ]);
                    BookingCancel::create([
                        'company_id' => $ticket->company_id,
                        'ticket_id' => $ticket->id,
                        'percentage' => 0,
                        'reason' => "auto cancel",
                        'type' => $type,
                        'added_by' => 0,
                    ]);
                    ActivityLog::create([
                        "activity_by" => 0,
                        "message" => "Auto | canceled booking. tickets".$ticket->id,
                        "requested_host" => "0:0",
                        "company_id" => $ticket->company_id
                    ]);
                    $ticket->delete();
                }
            }
            DB::commit();
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }
}
