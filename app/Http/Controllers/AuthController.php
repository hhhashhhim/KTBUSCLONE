<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Expense\ExpenseCategory;
use App\Models\Expense\TicketMergeExpense;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Terminal;
use App\Models\Ticket;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        //        main Data for urdu
       $closings = TicketClosingMerge::with('closing:id,ticket_merge_id,bus_id', 'closing.tickets:id,ticket_closing_id,seat_fare,discount', 'closing.tickets.elt:id,elt_price,ticket_id')->where('schedule_complete', 1)->where('schedule_departure_date', '>', '2023-04-02')->get(['id', 'schedule_complete']);
       $mergeIds = TicketClosingMerge::where('schedule_complete', 1)->where('schedule_departure_date', '>', '2023-04-02')->pluck('id');

       $onlineTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)->where('company_id', Auth::user()->company_id)->where('online_terminal', 1)->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount'])->groupBy(['ticket_merge_id', 'terminal_id']);
//Map function for single iteration
       $closings->map(function ($closing) {
//            get data from single iteration with relation
           $closing->closing->map(function ($ticket) use ($closing) {

               $ticket->ticket_fare = $ticket->tickets->sum("seat_fare") - $ticket->tickets->sum("discount");
               $ticket->elt_fare = 0;
               $ticket->tickets->map(function ($elt) use ($ticket) {
                   if (!is_null($elt->elt)) {
                       $ticket->elt_fare = $elt->elt->sum('elt_price');
                   }
               });
               $closing->total_income = (int)$closing->closing->sum('ticket_fare') + (int)$closing->closing->sum('elt_fare');
           });
           $closing->total_expenses = (int)TicketMergeExpense::where('ticket_merge_id', $closing->id)->sum('amount');
           $closing->mod = ($closing->closing[0]->tickets->count() + $closing->closing[1]->tickets->count()) * 20;
           return $closing;
       });
       return view('reports.dailySummeryReportUrdu', [
           "data" => $closings,
           "online_terminals" => $onlineTerminalData,
       ]);



// //        main Data for english
//        $closings = TicketClosingMerge::with('closing:id,ticket_merge_id,bus_id', 'closing.tickets:id,ticket_closing_id,seat_fare,discount', 'closing.tickets.elt:id,elt_price,ticket_id')->where('schedule_complete', 1)->where('schedule_departure_date', '>', '2023-04-02')->get(['id', 'schedule_complete']);
//        $mergeIds = TicketClosingMerge::where('schedule_complete', 1)->where('schedule_departure_date', '>', '2023-04-02')->pluck('id');

//        $onlineTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)->where('company_id', Auth::user()->company_id)->where('online_terminal', 1)->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount'])->groupBy(['ticket_merge_id', 'terminal_id']);
// //Map function for single iteration
//        $closings->map(function ($closing) {
// //            get data from single iteration with relation
//            $closing->closing->map(function ($ticket) use ($closing) {

//                $ticket->ticket_fare = $ticket->tickets->sum("seat_fare") - $ticket->tickets->sum("discount");
//                $ticket->elt_fare = 0;
//                $ticket->tickets->map(function ($elt) use ($ticket) {
//                    if (!is_null($elt->elt)) {
//                        $ticket->elt_fare = $elt->elt->sum('elt_price');
//                    }
//                });
//                $closing->total_income = (int)$closing->closing->sum('ticket_fare') + (int)$closing->closing->sum('elt_fare');
//            });
//            $closing->total_expenses = (int)TicketMergeExpense::where('ticket_merge_id', $closing->id)->sum('amount');
//            $closing->mod = ($closing->closing[0]->tickets->count() + $closing->closing[1]->tickets->count()) * 20;
//            return $closing;
//        });
//        return view('reports.dailySummeryReportEng', [
//            "data" => $closings,
//            "online_terminals" => $onlineTerminalData,
//        ]);


        if (!Auth::check() && $request->path() != "login") {
            return redirect('/login');
        }
        if (Auth::check() && $request->path() == "login") {
            return redirect('/');
        }
        return view('admin.index');
    }


    public function checkForPermission($user, $request)
    {
        $permission = collect($user->role
            ->permissions);
        return $permission->where('name', $request->path())
            ->where('read', true)
            ->first();
    }

    public function logout()
    {
        Auth::logout();
        return redirect("/");
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // return $request;
        $attempt = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        if ($attempt) {
            return response()->json([
                'message' => 'You are Logged In Successfully',
                'success' => true,
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid Credentials !!!!',
                'success' => false,
            ], 401);
        }
    }

    public function doubleCheck(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($request->password, auth()->user()->password)) {
            return response()->json([], 200);
        } else {
            return response()->json([], 403);
        }
    }
}
