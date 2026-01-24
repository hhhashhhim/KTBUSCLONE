<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Account\AccountHead;
use App\Models\Account\AccountTransaction;
use App\Models\Account\Bank;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Hrm\Employee\Employee;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalCommission;
use App\Models\ActivityLog;
use App\Models\Expense\TicketMergeExpense;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\TicketClosingShortage;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleClosingController extends Controller
{
    public function closing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();

        // 🔹 Static data
        $buses = Bus::where('company_id', $user->company_id)
            ->orderBy('id')
            ->get();

        $hosts = Employee::where([
            'employee_type' => 2,
            'company_id' => $user->company_id,
            'hide' => 0
        ])
            ->where('user_id', '!=', 0)
            ->get(['user_id', 'name', 'cnic']);

        $drivers = Employee::where([
            'employee_type' => 1,
            'company_id' => $user->company_id,
            'hide' => 0
        ])->get(['id', 'user_id', 'name', 'cnic']);

        // 🔹 Closings query with filters
        $closingsQuery = TicketClosing::where('company_id', $user->company_id)
            ->with(
                'bus:id,bus_number',
                'schedule:id,name,route_id',
                'schedule.route:id,name'
            );

        // 🔸 Bus filter
        if ($request->from_date) {
            $closingsQuery->whereDate('schedule_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $closingsQuery->whereDate('schedule_date', '<=', $request->to_date);
        }


        $closings = $closingsQuery
            ->get()
            ->groupBy('ticket_merge_id')
            ->filter(fn($group) => $group->count() == 2);

        return [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "closings" => $closings
        ];
    }


    public function unclosing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $query = TicketClosing::where('company_id', Auth::user()->company_id)
            ->with(
                "bus:id,bus_number",
                "schedule:id,name,route_id",
                "schedule.route:id,name"
            )
            ->where([
                "hide" => 0,
                "commission_route" => 0
            ]);
        $buses = Bus::where('company_id', Auth::user()->company_id)
            ->select('id', 'bus_number')
            ->orderBy('bus_number')
            ->get();
        $buses = Bus::where('company_id', Auth::user()->company_id)
            ->select('id', 'bus_number')
            ->orderBy('bus_number')
            ->get();
        // ✅ Bus filter
        if ($request->bus_number) {
            $query->where('bus_id', $request->bus_number);
        }

        // ✅ Date range filter
        if ($request->from_date) {
            $query->whereDate('schedule_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('schedule_date', '<=', $request->to_date);
        }

        $closings = $query
            ->orderBy('bus_id')
            ->get()
            ->groupBy('ticket_merge_id')
            ->filter(function ($group) {
                return $group->count() == 1;
            });

        return response()->json([
            "closings" => $closings,
            "buses" => $buses
        ]);
    }


    public function commissionClosing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();

        // Base query
        $query = TicketClosing::where('company_id', $user->company_id)
            ->with(
                "bus:id,bus_number",
                "schedule:id,name,route_id",
                "schedule.route:id,name"
            )
            ->with(["account_transaction" => function ($q) {
                $q->where("posting_type", "ticket_closing");
            }])
            ->where([
                "hide" => 0,
                "commission_route" => 1
            ]);

        // 🔹 Filters
        if ($request->bus_number) {
            $query->where('bus_id', $request->bus_number);
        }

        if ($request->from_date) {
            $query->whereDate('schedule_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('schedule_date', '<=', $request->to_date);
        }

        // Get results
        $closings = $query->get()
            ->groupBy('ticket_merge_id')
            ->filter(fn($group) => $group->count() == 1);

        // Get buses list
        $buses = Bus::where('company_id', $user->company_id)
            ->select('id', 'bus_number')
            ->orderBy('bus_number')
            ->get();

        return response()->json([
            "closings" => $closings,
            "buses" => $buses
        ]);
    }


    public function commissionClosingStore(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $request->validate([
            'ledgers' => ['required'],
            'credits' => ['required'],
            'debits' => ['required'],
            'narrations' => ['required'],
        ]);

        try {
            DB::beginTransaction();


            $document = AccountTransaction::where(["company_id" => Auth::user()->company_id])
                ->where("type", "JV")
                ->orderBy("document_id", "DESC")
                ->first();
            $document_id = $document ? $document->document_id + 1 : 1;


            foreach ($request->ledgers as $i => $value) {
                AccountTransaction::create([
                    'terminal_id' => $request->terminal,
                    'account_head_id' => $request->ledgers[$i],
                    'other_account_head_id' => $request->ledgers[$i + 1] ?? $request->ledgers[$i],
                    'credit' => $request->credits[$i] > 0 ? $request->credits[$i] : 0,
                    'debit' => $request->credits[$i] > 0 ? 0 : $request->debits[$i],
                    'document_id' => $document_id,
                    'type' => "JV",
                    'narration' => strtoupper($request->narrations[$i]),
                    'posting_type' => 'ticket_closing',
                    'posting_id' => $request->closeId,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | Added Transaction JV-" . $document_id,
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function hideUnclosing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $unclosing = TicketClosing::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name . " | unclosing deleted ($unclosing->id)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $unclosing->update([
            "hide" => 1
        ]);
    }

    public function revertUnclosing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $unclosing = TicketClosing::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name . " | unclosing revert ($unclosing->id)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $unclosing->update([
            "hide" => 0
        ]);
    }

    public function unclosingData(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $mergeIds = $request->mergeIds;

        // Get closing pairs with schedule in a single query
          $closingPairsOne = TicketClosing::with('schedule:id,route_id')
            ->where('company_id', $companyId)
            ->where('ticket_merge_id', $mergeIds[0])
            ->first();

        $closingPairsTwo = TicketClosing::with('schedule:id,route_id', 'schedule.route')
            ->where('company_id', $companyId)
            ->where('ticket_merge_id', $mergeIds[1])
            ->first();
        
        $closingPairs = TicketClosing::with('schedule:id,route_id','schedule.route')
            ->where('company_id', $companyId)
            ->whereIn('ticket_merge_id', $mergeIds)
            ->get();

        if ($closingPairs->count() < 2) {
            return response()->json(['error' => 'Invalid merge pair data'], 400);
        }

        // Preload route IDs
        $routeIdStart  = $closingPairsOne->schedule->route_id;
        $routeIdReturn = $closingPairsTwo->schedule->route_id;

        // Fetch tickets
        $tickets = Ticket::withTrashed()
            ->where('company_id', $companyId)
            ->whereIn('ticket_closing_id', [$closingPairsOne->id, $closingPairsTwo->id])
            ->whereIn('type', ['booked', 'over-issue', 'canceled'])
            ->with([
                'elt',
                'cancel_ticket',
                'terminal:id,name,recovery_method',
                'commission' => function ($q) use ($routeIdStart, $routeIdReturn) {
                    $q->whereIn('route_id', [$routeIdStart, $routeIdReturn]);
                },
            ])
            ->get();

        // Group tickets by schedule
        $data = (object)[];
        $data->schedule_start  = $tickets->where('ticket_closing_id', $closingPairsOne->id)->groupBy('terminal_id');
        $data->schedule_return = $tickets->where('ticket_closing_id', $closingPairsTwo->id)->groupBy('terminal_id');

        // Expenses
        $data->expense = TicketMergeExpense::where('company_id', $companyId)
            ->where('ticket_merge_id', $request->ticket_merge_id)
            ->with('expense_category:id,name')
            ->get();

        // Bus number
        $busId = $closingPairs->first()->bus_id;
        $singleData = (object)[
            'bus_number' => Bus::where('id', $busId)->where('company_id', $companyId)->value('bus_number')
        ];

        // Schedule routes
        $scheduleIds = $closingPairs->pluck('schedule_id');
        $routes = Schedule::with('route:id,name')->where('company_id', $companyId)->whereIn('id', $scheduleIds)->get();

        $singleData->city_one = explode("-", $closingPairsOne->schedule->route->name)[0];
        $singleData->city_two = explode("-", $closingPairsTwo->schedule->route->name)[0];

        // Refund calculations
        $cancelTickets = Ticket::onlyTrashed()
            ->where('company_id', $companyId)
            ->whereIn('ticket_merge_id', $mergeIds)
            ->where('type', 'canceled')
            ->with(['cancel_ticket:id,ticket_id,percentage', 'terminal:id,name'])
            ->get(['id', 'seat_fare', 'discount', 'terminal_id'])
            ->groupBy('terminal_id');

        $refundTerminal = [];
        foreach ($cancelTickets as $terminalId => $ticketsGroup) {
            $refundAmount = $ticketsGroup->sum(function ($ticket) {
                return $ticket->cancel_ticket
                    ? (($ticket->seat_fare - $ticket->discount) * $ticket->cancel_ticket->percentage) / 100
                    : 0;
            });

            $refundTerminal[] = [
                'terminal' => $ticketsGroup[0]->terminal->name,
                'amount'   => $refundAmount,
            ];
        }

        $banks = Bank::where('banks.company_id', $companyId)
            ->join('account_heads', 'banks.account_head_id', 'account_heads.id')
            ->select('account_heads.*', 'account_heads.name as text')
            ->get();

        $data->refund = $refundTerminal;
        $data->singleData = $singleData;

        // ✅ Add mergeIds and busIds to the response
        $response = [
            'banks'       => $banks,
            'data'        => $data,
            'mergeIds'    => $mergeIds,
            'startRoute'  => $routeIdStart,
            'returnRoute' => $routeIdReturn,
            'busIds'      => $closingPairs->pluck('bus_id')->unique()->values(), // return unique bus IDs
        ];

        return $response;
    }



    public function spareUnclosing(Request $request)
    {
        if (!checkForSubmenu("closing")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();
        $buses = Bus::where('company_id', Auth::user()->company_id)
            ->select('id', 'bus_number')
            ->orderBy('bus_number')
            ->get();
        // Base query with relationships
        $closingsQuery = TicketClosing::where('company_id', $user->company_id)
            ->with(
                "bus:id,bus_number",
                "schedule:id,name,route_id",
                "schedule.route:id,name"
            )
            ->where("hide", 1);

        // 🔹 Apply filters
        if ($request->bus_number) {
            $closingsQuery->where('bus_id', $request->bus_number);
        }

        if ($request->from_date) {
            $closingsQuery->whereDate('schedule_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $closingsQuery->whereDate('schedule_date', '<=', $request->to_date);
        }

        // Get results
        $closings = $closingsQuery->get();

        return [
            "closings" => $closings,
            "buses" => $buses
        ];
    }


    public function mergeClosing(Request $request)
    {


        if (!checkForSubmenu("closing")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $mergeIdOne = $request->mergeIds[0];
            $mergeIdTwo = $request->mergeIds[1];
            $mergeOne = DB::table('ticket_closing_merges')->where('id', $mergeIdOne)->first();

            $mergeTwo = DB::table('ticket_closing_merges')->where("id", $mergeIdTwo)->first();

            $merge = TicketClosingMerge::create([
                "bus_id" => $mergeOne->bus_id,
                "schedule_departure_date" => $mergeOne->schedule_departure_date,
                "schedule_return_date" => $mergeTwo->schedule_departure_date,
                "schedule_complete" => 1,
                "closing_date" => Carbon::now(),
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);

            TicketClosing::whereIn("ticket_merge_id", $request->mergeIds)->update([
                "ticket_merge_id" => $merge->id
            ]);
            TicketClosingMerge::whereIn("id", $request->mergeIds)->delete();

            $closingIds = TicketClosing::where("ticket_merge_id", $merge->id)->pluck("id");

            Ticket::whereIn("ticket_closing_id", $closingIds)
                ->withTrashed()
                ->update([
                    "ticket_merge_id" => $merge->id
                ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | closed merge ($closingIds[0] $closingIds[1] $merge->id)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            $i = 0;
            $expenses = $request->expenses;
            foreach ($expenses['category'] as $key => $value) {
                TicketMergeExpense::create([
                    'ticket_merge_id' => $merge->id,
                    'bus_id'          => $mergeOne->bus_id,
                    'start_route_id'  => $request->routes['start'],
                    'return_route_id' => $request->routes['return'],
                    'expense_category_id' => $expenses['category'][$key],
                    'description' => $expenses['description'][$key] ?? '-',
                    'amount' => $expenses['amount'][$key],
                    'paid' => isset($expenses['paid'][$key]) ? $expenses['paid'][$key] : $expenses['amount'][$key],
                    'ledger' => 1,
                    'invoice' => "exp-" . ++$i . '-' . $merge->id,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }

            DB::commit();
            return $merge;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function releaseClosing(Request $request)
    {
        if (!checkPermissionButtons("edit-close-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $mergeId = TicketClosing::find($request->closingId)->ticket_merge_id;
            $closings = TicketClosing::where("ticket_merge_id", $mergeId)->get();
            $previousMerge = TicketClosingMerge::find($mergeId);
            $mergeOne = TicketClosingMerge::create([
                "bus_id" => $previousMerge->bus_id,
                "schedule_departure_date" => $closings[0]->schedule_date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            $mergeTwo = TicketClosingMerge::create([
                "bus_id" => $previousMerge->bus_id,
                "schedule_departure_date" => $closings[1]->schedule_date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            Ticket::where("ticket_merge_id", $mergeId)->update([
                "ticket_merge_id" => null
            ]);
            $previousMerge->delete();
            $closings[0]->update([
                "ticket_merge_id" => $mergeOne->id
            ]);
            $closings[1]->update([
                "ticket_merge_id" => $mergeTwo->id
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | released merge (" . $closings[0]->id . " " . $closings[1]->id . " $mergeOne->id $mergeTwo->id)",
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

    public function merges(Request $request)
    {
        if (!checkForSubmenu("merges")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();

        // Base query
        $query = TicketClosingMerge::where([
            'company_id' => $user->company_id,
            'schedule_complete' => 1
        ])
            ->with([
                'bus:id,bus_number',
                'shortage:id,ticket_closing_id,terminal_id,shortage,total_receivable', // belongsTo relation
                'closing:id,ticket_merge_id,schedule_id',
                'closing.schedule:id,name'
            ]);
        $buses = Bus::where('company_id', $user->company_id)
            ->orderBy('id')
            ->get();
        // Filters
        if ($request->bus_number) $query->where('bus_id', $request->bus_number);
        // Schedule Name Start
        if ($request->schedule_name_start) {
            $query->whereHas('closing.schedule', function ($q) use ($request) {
                $q->where('name', $request->schedule_name_start);
            });
        }

        // Schedule Name End
        if ($request->schedule_name_end) {
            $query->whereHas('closing.schedule', function ($q) use ($request) {
                $q->where('name', $request->schedule_name_end);
            });
        }

        if ($request->from_date) $query->whereDate('schedule_departure_date', '>=', $request->from_date);
        if ($request->to_date) $query->whereDate('schedule_departure_date', '<=', $request->to_date);
        if ($request->closing_date) $query->whereDate('closing_date', $request->closing_date);

        $limit = (!$request->from_date && !$request->to_date) ? 20 : 2000;

        $merges = $query
            ->withSum('shortage', 'total_receivable')
            ->withSum('shortage', 'other_commission')
            ->withSum('shortage', 'kt_commission')
            ->withSum('expenses', 'amount')
            ->get()
            ->map(function ($item) {
                // Calculate the difference and add it as a new attribute
                $item->expenses_sum_amount = $item->shortage_sum_kt_commission + $item->shortage_sum_other_commission + $item->expenses_sum_amount;
                return $item;
            });


        return [
            'merges' => $merges,
            'buses'  => $buses
        ];
    }
    public function buses()
    {
        $user = Auth::user();
        $buses = Bus::where('company_id', $user->company_id)
            ->orderBy('id')
            ->get(['id', 'bus_number as text']);
        return [
            'buses'  => $buses
        ];
    }
    public function schedule()
    {
        $user = Auth::user();

        $schedules = Schedule::where('company_id', $user->company_id)
            ->where('hide', '!=', 1)
            ->distinct('name')
            ->pluck('name')->toArray();

        return [
            'schedules' => $schedules
        ];
    }



    //     public function mergesPdf(Request $request)
    // {
    //     if (!checkForSubmenu("merges")) {
    //         return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    //     }

    //     $user = Auth::user();

    //     // Base query
    //     $merges = TicketClosingMerge::where([
    //         'company_id' => $user->company_id,
    //         'schedule_complete' => 1
    //     ])
    //     ->withSum('expenses','amount')
    //     ->with([
    //         'bus:id,bus_number',
    //         'closing:id,ticket_merge_id,schedule_id',
    //         'closing.schedule:id,name',
    //         'tickets.elt:id,ticket_id,elt_price',
    //         'tickets.schedule:id,route_id',
    //         'tickets.terminal:id,name,is_online_terminal',
    //         'tickets' => function ($q) {
    //             $q->withTrashed()
    //               ->where(function ($query) {
    //                   $query->where('type','booked')
    //                         ->orWhere('type','over-issue');
    //               })
    //               ->whereHas('terminal', function($t) {
    //                   $t->where('is_online_terminal', 1); // only online terminals
    //               })
    //               ->select('id','ticket_merge_id','seat_fare','discount','schedule_id','terminal_id','ticket_closing_id');
    //         }
    //     ])
    //     ->where(function($q) use ($request){
    //         if($request->bus_number) {
    //             $q->where('bus_id', $request->bus_number);
    //         }
    //         if($request->from_date) {
    //             $q->whereDate('schedule_departure_date', '>=', $request->from_date);
    //         }
    //         if($request->to_date) {
    //             $q->whereDate('schedule_departure_date', '<=', $request->to_date);
    //         }
    //         if($request->closing_date) {
    //             $q->whereDate('closing_date', $request->closing_date);
    //         }
    //     })
    //     // Schedule Name Start
    //     ->when($request->schedule_name_start, function($q) use ($request){
    //         $q->whereHas('closing.schedule', function($sub) use ($request){
    //             $sub->where('name', $request->schedule_name_start);
    //         });
    //     })
    //     // Schedule Name End
    //     ->when($request->schedule_name_end, function($q) use ($request){
    //         $q->whereHas('closing.schedule', function($sub) use ($request){
    //             $sub->where('name', $request->schedule_name_end);
    //         });
    //     })
    //     ->limit((empty($request->from_date) && empty($request->to_date)) ? 20 : 2000)
    //     ->latest('schedule_departure_date')
    //     ->get([
    //         'id',
    //         'schedule_departure_date',
    //         'schedule_return_date',
    //         'bus_id',
    //         'closing_date'
    //     ]);

    //     // Map for calculations
    //     $merges->map(function($single) {

    //         // Only online terminal tickets
    //         $tickets = $single->tickets->filter(function($ticket){
    //             return $ticket->terminal && $ticket->terminal->is_online_terminal == 1;
    //         });

    //         $single->seat_fare = $tickets->sum('seat_fare');
    //         $single->discount = $tickets->sum('discount');

    //         $eltAmount = 0;
    //         $commission = 0;
    //         $closingOne = [];
    //         $closingTwo = [];

    //         foreach ($tickets as $ticket) {

    //             // ELT amount
    //             if($ticket->elt){
    //                 $eltAmount += $ticket->elt->elt_price;
    //             }

    //             // Terminal commission
    //             $terminalCommission = TerminalCommission::where([
    //                 "terminal_id" => $ticket->terminal_id,
    //                 "route_id" => $ticket->schedule->route_id,
    //                 "company_id" => Auth::user()->terminal_id
    //             ])->first();

    //             if ($terminalCommission) {
    //                 if (isset($single->closing[0]) && $single->closing[0]->id == $ticket->ticket_closing_id) {
    //                     $closingOne[] = $terminalCommission->id;
    //                 } else {
    //                     $closingTwo[] = $terminalCommission->id;
    //                 }

    //                 if ($terminalCommission->flat_commission == 0) {
    //                     $commission += (($ticket->seat_fare - $ticket->discount)/100) * $terminalCommission->percentage_commission;
    //                 } else {
    //                     $commission += $terminalCommission->flat_commission;
    //                 }

    //                 // KT adjustment commission
    //                 $commission += (($ticket->seat_fare - $ticket->discount)/100) * $terminalCommission->adjustment_commission;
    //             }
    //         }

    //         $commission += TerminalCommission::whereIn("id", array_unique($closingOne))->get()->sum("fix_commission");
    //         $commission += TerminalCommission::whereIn("id", array_unique($closingTwo))->get()->sum("fix_commission");

    //         $single->elt += $eltAmount;
    //         $single->commission += (int)$commission;

    //         // Cancellation/refund
    //         $cancelTicket = Ticket::onlyTrashed()
    //             ->where([
    //                 'company_id' => Auth::user()->company_id,
    //                 'ticket_merge_id' => $single->id,
    //                 'type' => "canceled",
    //             ])
    //             ->with("cancel_ticket:id,ticket_id,percentage")
    //             ->get(["id","seat_fare","discount"]);

    //         $refundAmount = 0;
    //         $cancelTicket->map(function($item) use (&$refundAmount){
    //             if($item->cancel_ticket){
    //                 $refundAmount += (($item->seat_fare - $item->discount)/100) * $item->cancel_ticket->percentage;
    //             }
    //         });

    //         $single->refund += $refundAmount;

    //     });

    //     $data = [
    //         "merges" => $merges,
    //         "closing_date" => $request->closing_date, 
    //     ];

    //     return view('reports.busMergeReport', ['data' => $data]);
    // }

    public function mergesPdf(Request $request)
    {
        if (!checkForSubmenu("merges")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $user = Auth::user();
        $merges = TicketClosingMerge::where(function ($q) use ($request) {
            if ($request->bus_number) {
                $q->where('bus_id', $request->bus_number);
            }
            if ($request->from_date) {
                $q->whereDate('schedule_departure_date', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $q->whereDate('schedule_departure_date', '<=', $request->to_date);
            }
            if ($request->closing_date) {
                $q->whereDate('closing_date', $request->closing_date);
            }
        })
            // Schedule Name Start
            ->when($request->schedule_name_start, function ($q) use ($request) {
                $q->whereHas('closing.schedule', function ($sub) use ($request) {
                    $sub->where('name', $request->schedule_name_start);
                });
            })
            // Schedule Name End
            ->when($request->schedule_name_end, function ($q) use ($request) {
                $q->whereHas('closing.schedule', function ($sub) use ($request) {
                    $sub->where('name', $request->schedule_name_end);
                });
            })
            ->pluck('id');

        $results = TicketClosingShortage::with([
            'bus:id,bus_number',
            'terminal:id,name,is_online_terminal',
            'ticket_closing_merge.expenses'
        ])
            ->whereIn('ticket_closing_id', $merges)
            ->get();

        // Step 1: Get unique terminal names ONLY where is_online_terminal is 1
        $dynamicTypes = $results->where('terminal.is_online_terminal', 1)
            ->pluck('terminal.name')
            ->unique()
            ->filter()
            ->values();

        $mappedResults = $results->groupBy('ticket_closing_id')
            ->map(function ($group) use ($dynamicTypes) {
                $first = $group->first();

                // Online group for portal sums
                $onlineGroup = $group->where('terminal.is_online_terminal', 1);

                // Calculate Total Expense for this merge (regardless of terminal type)
                $totalExpense = $group->unique('ticket_closing_id')->sum(function ($item) {
                    // This now runs only once per ticket_closing_id group
                    return optional($item->ticket_closing_merge)->expenses->sum('amount') ?? 0;
                });

                // Calculate Total Online Sale
                $totalOnlineSale = $group->sum('total_receivable');
                $totalOtherCommission = $group->sum('kt_commission');
                $totalKtCommission = $group->sum('other_commission');
                $totalExpense += $totalKtCommission + $totalOtherCommission; 

                $data = [
                    'bus_no'   => $first->bus->bus_number ?? 'N/A',
                    'sale'     => $totalOnlineSale,
                    'expense'  => $totalExpense,
                    'net_sale' => $totalOnlineSale - $totalExpense,
                ];

                $totalOnlinePortalsAmount = 0;
                // Step 2: Map the online terminal names and track their total
                foreach ($dynamicTypes as $terminalName) {
                    $amount = $onlineGroup->where('terminal.name', $terminalName)->sum('total_receivable');
                    
                    $data['types'][$terminalName] = $amount;
                    $totalOnlinePortalsAmount += $amount; 
                }

                // Step 3: Calculate Net Cash
                // Net Cash = (Total Online Sale - Expenses) - Total Online Portal Amounts
                $data['net_cash'] = $data['net_sale'] - $totalOnlinePortalsAmount;

                return $data;
            })
            ->values();

       $creditExpenses = TicketMergeExpense::with('expense_category:id,name')
            ->whereColumn('amount', '!=', 'paid') // Compares two columns in the same row
            ->whereIn('ticket_merge_id', $merges)
            ->get();

        $data = [
            'dynamicTypes' => $dynamicTypes,
            "merges" => $mappedResults,
            "closing_date" => $request->closing_date,
            "expenses"      => $creditExpenses
        ];

        return view('reports.busMergeReport', ['data' => $data]);
    }


    public function getMembers(Request $request)
    {
        $data = [
            "drivers" => TicketClosingMember::where(['type' => 1, 'company_id' => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->pluck("user_id"),
            "hosts" => TicketClosingMember::where(['type' => 2, 'company_id' => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->pluck("user_id"),
        ];
        return $data;
    }

    public function updateClosingDate(Request $request)
    {
        if (!checkForSubmenu("merges")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        TicketClosingMerge::where("id", $request->mergeId)->update([
            "closing_date" => $request->closingDate,
        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name . " | updated closing date ($request->closingDate)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
    }

    public function fetchSchedule(Request $request)
    {
        return Schedule::where('start_date', '<=', $request->date)
            ->where('end_date', '>=', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->with(["scheduleDetail" => function ($q) use ($request) {
                return $q->where("schedule_date", $request->date);
            }])
            ->orderBy('id')
            ->get(["id", "name"]);
    }

    public function journalHelper(Request $request)
    {
        $terminals = Terminal::where(["company_id" => Auth::user()->company_id])->get(["id", "name"]);
        $heads = AccountHead::with('level_four:id,name')
            ->get()
            ->map(function ($single) {
                return [
                    'id' => $single->id,
                    'text' => $single->name . ' (' . $single->level_four->name . ')',
                    'name' => $single->name,
                ];
            });

        return [
            "terminals" => $terminals,
            "heads" => $heads,
        ];
    }

    public function store(Request $request)
    {
        if (!checkPermissionButtons("assign-bus")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();

            $depTime = ScheduleDetail::where([
                "schedule_id" => $request->schedule,
                "departure_id" => $request->departureCity,
                "destination_id" => $request->destinationCity,
                "schedule_date" => $request->date,
                'departure_time' =>  date("H:i:s", strtotime($request->departure_time)),
                "company_id" => Auth::user()->company_id
            ])->first();

            $schedule = Schedule::where("id", $request->schedule)->first();
            $route = Route::where("id", $schedule->route_id)->first();

            $bookingAvailable = Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, 'schedule_date' => $depTime->schedule_date])->get();
            if (count($bookingAvailable) == 0) {
                return response()->json(["errors" => ["Tickets Error" => ["No Booking Found! \n\n Booked Any Single Seat First"]]], 422);
            }
            // if already assign
            $checkAssign = TicketClosing::where([
                'company_id' => Auth::user()->company_id,
                "bus_id" => $request->bus,
                'schedule_id' => $request->schedule,
                'schedule_date' => $depTime->schedule_date,
            ])
                ->first();

            if ($checkAssign) {
                return response()->json(["errors" => ["Closing Error" => ["Already Closed"]]], 422);
            }


            $newRecord = TicketClosingMerge::create([
                "bus_id" => $request->bus,
                "schedule_departure_date" => $request->date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);


            $closingRecord = TicketClosing::create([
                "bus_id" => $request->bus,
                "ticket_merge_id" => $newRecord->id,
                "schedule_id" => $request->schedule,
                "schedule_date" => $request->date,
                "schedule_time" => $depTime->departure_time,
                "schedule_start" => $request->departureCity,
                "schedule_end" => $request->destinationCity,
                "commission_route" => $route->commission_route,
                "schedule_return" => 0,
                "description" => $request->description,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);

            // for driver
            foreach ($request->drivers as $value) {

                TicketClosingMember::create([
                    "user_id" => $value,
                    "type" => 1,
                    "ticket_closing_id" => $closingRecord->id,
                    "bus_id" => $request->bus,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
            // for host
            foreach ($request->hosts as $value) {
                TicketClosingMember::create([
                    "user_id" => $value,
                    "type" => 2,
                    "ticket_closing_id" => $closingRecord->id,
                    "bus_id" => $request->bus,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }

            Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, "schedule_date" => $request->date])
                ->withTrashed()
                ->update([
                    "bus_id" => $request->bus,
                    "ticket_closing_id" => $closingRecord->id,
                ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | closed schedule ($closingRecord->id)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return $closingRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function update(Request $request)
    {
        if (!checkPermissionButtons("edit-close-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            // delete old members
            TicketClosingMember::where(["company_id" => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->delete();
            // for 
            $closing = TicketClosing::find($request->closingId);
            $merge = TicketClosingMerge::find($closing->ticket_merge_id);
            if ($merge->schedule_complete == 0) {
                $closing->update([
                    "bus_id" => $request->bus
                ]);
                $merge->update([
                    "bus_id" => $request->bus
                ]);
            }
            foreach ($request->drivers as $value) {
                TicketClosingMember::create([
                    "user_id" => $value,
                    "type" => 1,
                    "ticket_closing_id" => $request->closingId,
                    "bus_id" => $closing->bus_id,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
            // for host
            foreach ($request->hosts as $value) {
                TicketClosingMember::create([
                    "user_id" => $value,
                    "type" => 2,
                    "ticket_closing_id" => $request->closingId,
                    "bus_id" => $closing->bus_id,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }

            Ticket::where("ticket_closing_id", $closing->id)->withTrashed()->update(["bus_id" => $request->bus]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated closed schedule ($request->closingId)",
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
