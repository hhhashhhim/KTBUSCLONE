<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Booking\BookingCancel;
use App\Models\Schedule\TicketClosing;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count of seats detail';

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
        $auth_key = "@+_VbdTWAYv4c1kkuIO!NQQupcb@yNw%_I^qNWJ1cp+owvKF35";

        // ✅ ensure Auth user exists (for CLI/schedule)
        if (!Auth::check()) {
            // Pick a valid user who belongs to a real company
            $user = User::where('company_id', '>', 0)->first();

            if (!$user) {
                Log::error('No valid user found with a company_id > 0');
                return; // Stop execution if no valid user
            }

            Auth::setUser($user);
            Log::info('User set for scheduled job', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'user_name' => $user->name,
            ]);
        }

        // Now safely get the company_id
        $company_id = Auth::user()->company_id;

        Log::info('Company ID resolved for report', ['company_id' => $company_id]);


        $today = now()->subDays(1)->format("Y-m-d");

        // confirm | reserve | over issue | today | lastday tickets
        $ticketData = Ticket::withTrashed()
            ->where("date", $today)
            ->get();

        // today new customer
        $newCustomer = Ticket::where("date", $today)
            ->whereNotIn('customer_id', function ($query) use ($today) {
                $query->select('customer_id')
                    ->from('tickets')
                    ->whereDate('date', '<', $today);
            })
            ->select('customer_id', 'date')
            ->distinct()
            ->get();

        // today customer repeat
        $oldCustomer = Ticket::where("date", $today)
            ->whereIn('customer_id', function ($query) use ($today) {
                $query->select('customer_id')
                    ->from('tickets')
                    ->whereDate('date', '<', $today);
            })
            ->select('customer_id', 'date')
            ->distinct()
            ->get();

        $cancel_ids = $ticketData->where("date", $today)->where("type", "canceled")->pluck('id');
        Log::info('Pending Merge Check', [
            'company_id' => $company_id,
        ]);

        $pending_merges = TicketClosing::where('company_id', $company_id)
            ->where(function ($q) {
                $q->whereIn('hide', [0, '0'])
                    ->orWhereNull('hide');
            })
            ->where(function ($q) {
                $q->whereIn('commission_route', [0, '0'])
                    ->orWhereNull('commission_route');
            })
            ->whereNull('ticket_merge_id')
            ->count();

        Log::info('Pending Merge Result', [
            'company_id' => $company_id,
            'pending_merges' => $pending_merges,
        ]);





        $today_confirm = $ticketData->where("date", $today)->where("type", "booked")->count();
        $today_reserve = $ticketData->where("date", $today)->where("type", "advance booking")->count();
        $today_confirm_cancel = BookingCancel::whereIn('ticket_id', $cancel_ids)->where("type", "booked")->count();
        $today_reserve_cancel = BookingCancel::whereIn('ticket_id', $cancel_ids)->where("type", "advance booking")->count();
        $today_overissue = $ticketData->where("date", $today)->where("type", "over-issue")->count();
        $today_discount = $ticketData->where("type", "booked")->where("date", $today)->sum(function ($ticket) {
            return $ticket->discount + $ticket->terminal_discount + $ticket->schedule_discount;
        });
        $today_new_customers = $newCustomer->where("date", $today)->count();
        $today_old_customers = $oldCustomer->where("date", $today)->count();
        $today_sale = $ticketData->whereIn('type', ['booked', 'over-issue'])->where("date", $today)->sum(function ($ticket) {
            return $ticket->seat_fare - $ticket->discount;
        });

        $url = "https://whatsapp.sarzone.com/api/send-messages";
        // $mobile = "923203948283"; //abdul rehma
        // $mobile2 = "923360111140"; //hashim sb
        // $mobile3 = "923108886288"; // qasim sb
        // $mobile4 = "923143136767"; // farhan ali
        $session = "Muhammad-Shahzaib_3-sarzone";
        $messageConfirmed = "*Dear Sir following is the report of Kainat Travels for the date of " . date('d M Y', strtotime($today)) . "*

* Total Confirmed Seats : *$today_confirm*
* Total Reserved Seats : *$today_reserve*
* Total Confirmed Cancelled : *$today_confirm_cancel*
* Total Reserved Cancelled : *$today_reserve_cancel*
* Total Overissue Seats : *$today_overissue*
* Total Discount Amount : *$today_discount*
* New Customers : *$today_new_customers*
* Repeated Customers : *$today_old_customers*
* Gross Sale : *$today_sale*
* Pending Merges : *$pending_merges*

This is automated generated report.
(E&EO)
";

        // $response = Http::withHeaders([
        //     'X-Api-Key' => $auth_key,
        // ])->post($url, [
        //     "session" => $session,
        //     "message_type" =>  'text',
        //     "receiver_number" => $mobile,
        //     "message_body" => $messageConfirmed
        // ]);
        // $response2 = Http::withHeaders([
        //     'X-Api-Key' => $auth_key,
        // ])->post($url, [
        //     "session" => $session,
        //     "message_type" =>  'text',
        //     "receiver_number" => $mobile2,
        //     "message_body" => $messageConfirmed
        // ]);
        // $response2 = Http::withHeaders([
        //     'X-Api-Key' => $auth_key,
        // ])->post($url, [
        //     "session" => $session,
        //     "message_type" =>  'text',
        //     "receiver_number" => $mobile3,
        //     "message_body" => $messageConfirmed
        // ]);
        // $response2 = Http::withHeaders([
        //     'X-Api-Key' => $auth_key,
        // ])->post($url, [
        //     "session" => $session,
        //     "message_type" =>  'text',
        //     "receiver_number" => $mobile4,
        //     "message_body" => $messageConfirmed
        // ]);
    }
}
