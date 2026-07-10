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
use App\Models\CounterExpense;
use App\Models\Expense\TicketMergeExpense;
use App\Models\FareTable;
use App\Models\OfficeExpense;
use App\Models\ReportHeaderLink;
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
use Illuminate\Validation\Rule;

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

        $user = Auth::user();

        // User allowed route ids
        $allowedRouteIds = $user->route_ids;

        if (is_string($allowedRouteIds)) {
            $allowedRouteIds = json_decode($allowedRouteIds, true);
        }

        $allowedRouteIds = is_array($allowedRouteIds) ? array_map('intval', $allowedRouteIds) : [];

        // Final route ids = user allowed routes
        $finalRouteIds = $allowedRouteIds;

        // If dropdownRoute exists then intersect with allowed routes
        $requestedRoutes = collect((array) $request->input('dropdownRoute', []))
            ->filter(fn($routeId) => $routeId !== null && $routeId !== '')
            ->map(fn($routeId) => (int) $routeId)
            ->unique()
            ->values()
            ->all();

        if (!empty($requestedRoutes)) {
            if (!$user->is_super_admin) {
                $finalRouteIds = array_values(array_intersect($requestedRoutes, $allowedRouteIds));
            } else {
                $finalRouteIds = $requestedRoutes;
            }
        }

        $query = TicketClosing::where('company_id', $user->company_id)
            ->with([
                "bus:id,bus_number",
                "schedule:id,name,route_id",
                "schedule.route:id,name"
            ])
            ->where([
                "hide" => 0,
                "commission_route" => 0
            ])
            ->whereIn('ticket_merge_id', function ($q) use ($user) {
                $q->select('ticket_merge_id')
                    ->from('ticket_closings')
                    ->where('company_id', $user->company_id)
                    ->where('hide', 0)
                    ->where('commission_route', 0)
                    ->groupBy('ticket_merge_id')
                    ->havingRaw('COUNT(*) = 1');
            });

        // Always apply allowed route filter
        if (!$user->is_super_admin) {
            if (empty($finalRouteIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('schedule', function ($q) use ($finalRouteIds) {
                    $q->whereIn('route_id', $finalRouteIds);
                });
            }
        } elseif (!empty($finalRouteIds)) {
            // Super admin + dropdownRoute filter
            $query->whereHas('schedule', function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            });
        }

        $buses = Bus::where('company_id', $user->company_id)
            ->select('id', 'bus_number')
            ->orderBy('bus_number')
            ->get();

        // Bus filter
        if ($request->filled('bus_number')) {
            $query->where('bus_id', $request->bus_number);
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('schedule_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('schedule_date', '<=', $request->to_date);
        }

        $closings = $query
            ->orderBy('bus_id')
            ->get()
            ->groupBy('ticket_merge_id');

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

        $closingPairs = TicketClosing::with('schedule:id,route_id', 'schedule.route')
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
        $ticketsStart = Ticket::withTrashed()
            ->where('company_id', $companyId)
            ->whereIn('ticket_closing_id', [$closingPairsOne->id, $closingPairsTwo->id])
            ->whereIn('type', ['booked', 'over-issue', 'canceled'])

            ->with([
                'elt',
                'cancel_ticket',
                'terminal:id,name,recovery_method',
                'commission' => function ($q) use ($routeIdStart) {
                    return $q->where("route_id", $routeIdStart);
                },
            ])
            ->get();

        $ticketsReturn = Ticket::withTrashed()
            ->where('company_id', $companyId)
            ->whereIn('ticket_closing_id', [$closingPairsOne->id, $closingPairsTwo->id])
            ->whereIn('type', ['booked', 'over-issue', 'canceled'])

            ->with([
                'elt',
                'cancel_ticket',
                'terminal:id,name,recovery_method',
                'commission' => function ($q) use ($routeIdReturn) {
                    return $q->where("route_id", $routeIdReturn);
                },
            ])
            ->get();

        // Group tickets by schedule
        $data = (object)[];
        $data->schedule_start  = $ticketsStart->where('ticket_closing_id', $closingPairsOne->id)->groupBy('terminal_id');
        $data->schedule_return = $ticketsReturn->where('ticket_closing_id', $closingPairsTwo->id)->groupBy('terminal_id');

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
                    'paid' => isset($expenses['paid'][$key]) ? $expenses['paid'][$key] : 0,
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

        $allowedRouteIds = $user->route_ids;

        if (is_string($allowedRouteIds)) {
            $allowedRouteIds = json_decode($allowedRouteIds, true);
        }

        $allowedRouteIds = is_array($allowedRouteIds) ? array_map('intval', $allowedRouteIds) : [];

        // Default
        $finalRouteIds = $allowedRouteIds;

        // Check requested routes
        if ($request->route && count($request->route) > 0) {
            $requestedRoutes = array_map('intval', $request->route);

            if (!$user->is_super_admin) {
                $invalidRoutes = array_diff($requestedRoutes, $allowedRouteIds);

                if (!empty($invalidRoutes)) {
                    return response()->json([
                        'Error' => ['You are not allowed to access these route ids'],
                        'invalid_route_ids' => array_values($invalidRoutes)
                    ], 403);
                }
            }

            $finalRouteIds = $requestedRoutes;
        }

        $query = TicketClosingMerge::where([
            'company_id' => $user->company_id,
            'schedule_complete' => 1
        ])
            ->with([
                'tickets' => function ($q) use ($user, $finalRouteIds) {
                    $q->select('route_id', 'ticket_merge_id');

                    if (!empty($finalRouteIds)) {
                        $q->whereIn('route_id', $finalRouteIds);
                    } elseif (!$user->is_super_admin) {
                        $q->whereRaw('1 = 0');
                    }
                },
                'bus:id,bus_number',
                'shortage:id,ticket_closing_id,elt,terminal_id,shortage,total_receivable',
                'closing:id,ticket_merge_id,schedule_id',
                'closing.schedule:id,name'
            ]);

        // Restrict merges by requested/allowed routes
        if (!empty($finalRouteIds)) {
            $query->whereHas('tickets', function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            });
        } elseif (!$user->is_super_admin) {
            $query->whereRaw('1 = 0');
        }

        if ($request->bus_number && count($request->bus_number) > 0) {
            $query->whereIn('bus_id', $request->bus_number);
        }

        if ($request->schedule_name_start && count($request->schedule_name_start) > 0) {
            $query->whereHas('closing.schedule', function ($q) use ($request) {
                $q->whereIn('name', $request->schedule_name_start);
            });
        }

        if ($request->schedule_name_end && count($request->schedule_name_end) > 0) {
            $query->whereHas('closing.schedule', function ($q) use ($request) {
                $q->whereIn('name', $request->schedule_name_end);
            });
        }

        if ($request->from_date) {
            $query->whereDate('schedule_departure_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('schedule_departure_date', '<=', $request->to_date);
        }

        if ($request->closing_from_date) {
            $query->whereDate('closing_date', '>=', $request->closing_from_date);
        }

        if ($request->closing_to_date) {
            $query->whereDate('closing_date', '<=', $request->closing_to_date);
        }

        $merges = $query
            ->withSum('shortage', 'total_receivable')
            ->withSum('shortage', 'other_commission')
            ->withSum('shortage', 'kt_commission')
            ->withSum('expenses', 'amount')
            ->withSum('shortage', 'elt')
            ->get()
            ->map(function ($item) {
                $item->expenses_sum_amount =
                    $item->shortage_sum_kt_commission +
                    $item->shortage_sum_other_commission +
                    $item->expenses_sum_amount;

                return $item;
            });

        return [
            'merges' => $merges
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

    public function route()
    {
        $user = Auth::user();

        $allowedRouteIds = $user->route_ids;

        // Agar JSON string ho to decode kar lo
        if (is_string($allowedRouteIds)) {
            $allowedRouteIds = json_decode($allowedRouteIds, true);
        }

        // Safety: agar null ho to empty array bana do
        $allowedRouteIds = is_array($allowedRouteIds) ? $allowedRouteIds : [];

        return [
            'routes' => Route::with('addedBy')
                ->where('company_id', $user->company_id)
                ->where('hide', 0)
                ->whereIn('id', $allowedRouteIds)
                ->get()
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
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();

        $allowedRouteIds = $user->route_ids;

        if (is_string($allowedRouteIds)) {
            $allowedRouteIds = json_decode($allowedRouteIds, true);
        }

        $allowedRouteIds = is_array($allowedRouteIds) ? array_map('intval', $allowedRouteIds) : [];

        // Default allowed routes
        $finalRouteIds = $allowedRouteIds;

        // Safely get requested routes from payload
        $requestedRoutes = array_map('intval', (array) $request->input('route', []));

        // Check requested routes
        if (count($requestedRoutes) > 0) {
            if (!$user->is_super_admin) {
                $invalidRoutes = array_diff($requestedRoutes, $allowedRouteIds);

                if (!empty($invalidRoutes)) {
                    return response()->json([
                        'Error' => ['You are not allowed to access these route ids'],
                        'invalid_route_ids' => array_values($invalidRoutes)
                    ], 403);
                }
            }

            $finalRouteIds = $requestedRoutes;
        }

        $mergesQuery = TicketClosingMerge::where([
            'company_id' => $user->company_id,
            'schedule_complete' => 1
        ]);

        // Restrict by requested/allowed routes
        if (!empty($finalRouteIds)) {
            $mergesQuery->whereHas('tickets', function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            });
        } elseif (!$user->is_super_admin) {
            $mergesQuery->whereRaw('1 = 0');
        }

        // Bus filter
        $busNumbers = (array) $request->input('bus_number', []);
        if (count($busNumbers) > 0) {
            $mergesQuery->whereIn('bus_id', $busNumbers);
        }

        if ($request->from_date) {
            $mergesQuery->whereDate('schedule_departure_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $mergesQuery->whereDate('schedule_departure_date', '<=', $request->to_date);
        }

        if ($request->closing_from_date) {
            $mergesQuery->whereDate('closing_date', '>=', $request->closing_from_date);
        }

        if ($request->closing_to_date) {
            $mergesQuery->whereDate('closing_date', '<=', $request->closing_to_date);
        }

        // Schedule Name Start
        $scheduleNameStart = (array) $request->input('schedule_name_start', []);
        if (count($scheduleNameStart) > 0) {
            $mergesQuery->whereHas('closing.schedule', function ($sub) use ($scheduleNameStart) {
                $sub->whereIn('name', $scheduleNameStart);
            });
        }

        // Schedule Name End
        $scheduleNameEnd = (array) $request->input('schedule_name_end', []);
        if (count($scheduleNameEnd) > 0) {
            $mergesQuery->whereHas('closing.schedule', function ($sub) use ($scheduleNameEnd) {
                $sub->whereIn('name', $scheduleNameEnd);
            });
        }

        $merges = $mergesQuery->pluck('id');

        $results = TicketClosingShortage::with([
            'bus:id,bus_number',
            'terminal:id,name,is_online_terminal',
            'ticket_closing_merge.expenses',
            'route'
        ])
            ->whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->get();

        $dynamicTypes = $results->where('terminal.is_online_terminal', 1)
            ->pluck('terminal.name')
            ->unique()
            ->filter()
            ->values();

        $mappedResults = $results->groupBy('ticket_closing_id')
            ->map(function ($group) use ($dynamicTypes) {
                $first = $group->first();

                $onlineGroup = $group->where('terminal.is_online_terminal', 1);
                $offlineGroup = $group->where('terminal.is_online_terminal', 0);

                $totalExpense = $group->unique('ticket_closing_id')->sum(function ($item) {
                    return optional($item->ticket_closing_merge)->expenses->sum('amount') ?? 0;
                });

                $totalOnlineSale = $group->sum('total_receivable');
                $totalEltSale = $group->sum('elt');
                $totalReceivedBank = $offlineGroup->sum('total_received_bank');
                $totalOtherCommission = $group->sum('other_commission');
                $totalKtCommission = $group->sum('kt_commission');

                $totalExpense += $totalKtCommission + $totalOtherCommission;

                $data = [
                    'bus_no'              => $first->bus->bus_number ?? 'N/A',
                    'route'               => $first->route->name ?? 'N/A',
                    'total_received_bank' => $totalReceivedBank,
                    'sale'                => $totalOnlineSale,
                    'elt_sale'            => $totalEltSale,
                    'expense'             => $totalExpense,
                    'net_sale'            => ( $totalOnlineSale + $totalEltSale ) - $totalExpense,
                ];

                $totalOnlinePortalsAmount = 0;

                foreach ($dynamicTypes as $terminalName) {
                    $amount = $onlineGroup
                        ->where('terminal.name', $terminalName)
                        ->sum(function ($item) {
                            return ($item->received ?? 0);
                        });

                    $data['types'][$terminalName] = $amount;
                    $totalOnlinePortalsAmount += $amount;
                }

                $data['net_cash'] = $data['net_sale'] - $totalOnlinePortalsAmount;

                return $data;
            })
            ->values();

        $creditExpenses = TicketMergeExpense::with('expense_category:id,name')
            ->whereColumn('amount', '!=', 'paid')
            ->whereIn('ticket_merge_id', $merges)
            ->whereHas('expense_category', function ($query) {
                $query->where('include_in_closing', '1');
            })
            ->get();

        $totalCounterExpense = CounterExpense::where(function ($q) use ($request) {
            if ($request->closing_from_date) {
                $q->whereDate('date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date) {
                $q->whereDate('date', '<=', $request->closing_to_date);
            }
        })
            ->where('type', 'expense')
            ->sum('total');

        $totalCounterIncome = CounterExpense::where(function ($q) use ($request) {
            if ($request->closing_from_date) {
                $q->whereDate('date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date) {
                $q->whereDate('date', '<=', $request->closing_to_date);
            }
        })
            ->where('type', 'income')
            ->sum('total');

        $totalKtCommission = TicketClosingShortage::whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->sum('kt_commission');

        $totalReceivedBank = TicketClosingShortage::whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->whereHas('terminal', function ($query) {
                $query->where('is_online_terminal', 0);
            })
            ->sum('total_received_bank');

        $data = [
            'dynamicTypes'        => $dynamicTypes,
            'merges'              => $mappedResults,
            'closing_from_date'   => $request->closing_from_date,
            'closing_to_date'     => $request->closing_to_date,
            'expenses'            => $creditExpenses,
            'totalCounterExpense' => $totalCounterExpense,
            'totalCounterIncome'  => $totalCounterIncome,
            'totalKtCommission'   => $totalKtCommission,
            'totalReceivedBank'   => $totalReceivedBank
        ];

        return view('reports.busMergeReport', ['data' => $data]);
    }
    public function mergesUrduPdf(Request $request)
    {
        if (!checkForSubmenu("merges")) {
            return response()->json([
                "Error" => ['You are not authorized to access this url']
            ], 403);
        }

        $user = Auth::user();

        $allowedRouteIds = $user->route_ids;

        if (is_string($allowedRouteIds)) {
            $allowedRouteIds = json_decode($allowedRouteIds, true);
        }

        $allowedRouteIds = is_array($allowedRouteIds) ? array_map('intval', $allowedRouteIds) : [];

        // Default allowed routes
        $finalRouteIds = $allowedRouteIds;

        // Safely get requested routes from payload
        $requestedRoutes = array_map('intval', (array) $request->input('route', []));

        // Check requested routes
        if (count($requestedRoutes) > 0) {
            if (!$user->is_super_admin) {
                $invalidRoutes = array_diff($requestedRoutes, $allowedRouteIds);

                if (!empty($invalidRoutes)) {
                    return response()->json([
                        'Error' => ['You are not allowed to access these route ids'],
                        'invalid_route_ids' => array_values($invalidRoutes)
                    ], 403);
                }
            }

            $finalRouteIds = $requestedRoutes;
        }

        $mergesQuery = TicketClosingMerge::where([
            'company_id' => $user->company_id,
            'schedule_complete' => 1
        ]);

        // Restrict by requested/allowed routes
        if (!empty($finalRouteIds)) {
            $mergesQuery->whereHas('tickets', function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            });
        } elseif (!$user->is_super_admin) {
            $mergesQuery->whereRaw('1 = 0');
        }

        // Bus filter
        $busNumbers = (array) $request->input('bus_number', []);
        if (count($busNumbers) > 0) {
            $mergesQuery->whereIn('bus_id', $busNumbers);
        }

        if ($request->from_date) {
            $mergesQuery->whereDate('schedule_departure_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $mergesQuery->whereDate('schedule_departure_date', '<=', $request->to_date);
        }

        if ($request->closing_from_date) {
            $mergesQuery->whereDate('closing_date', '>=', $request->closing_from_date);
        }

        if ($request->closing_to_date) {
            $mergesQuery->whereDate('closing_date', '<=', $request->closing_to_date);
        }

        // Schedule Name Start
        $scheduleNameStart = (array) $request->input('schedule_name_start', []);
        if (count($scheduleNameStart) > 0) {
            $mergesQuery->whereHas('closing.schedule', function ($sub) use ($scheduleNameStart) {
                $sub->whereIn('name', $scheduleNameStart);
            });
        }

        // Schedule Name End
        $scheduleNameEnd = (array) $request->input('schedule_name_end', []);
        if (count($scheduleNameEnd) > 0) {
            $mergesQuery->whereHas('closing.schedule', function ($sub) use ($scheduleNameEnd) {
                $sub->whereIn('name', $scheduleNameEnd);
            });
        }

        $merges = $mergesQuery->pluck('id');

        $results = TicketClosingShortage::with([
            'bus:id,bus_number',
            'terminal:id,name,is_online_terminal',
            'ticket_closing_merge.expenses',
            'route'
        ])
            ->whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->get();

        $dynamicTypes = $results->where('terminal.is_online_terminal', 1)
            ->pluck('terminal.name')
            ->unique()
            ->filter()
            ->values();

        $mappedResults = $results->groupBy('ticket_closing_id')
            ->map(function ($group) use ($dynamicTypes) {
                $first = $group->first();

                $onlineGroup = $group->where('terminal.is_online_terminal', 1);
                $offlineGroup = $group->where('terminal.is_online_terminal', 0);

                $totalExpense = $group->unique('ticket_closing_id')->sum(function ($item) {
                    return optional($item->ticket_closing_merge)->expenses->sum('amount') ?? 0;
                });

                $totalOnlineSale = $group->sum('total_receivable');
                $totalReceivedBank = $offlineGroup->sum('total_received_bank');
                $totalOtherCommission = $group->sum('other_commission');
                $totalKtCommission = $group->sum('kt_commission');

                $totalExpense += $totalKtCommission + $totalOtherCommission;

                $data = [
                    'bus_no'              => $first->bus->bus_number ?? 'N/A',
                    'route'               => $first->route->name ?? 'N/A',
                    'total_received_bank' => $totalReceivedBank,
                    'sale'                => $totalOnlineSale,
                    'expense'             => $totalExpense,
                    'net_sale'            => $totalOnlineSale - $totalExpense,
                ];

                $totalOnlinePortalsAmount = 0;

                foreach ($dynamicTypes as $terminalName) {
                    $amount = $onlineGroup
                        ->where('terminal.name', $terminalName)
                        ->sum(function ($item) {
                            return ($item->received ?? 0);
                        });

                    $data['types'][$terminalName] = $amount;
                    $totalOnlinePortalsAmount += $amount;
                }

                $data['net_cash'] = $data['net_sale'] - $totalOnlinePortalsAmount;

                return $data;
            })
            ->values();

        $creditExpenses = TicketMergeExpense::with('expense_category:id,name')
            ->whereColumn('amount', '!=', 'paid')
            ->whereIn('ticket_merge_id', $merges)
            ->whereHas('expense_category', function ($query) {
                $query->where('include_in_closing', '1');
            })
            ->get();

        $totalCounterExpense = CounterExpense::where(function ($q) use ($request) {
            if ($request->closing_from_date) {
                $q->whereDate('date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date) {
                $q->whereDate('date', '<=', $request->closing_to_date);
            }
        })
            ->where('type', 'expense')
            ->sum('total');

        $totalCounterIncome = CounterExpense::where(function ($q) use ($request) {
            if ($request->closing_from_date) {
                $q->whereDate('date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date) {
                $q->whereDate('date', '<=', $request->closing_to_date);
            }
        })
            ->where('type', 'income')
            ->sum('total');

        $totalKtCommission = TicketClosingShortage::whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->sum('kt_commission');

        $totalReceivedBank = TicketClosingShortage::whereIn('ticket_closing_id', $merges)
            ->when(!empty($finalRouteIds), function ($q) use ($finalRouteIds) {
                $q->whereIn('route_id', $finalRouteIds);
            })
            ->whereHas('terminal', function ($query) {
                $query->where('is_online_terminal', 0);
            })
            ->sum('total_received_bank');

        $data = [
            'dynamicTypes'        => $dynamicTypes,
            'merges'              => $mappedResults,
            'closing_from_date'   => $request->closing_from_date,
            'closing_to_date'     => $request->closing_to_date,
            'expenses'            => $creditExpenses,
            'totalCounterExpense' => $totalCounterExpense,
            'totalCounterIncome'  => $totalCounterIncome,
            'totalKtCommission'   => $totalKtCommission,
            'totalReceivedBank'   => $totalReceivedBank
        ];


        return view('reports.busMergeReportUrdu', ['data' => $data]);
    }
    public function summaryReport(Request $request)
{
    if (!checkForSubmenu("close-trip")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $companyId = Auth::user()->company_id;

    $closings = TicketClosingMerge::with([
        'closing:id,ticket_merge_id,bus_id',
        'closing.tickets.elt:id,elt_price,ticket_id',
        'closing.tickets' => function ($q) {
            $q->where("type", "booked");
            $q->select(["id", "ticket_closing_id", "seat_fare", "discount", "terminal_id"]);
        }
    ])
        ->where('schedule_complete', 1)
        ->where('company_id', $companyId)
        ->where(function ($q) use ($request) {
            if ($request->filled('busNO')) {
                $busIds = is_array($request->busNO) ? $request->busNO : [$request->busNO];
                $busIds = array_filter($busIds, function ($id) {
                    return !empty($id) && $id != 0;
                });

                if (!empty($busIds)) {
                    $q->whereIn('bus_id', $busIds);
                }
            }

            if ($request->closing_from_date != null) {
                $q->where('closing_date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date != null) {
                $q->where('closing_date', '<=', $request->closing_to_date);
            }
        })
        ->get();

    $mergeIds = TicketClosingMerge::where('schedule_complete', 1)
        ->where('company_id', $companyId)
        ->where(function ($q) use ($request) {
            if ($request->filled('busNO')) {
                $busIds = is_array($request->busNO) ? $request->busNO : [$request->busNO];
                $busIds = array_filter($busIds, function ($id) {
                    return !empty($id) && $id != 0;
                });

                if (!empty($busIds)) {
                    $q->whereIn('bus_id', $busIds);
                }
            }

            if ($request->closing_from_date != null) {
                $q->where('closing_date', '>=', $request->closing_from_date);
            }

            if ($request->closing_to_date != null) {
                $q->where('closing_date', '<=', $request->closing_to_date);
            }
        })
        ->pluck('id');

    $headerLink = ReportHeaderLink::where('company_id', $companyId)
        ->whereIn('ticket_merge_id', $mergeIds)
        ->get(['id', 'header_id', 'ticket_merge_id', 'value'])
        ->groupBy(['ticket_merge_id', 'header_id']);

    $expenses = TicketMergeExpense::where('company_id', $companyId)
        ->whereIn('ticket_merge_id', $mergeIds)
        ->get(['ticket_merge_id', 'description'])
        ->groupBy('ticket_merge_id');

    foreach ($expenses as $mergeId => $expenseList) {
        if (!isset($headerLink[$mergeId])) {
            $headerLink[$mergeId] = collect();
        }

        $headerLink[$mergeId]['expenses'] = $expenseList->map(function ($exp) {
            return [
                'description' => $exp->description,
                'amount' => $exp->amount,
            ];
        })->values();
    }

    $onlineTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)
        ->where('company_id', $companyId)
        ->where(['online_terminal' => 1, 'type' => "booked"])
        ->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount', 'schedule_id', 'route_id'])
        ->groupBy(['ticket_merge_id', 'terminal_id']);

    $physicalTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)
        ->where('company_id', $companyId)
        ->where(['online_terminal' => 0, 'type' => "booked"])
        ->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount', 'schedule_id', 'route_id'])
        ->groupBy(['ticket_merge_id', 'schedule_id', 'terminal_id']);

    $closings->map(function ($closing) {
        $closing->closing->map(function ($ticket) use ($closing) {
            $ticket->ticket_fare = $ticket->tickets->sum("seat_fare") - $ticket->tickets->sum("discount");
            $ticket->elt_fare = 0;

            $ticket->tickets->map(function ($elt) use ($ticket) {
                if (!is_null($elt->elt)) {
                    $ticket->elt_fare = $elt->elt->sum('elt_price');
                }
            });

            $closing->total_income = (int) $closing->closing->sum('ticket_fare') + (int) $closing->closing->sum('elt_fare');
        });

        $closing->total_expenses = (int) TicketMergeExpense::where('ticket_merge_id', $closing->id)->sum('amount');
        $closing->mod = ($closing->closing[0]->tickets->count() + $closing->closing[1]->tickets->count()) * 20;

        return $closing;
    });

    $onlineTerminalData->map(function ($merge) use ($companyId) {
        $merge->map(function ($terminal) use ($companyId) {
            $terminal->map(function ($ticket) use ($companyId) {
                $commission = TerminalCommission::where([
                    "company_id" => $companyId,
                    'terminal_id' => $ticket->terminal_id,
                    "route_id" => $ticket->route_id
                ])->first();

                if ($commission) {
                    if ($commission->flat_commission == 0) {
                        $amount = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->percentage_commission;
                    } else {
                        $amount = $commission->flat_commission;
                    }

                    $ticket->commission_amount = intval($amount);
                } else {
                    $ticket->commission_amount = 0;
                }
            });
        });
    });

    $physicalTerminalData->map(function ($merge) use ($companyId) {
        $merge->map(function ($schedule) use ($companyId) {
            $schedule->map(function ($terminal) use ($companyId) {
                $terminal->map(function ($ticket) use ($companyId) {
                    $commission = TerminalCommission::where([
                        "company_id" => $companyId,
                        'terminal_id' => $ticket->terminal_id,
                        "route_id" => $ticket->route_id
                    ])->first();

                    if ($commission) {
                        if ($commission->flat_commission == 0) {
                            $amount = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->percentage_commission;
                        } else {
                            $amount = $commission->flat_commission;
                        }

                        $ticket->commission_amount = intval($amount);
                        $ticket->kt_commission = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->adjustment_commission;
                        $ticket->fix_commission = intval($commission->fix_commission);
                    } else {
                        $ticket->commission_amount = 0;
                        $ticket->fix_commission = 0;
                        $ticket->kt_commission = 0;
                    }
                });
            });
        });
    });

    return view('reports.SummeryReportEng', [
        "data" => $closings,
        "online_terminals" => $onlineTerminalData,
        "physical_terminals" => $physicalTerminalData,
        "headers_link" => $headerLink,
    ]);
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
        $request->validate([
            'terminal_id' => [
                'nullable',
                Rule::exists('terminals', 'id')->where(function ($query) {
                    return $query->where([
                        'company_id' => Auth::user()->company_id,
                        'is_online_terminal' => 0,
                        'hide' => 0,
                    ])->whereNull('deleted_at');
                }),
            ],
        ]);
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
                "terminal_id" => $request->terminal_id ?: null,
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
        $request->validate([
            'terminal_id' => [
                'nullable',
                Rule::exists('terminals', 'id')->where(function ($query) {
                    return $query->where([
                        'company_id' => Auth::user()->company_id,
                        'is_online_terminal' => 0,
                        'hide' => 0,
                    ])->whereNull('deleted_at');
                }),
            ],
        ]);
        try {
            DB::beginTransaction();
            // delete old members
            TicketClosingMember::where(["company_id" => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->delete();
            // for
            $closing = TicketClosing::find($request->closingId);
            $merge = TicketClosingMerge::find($closing->ticket_merge_id);
            $closing->update([
                "terminal_id" => $request->terminal_id ?: null,
            ]);
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
