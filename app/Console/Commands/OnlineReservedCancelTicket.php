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
        $tickets = Ticket::where(["type"=>"advance booking","online_terminal"=>1])->where("created_at",'<',Carbon::now()->subHours(2))->get();
        try {
            DB::beginTransaction();
            foreach($tickets as $ticket)
            {
                $ticket->update([
                    'type' => 'canceled',
                ]);
                BookingCancel::create([
                    'company_id' => $ticket->company_id,
                    'ticket_id' => $ticket->id,
                    'percentage' => 0,
                    'reason' => "auto cancel",
                    'added_by' => 0,
                ]);
                $ticket->delete();
            }
            ActivityLog::create([
                "activity_by" => 0,
                "message" => "Auto | canceled booking. tickets (".implode(",",$tickets->pluck('id')->toArray()).")",
                "requested_host" => "0:0",
                "company_id" => $ticket->company_id
            ]);
            DB::commit();
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }
}
