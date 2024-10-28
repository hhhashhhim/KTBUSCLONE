<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Account\AccountTransaction;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Schedule\Schedule;
use App\Models\Bus\Bus;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Models\OfficeExpense;
use App\Models\TerminalCommission;
use App\Models\Expense\TicketMergeExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{

    public function index(Request $request)
    {
        if(!checkPermissionButtons("add-expense"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $expenses = TicketMergeExpense::where(["ticket_merge_id" => $request->ticket_merge_id, 'company_id' => Auth::user()->company_id])->orderBy('id')->get();
        
        // for sale show at front
        $merge = TicketClosingMerge::where(['company_id' => Auth::user()->company_id, 'schedule_complete' => 1,"id" => $request->ticket_merge_id])
            ->with("closing:id,ticket_merge_id,schedule_id", "closing.schedule:id,name")
            ->with("tickets.elt:id,ticket_id,elt_price","tickets.schedule:id,route_id")
            ->with(["tickets"=>function($q){
                $q->where("type","booked");
                $q->select("id","ticket_merge_id","seat_fare","discount","schedule_id","terminal_id","ticket_closing_id");
            }])
            ->first(["id","schedule_departure_date","schedule_return_date","bus_id"]);


        $merge->seat_fare = $merge->tickets->sum("seat_fare");
        $merge->discount = $merge->tickets->sum("discount");

        // elt amount | commission
        $eltAmount = 0;
        $commission = 0;
        $closingOne = [];
        $closingTwo = [];
        foreach($merge->tickets as $ticket)
        {
            // elt
            if($ticket->elt)
            {
                $eltAmount += $ticket->elt->elt_price;
            }
            // commission
            $terminalCommission = TerminalCommission::where(["terminal_id"=>$ticket->terminal_id,"route_id"=>$ticket->schedule->route_id,"company_id"=>Auth::user()->company_id])->first();
            if($terminalCommission)
            {    
                if($merge->closing[0]->id == $ticket->ticket_closing_id)
                {
                    $closingOne[] = $terminalCommission->id;
                }
                else
                {
                    $closingTwo[] = $terminalCommission->id;
                }
                
                if($terminalCommission->flat_commission == 0)
                    $commission += (($ticket->seat_fare - ($ticket->discount))/100)*$terminalCommission->percentage_commission;
                else
                {
                    $commission += $terminalCommission->flat_commission;
                }
                // kt adjustment commission
                $commission += (($ticket->seat_fare - $ticket->discount)/100)*$terminalCommission->adjustment_commission;
            }
            else
            {
                $commission += 0;
            }
                
        }
        
        $commission += TerminalCommission::whereIn("id",array_unique($closingOne))->get()->sum("fix_commission");
        $commission += TerminalCommission::whereIn("id",array_unique($closingTwo))->get()->sum("fix_commission");
        
        $merge->elt += $eltAmount;
        $merge->commission += (int)$commission;

        // for add cancelation charges into the sale
        $cancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                'ticket_merge_id' => $merge->id,
                'type' => "canceled",
            ])
            ->with("cancel_ticket:id,ticket_id,percentage")
            ->get(["id","seat_fare","discount"]);

    
        $refundAmount = 0;
        $cancelTicket->map(function($item) use (&$refundAmount){
            if($item->cancel_ticket)
            {
                $refundAmount += (($item->seat_fare - $item->discount) / 100) * $item->cancel_ticket->percentage;
            }
        });

        $merge->refund += $refundAmount;
        
        $checkClosing = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"posting_id"=>$request->ticket_merge_id])->first();
        return [
            "expenses" => $expenses,
            "sale" => $merge->seat_fare - $merge->discount - $merge->commission + $merge->elt + $merge->refund,
            "closing" => $checkClosing ? true : false,
        ];
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-expense"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "ticket_merge_id" => 'required',
                    "category" => 'required',
                    "amount" => 'required',
                ], [
                        "category.required" => "Category is  Required",
                        "amount.required" => "Expenses Amount  is Required",
                    ]
                );

                TicketMergeExpense::where("ticket_merge_id", $request->ticket_merge_id)->delete();
                $i = 0;
                foreach ($request->category as $key => $value) {
                    TicketMergeExpense::create([
                        'ticket_merge_id' => $request->ticket_merge_id,
                        'expense_category_id' => $request->category[$key],
                        'description' => $request->description[$key],
                        'amount' => $request->amount[$key],
                        'paid' => isset($request->paid[$key]) ? $request->paid[$key] : $request->amount[$key],
                        'ledger' => isset($request->ledger[$key]) ? $request->ledger[$key] : false,
                        'invoice' => "exp-".++$i.'-'.$request->ticket_merge_id,
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);

                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated expense against merge id (".$request->ticket_merge_id.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }


    public function dailySummery(Request $request)
    {
        if(!checkPermissionButtons("add-expense"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $closing_pair = TicketClosing::with("schedule")->where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $request->ticket_merge_id])->get();
        $data = (object)[];
        
        $data->schedule_start = Ticket::withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[0]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[0]->id)->with('terminal:id,name')->get()->groupBy(['terminal_id']);

        $data->schedule_return = Ticket:: withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[1]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[1]->id)->with('terminal:id,name')->get()->groupBy(['terminal_id']);
    
        $data->expense = TicketMergeExpense::where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $request->ticket_merge_id])->with("expense_category:id,name")->get();

        // get bus number
        $busId = TicketClosingMerge::where("id", $request->ticket_merge_id)->first()->bus_id;
        $singleData = (object)[];
        $singleData->bus_number = Bus::where(["company_id" => Auth::user()->company_id, "id" => $busId])->first()->bus_number;
        // get route both side
        $schedule_ids = TicketClosing::where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $request->ticket_merge_id])->pluck('schedule_id');
        $schedule = Schedule::where(["company_id" => Auth::user()->company_id])->whereIn("id", $schedule_ids)->with("route")->get();
        $singleData->city_one = explode("-", $schedule[0]->route->name)[0];
        $singleData->city_two = explode("-", $schedule[1]->route->name ?? $schedule[0]->route->name)[0];

        // refund amount
        $cancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                'ticket_merge_id' => $request->ticket_merge_id,
                'type' => "canceled",
            ])
            ->with("cancel_ticket:id,ticket_id,percentage","terminal:id,name")
            ->get(["id","seat_fare","discount","terminal_id"])->groupBy("terminal_id");

        $refundTerminal = [];
        $cancelTicket->map(function($single) use (&$refundTerminal){
            
            $refundAmount = 0;
            $single->map(function($ticket) use (&$refundAmount){
            
                if($ticket->cancel_ticket)
                {
                    $refundAmount += (($ticket->seat_fare - $ticket->discount) / 100) * $ticket->cancel_ticket->percentage;
                }
            });
            $singleTerminal = [];
            $singleTerminal["terminal"] = $single[0]->terminal->name;
            $singleTerminal["amount"] = $refundAmount;

            $refundTerminal[] = $singleTerminal;
        });
        

        return view('reports.dailySaleReport', [
            "singleData" => $singleData,
            "data" => $data,
            "refundTerminal" => $refundTerminal
        ]);
    }

    public function officeExpenses(Request $request)
    {
        if(!checkForSubmenu("expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return OfficeExpense::where(['company_id' => Auth::user()->company_id])->latest("closing_date")->get();
    }

    public function officeExpenStore(Request $request)
    {
        if(!checkForSubmenu("expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $rules = [
                'date' => 'required',
                'amount' => 'required | integer',
                'narration' => 'required',
            ];

            $customMessages = [
                'date.required' => 'Closing Date is Required!',
                'amount.required' => 'Expenses Amount is Required!',
                'narration.required' => 'Expenses Narration is Required!',
            ];
            $this->validate($request, $rules, $customMessages);
            
            OfficeExpense::create([
                'closing_date' => $request->date,
                'amount' => $request->amount,
                'narration' => $request->narration,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | added office expense of closing date (".$request->date.")",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }
    
    public function officeExpenUpdate(Request $request)
    {
        if(!checkForSubmenu("expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $rules = [
                'closing_date' => 'required',
                'amount' => 'required | integer',
                'narration' => 'required',
            ];

            $customMessages = [
                'closing_date.required' => 'Closing Date is Required!',
                'amount.required' => 'Expenses Amount is Required!',
                'narration.required' => 'Expenses Narration is Required!',
            ];

            $this->validate($request, $rules, $customMessages);
            
            OfficeExpense::where("id",$request->id)->update([
                'closing_date' => $request->closing_date,
                'amount' => $request->amount,
                'narration' => $request->narration,
                'updated_by' => Auth::user()->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | updated office expense of closing date (".$request->closing_date.")",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }
}
