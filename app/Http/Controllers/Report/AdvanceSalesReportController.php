<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use App\Models\CounterExpense;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use App\Support\ReportFilterScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvanceSalesReportController extends Controller
{
    private const ADVANCE_SALES_COLUMNS = [
        'date',
        'bus_number',
        'bus_class',
        'route',
        'seats',
        'terminal',
        'user',
        'invoice_id',
        'transaction_id',
        'passenger_name',
        'passenger_contact',
        'passenger_cnic',
        'sales',
        'discount',
        'elt',
        'net_sale',
    ];

    public function getUserNames()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::terminalUsers('user-filter');
    }

    public function getSchedules()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Schedule::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function getTerminals()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::terminals('terminal-filter');
    }

    public function getRoutes()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::routes('route-filter');
    }

    public function filterData(Request $request)
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $routeIds = ReportFilterScope::routeIds($request, 'route-filter');
        $passengerName = trim($request->passenger_name ?? '');
        $passengerContact = preg_replace('/[^0-9]/', '', $request->passenger_contact ?? '');
        $passengerCnic = preg_replace('/[^0-9]/', '', $request->passenger_cnic ?? '');
        $isCounterSale = filter_var($request->counterSale, FILTER_VALIDATE_BOOLEAN);
        $selectedTerminalId = ReportFilterScope::terminalId($request, 'terminal-filter');
        $selectedUserId = ReportFilterScope::terminalUserId($request, 'user-filter');

        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'route:id,name,via', 'schedule:id,name,time',"bus:id,bus_number", 'customer:id,name,contact,cnic')
            ->withTrashed()
            ->where('company_id', Auth::user()->company_id)
             ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })

            ->when($selectedTerminalId, function($query) use ($selectedTerminalId){
                return $query->where('terminal_id', $selectedTerminalId);
            })
            ->when($selectedUserId, function ($query) use ($selectedUserId) {
                return $query->where('updated_by', $selectedUserId);
            })
            ->when($routeIds !== null, function ($query) use ($routeIds) {
                return empty($routeIds) ? $query->whereRaw('1 = 0') : $query->whereIn('route_id', $routeIds);
            })
            ->when($request->invoice_id, function ($query) use ($request) {
                return $query->where('invoice_id', 'LIKE', '%' . trim($request->invoice_id) . '%');
            })
            ->when($request->transaction_id, function ($query) use ($request) {
                return $query->where('transaction_id', 'LIKE', '%' . trim($request->transaction_id) . '%');
            })
            ->when($passengerName || $passengerContact || $passengerCnic, function ($query) use ($passengerName, $passengerContact, $passengerCnic) {
                return $query->whereHas('customer', function ($customerQuery) use ($passengerName, $passengerContact, $passengerCnic) {
                    if ($passengerName != '') {
                        $customerQuery->where('name', 'LIKE', '%' . $passengerName . '%');
                    }

                    if ($passengerContact != '') {
                        $customerQuery->whereRaw(
                            "REPLACE(REPLACE(REPLACE(contact, '-', ''), ' ', ''), '+', '') LIKE ?",
                            ['%' . $passengerContact . '%']
                        );
                    }

                    if ($passengerCnic != '') {
                        $customerQuery->whereRaw("REPLACE(cnic, '-', '') LIKE ?", ['%' . $passengerCnic . '%']);
                    }
                });
            })
            ->when($isCounterSale, function ($query) use ($request) {
                return $query->whereBetween('created_at', [date("Y-m-d H:i:s",strtotime($request->fromDateTime)),date("Y-m-d H:i:s",strtotime($request->toDateTime))]);
            })
            ->orderBy('date', 'desc')
            ->get();

        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
            return $single;
        });

        // date filter
        if($request->fromDateTime && !$isCounterSale)
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime && !$isCounterSale)
        {
            $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s",strtotime($request->toDateTime)));
        }
        // return $tickets;
        $sortData = $this->buildTicketRows($tickets);

//        Refund Data Details

        // $refundTickets = Ticket::with('cancel_ticket', 'schedule:id,time')->where('company_id', Auth::user()->company_id)
        //     ->where('type', 'canceled')->withTrashed()
        //     ->when($request->terminal, function ($query) use ($request) {
        //         return $query->where('terminal_id', $request->terminal);
        //     })
        //     ->when($request->user, function ($query) use ($request) {
        //         return $query->where('added_by', $request->user);
        //     })
        //     ->when($request->route, function ($query) use ($request) {
        //         return $query->whereIn('route_id', $request->route);
        //     })
        //     ->get();
        // $refundTickets->map(function ($q) {
        //     $q->cancel_percentage = $q->cancel_ticket->percentage;
        //     $user = User::find($q->cancel_ticket->added_by);
        //     $q->refund_by = $user ? $user->name : '-';
        //     $q->cancel_date = $q->cancel_ticket->time;
        //     $q->bus_NO = BusClass::find($q->bus_class_id)->name;
        //     $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
        //     $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
        //     $final = $percentageValue / 100;
        //     $q->amount_refund = round((int)$q->seat_fare - $final);
        //     $q->cancelation_charges = round($final);
        //     unset($q->cancel_ticket, $q->schedule);
        // });


        // Counter expenses data
        // if ((int)$request->terminal !== 0 || (int)$request->user !== 0 || $request->fromDateTime || $request->toDateTime) {
        //     $counterexpenses = CounterExpense::with('added_by', 'terminal')->where('company_id', Auth::user()->company_id)
        //         ->when($request->terminal, function ($query) use ($request) {
        //             return $query->where('terminal_id', $request->terminal);
        //         })
        //         ->when($request->user, function ($query) use ($request) {
        //             return $query->where('added_by', $request->user);
        //         })
        //         ->when($request->fromDateTime, function ($query) use ($request) {
        //             return $query->where('time', '>=', $request->fromDateTime);
        //         })
        //         ->when($request->toDateTime, function ($query) use ($request) {
        //             return $query->where('time', '<=', $request->toDateTime);
        //         })
        //         ->get();
        //     }


            return [
                'record' => $sortData,
                'refund' => [],
                'counterExpenses' => $counterexpenses ?? [],
        ];

    }

    public function advanceSalePdf(Request $request)
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $route_ids = ReportFilterScope::routeIds($request, 'route-filter');
        $passengerName = trim($request->passenger_name ?? '');
        $passengerContact = preg_replace('/[^0-9]/', '', $request->passenger_contact ?? '');
        $passengerCnic = preg_replace('/[^0-9]/', '', $request->passenger_cnic ?? '');
        $isCounterSale = filter_var($request->counterSale, FILTER_VALIDATE_BOOLEAN);
        $selectedTerminalId = ReportFilterScope::terminalId($request, 'terminal-filter');
        $selectedUserId = ReportFilterScope::terminalUserId($request, 'user-filter');

        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'route:id,name,via', 'schedule:id,name,time',"bus:id,bus_number", 'customer:id,name,contact,cnic')
            ->withTrashed()
            ->where('company_id', Auth::user()->company_id)
             ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })

            ->when($selectedTerminalId, function($query) use ($selectedTerminalId){
                return $query->where('terminal_id', $selectedTerminalId);
            })
            ->when($selectedUserId, function ($query) use ($selectedUserId) {
                return $query->where('updated_by', $selectedUserId);
            })
            ->when($route_ids !== null, function ($query) use ($route_ids) {
                return empty($route_ids) ? $query->whereRaw('1 = 0') : $query->whereIn('route_id', $route_ids);
            })
            ->when($request->invoice_id, function ($query) use ($request) {
                return $query->where('invoice_id', 'LIKE', '%' . trim($request->invoice_id) . '%');
            })
            ->when($request->transaction_id, function ($query) use ($request) {
                return $query->where('transaction_id', 'LIKE', '%' . trim($request->transaction_id) . '%');
            })
            ->when($passengerName || $passengerContact || $passengerCnic, function ($query) use ($passengerName, $passengerContact, $passengerCnic) {
                return $query->whereHas('customer', function ($customerQuery) use ($passengerName, $passengerContact, $passengerCnic) {
                    if ($passengerName != '') {
                        $customerQuery->where('name', 'LIKE', '%' . $passengerName . '%');
                    }

                    if ($passengerContact != '') {
                        $customerQuery->whereRaw(
                            "REPLACE(REPLACE(REPLACE(contact, '-', ''), ' ', ''), '+', '') LIKE ?",
                            ['%' . $passengerContact . '%']
                        );
                    }

                    if ($passengerCnic != '') {
                        $customerQuery->whereRaw("REPLACE(cnic, '-', '') LIKE ?", ['%' . $passengerCnic . '%']);
                    }
                });
            })
            ->when($isCounterSale, function ($query) use ($request) {
                return $query->whereBetween('created_at', [date("Y-m-d H:i:s",strtotime($request->fromDateTime)),date("Y-m-d H:i:s",strtotime($request->toDateTime))]);
            })
            ->orderBy('date', 'desc')
            ->get();

        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
            return $single;
        });

        // date filter
        if($request->fromDateTime && !$isCounterSale)
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime && !$isCounterSale)
        {
            $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s",strtotime($request->toDateTime)));
        }
        // return $tickets;
        $sortData = $this->buildTicketRows($tickets);


        $filterData = (object)[];
        $filterData->terminal = Terminal::find($selectedTerminalId)->name??"N/A";
        $filterData->user = User::find($selectedUserId)->name??"All";
        $filterData->route = Route::whereIn("id",$route_ids ?? [])->pluck("name")->toArray();
        $filterData->from = date("Y/m/d H:i A",strtotime($request->fromDateTime));
        $filterData->to = date("Y/m/d h:i A",strtotime($request->toDateTime));
        $filterData->invoice_id = trim($request->invoice_id ?? '') ?: 'All';
        $filterData->transaction_id = trim($request->transaction_id ?? '') ?: 'All';
        $filterData->passenger_name = trim($request->passenger_name ?? '') ?: 'All';
        $filterData->passenger_contact = trim($request->passenger_contact ?? '') ?: 'All';
        $filterData->passenger_cnic = trim($request->passenger_cnic ?? '') ?: 'All';
        $filterData->counter_sale = $isCounterSale ? 'Yes' : 'No';



        return view('reports.advanceSaleReport', [
            'record' => $sortData,
            'refund' =>[],
            'counterExpenses' => $counterexpenses ?? [],
            'filterData' => $filterData,
            'visibleColumns' => $this->getVisibleColumns($request),
        ]);
    }

    private function getVisibleColumns(Request $request): array
    {
        if (!$request->has('visible_columns')) {
            return self::ADVANCE_SALES_COLUMNS;
        }

        $visibleColumns = $request->input('visible_columns');

        if (is_string($visibleColumns)) {
            $visibleColumns = trim($visibleColumns) === ''
                ? []
                : array_map('trim', explode(',', $visibleColumns));
        } elseif (!is_array($visibleColumns)) {
            return self::ADVANCE_SALES_COLUMNS;
        }

        return array_values(array_intersect(self::ADVANCE_SALES_COLUMNS, $visibleColumns));
    }

    private function buildTicketRows($tickets): array
    {
        return $tickets
            ->sortBy('schedule_date_time')
            ->values()
            ->map(function ($ticket) {
                $elt = $ticket->ticketElt?->elt_price ?? 0;
                $sales = $ticket->seat_fare ?? 0;
                $discount = $ticket->discount ?? 0;

                return [
                    'bus_number' => $ticket->bus?->bus_number ?? 'N/A',
                    'bus_class' => $ticket->busClass?->name ?? 'N/A',
                    'route' => $ticket->route?->name ?? 'N/A',
                    'seats' => 1,
                    'seat_no' => $ticket->seat_no,
                    'terminal' => $ticket->terminal?->name ?? 'N/A',
                    'user' => $ticket->updated_name?->name ?? 'N/A',
                    'sales' => $sales,
                    'discount' => $discount,
                    'date' => date("Y-m-d", strtotime($ticket->schedule_date_time)),
                    'time' => date("h:i A", strtotime($ticket->schedule_date_time)),
                    'invoice_id' => $ticket->invoice_id ?: 'N/A',
                    'transaction_id' => $ticket->transaction_id ?: 'N/A',
                    'passenger_name' => $ticket->customer->name ?? 'N/A',
                    'passenger_contact' => !empty($ticket->customer?->contact)
                        ? formatContact($ticket->customer->contact)
                        : 'N/A',
                    'passenger_cnic' => !empty($ticket->customer?->cnic)
                        ? formatCNIC($ticket->customer->cnic)
                        : 'N/A',
                    'elt' => $elt,
                    'net_sale' => $sales - $discount + $elt,
                ];
            })
            ->toArray();
    }

}
