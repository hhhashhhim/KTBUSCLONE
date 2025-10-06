<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\Bus\BusSeatMap;
use App\Models\ActivityLog;
use App\Models\Booking\BookingCancel;
use App\Models\FareClass;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Models\Schedule\TicketClosing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusController extends Controller
{
    public function index()
    {
        $auth_key = "@+_VbdTWAYv4c1kkuIO!NQQupcb@yNw%_I^qNWJ1cp+owvKF35";

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
        $pending_merges = TicketClosing::where('company_id', Auth::user()->company_id)
           ->where(["hide"=>0,"commission_route"=>0])
            ->get()
            ->groupBy('ticket_merge_id')
            ->filter(function ($group) {
                return $group->count() == 1;
            })
            ->count();

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
        $mobile = "923203948283"; //abdul rehma
        $mobile2 = "923143136767"; //hashim sb
        // $mobile2 = "923360111140"; //hashim sb
        // $mobile3 = "923108886288"; // qasim sb
        // $mobile2 = "923333068686";
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

        $response = Http::withHeaders([
            'X-Api-Key' => $auth_key,
        ])->post($url, [
            "session" => $session,
            "message_type" =>  'text',
            "receiver_number" => $mobile,
            "message_body" => $messageConfirmed
        ]);
        $response2 = Http::withHeaders([
            'X-Api-Key' => $auth_key,
        ])->post($url, [
            "session" => $session,
            "message_type" =>  'text',
            "receiver_number" => $mobile2,
            "message_body" => $messageConfirmed
        ]);
        // $response2 = Http::withHeaders([
        //     'X-Api-Key'=>$auth_key,
        // ])->post($url, [
        //     "session" => $session,
        //     "message_type" =>  'text',
        //     "receiver_number" => $mobile3, 
        //     "message_body" => $messageConfirmed
        // ]);

        if (!checkForSubmenu("buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        return Bus::with('addedBy', 'busClass')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }

    public function storeBus(Request $request)
    {
        // $data = ScheduleDetail::where(["company_id"=>Auth::user()->company_id,"schedule_date"=>"2024-02-25","schedule_id"=>330])->get();

        // foreach($data as $single)
        // {
        //     ScheduleDetail::create([
        //         'company_id' => $single->company_id,
        //         'added_by' => $single->added_by,
        //         'schedule_id' => $single->schedule_id,
        //         'departure_id' => $single->departure_id,
        //         'destination_id' => $single->destination_id,
        //         'departure_time' => date('H:i', strtotime($single->departure_time)),
        //         'departure_date' => date('Y-m-d', strtotime($single->departure_date) + 86400),
        //         'schedule_date' => "2024-02-26", // schedule departure date
        //     ]);
        // }
        // return 'helo';

        if (!checkPermissionButtons("add-buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $rules = [
                'busNumber' => ['required', Rule::unique('buses', 'bus_number')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                'fare_class' => 'required|integer',
                //            'chassisNumber' => 'required',
                //            'insuranceNumber' => 'required',
                //            'routePermit' => 'required',
            ];

            $customMessages = [
                'busNumber.required' => 'Bus Number is Required!',
                'busNumber.unique' => 'Bus Number is already exist!',
                'fare_class.required' => 'Bus Class is Required!',
            ];
            $this->validate($request, $rules, $customMessages);
            $bus =  Bus::create([
                'bus_number' => $request->busNumber,
                'fare_class_id' => $request->fare_class,
                'chassis_number' => $request->chassisNumber,
                'insurance_number' => $request->insuranceNumber,
                'route_permit_number' => $request->routePermit,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | added bus $bus->bus_number",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return $bus;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function updateBus(Request $request)
    {
        if (!checkPermissionButtons("edit-buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $rules = [
                'bus_number' => ['required', Rule::unique('buses', 'bus_number')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],
                'fare_class_id' => 'required|integer',
            ];

            $customMessages = [
                'bus_number.required' => 'Bus Number is Required!',
                'bus_number.unique' => 'Bus Number is already exist!',
                'fare_class_id.required' => 'Fare Class is Required!',
            ];
            $this->validate($request, $rules, $customMessages);
            $bus = Bus::where('id', $request->id)->update([
                'bus_number' => $request->bus_number,
                'chassis_number' => $request->chassis_number,
                'insurance_number' => $request->insurance_number,
                'route_permit_number' => $request->route_permit_number,
                'fare_class_id' => $request->fare_class_id,
                'company_id' => Auth::user()->company_id,
                'updated_by' => Auth::user()->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated bus $request->bus_number",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return $bus;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    // public function deleteBus(Request $request)
    // {
    //     return Bus::find($request->id)->delete();
    // }

    public function getBusData(Request $request)
    {
        if (!checkForSubmenu("buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Bus::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();
    }

    public function getBusSchedule(Request $request)
    {
        if (!checkForSubmenu("buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return TicketClosing::where('bus_id', $request->id)->where('company_id', Auth::user()->company_id)->latest()->first(['id', 'schedule_id', 'schedule_date', 'schedule_time']);
    }
    public function busClasses()
    {
        if (!checkForSubmenu("buses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return BusClass::with('addedBy')->orderBy('id')->where(['company_id' => Auth::user()->company_id, "hide" => 0])->get();
    }
}
