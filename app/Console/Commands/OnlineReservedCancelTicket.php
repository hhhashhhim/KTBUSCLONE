<?php

namespace App\Console\Commands;
use App\Models\ActivityLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        $tickets = Ticket::with("terminal:id,name,reservation_cancel")->where(["type"=>"advance booking"])->get();
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
