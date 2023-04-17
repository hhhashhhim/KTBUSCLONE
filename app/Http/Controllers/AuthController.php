<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense\TicketMergeExpense;
use App\Models\ReportHeaderLink;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        // $tickets = Ticket::with('addedBy:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time')
        //     ->where('company_id', Auth::user()->company_id)
        //     ->where('type', 'booked')
        //     ->where('terminal_id', 10)
        //     ->when($request->terminal, function ($query) use ($request) {
        //         return $query->where('terminal_id', $request->terminal);
        //     })
        //     ->when($request->user, function ($query) use ($request) {
        //         return $query->where('added_by', $request->user);
        //     })
        //     ->when($request->route, function ($query) use ($request) {
        //         $scheduleIds = Schedule::where('route_id', $request->route)->pluck('id');
        //         return $query->whereIn('schedule_id', $scheduleIds);
        //     })->get();

        // $tickets->transform(function ($single) {
        //     $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule->time));
        //     return $single;
        // });

        // $tickets = $tickets->when($request->fromDateTime, function ($query) use ($request) {
        //     return $query->where('schedule_date_time', '>=', $request->fromDateTime);
        // })
        //     ->when($request->toDateTime, function ($query) use ($request) {
        //         return $query->where('schedule_date_time', '<=', $request->toDateTime);
        //     })
        //     ->groupBy(['schedule_date_time', 'added_by']);

        // $sortData = [];
        // // $tickets->map(function($outer) use ($sortData){
        //     // $outer->map(function($inner) use ($sortData){
        //         // $single = [];
        //         // $single['a'] = 'helo';
        //         // array_push($sortData, 'abc');
                
        //     // });
        // // });
        // foreach($tickets as $outer)
        // {
        //     foreach($outer as $inner)
        //     {
        //         $single = [];
        //         $single['bus_class'] = $inner[0]->busClass->name;
        //         $single['seats'] = $inner->count();
        //         $single['terminal'] = $inner[0]->terminal->name;
        //         $single['user'] = $inner[0]->addedBy->name;
        //         $single['sales'] = $inner->sum('seat_fare') - $inner->sum('discount');
        //         array_push($sortData, $single);
        //     }
        // }
        // return $sortData;


//        $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')->where('company_id', Auth::user()->company_id)->where('type', 'canceled')->withTrashed()->get();
//        $tickets->map(function ($q) {
//            $q->cancel_percentage = $q->cancel_ticket->percentage;
//            $q->cancel_reason = $q->cancel_ticket->reason;
//            $q->cancel_by = User::find($q->cancel_ticket->added_by)->name;
//            $q->cancel_date = $q->cancel_ticket->time;
//            $q->bus_time = date('Y-m-d',strtotime($q->cancel_ticket->time)).' '. date('H:i:s', strtotime($q->schedule->time));
//            $q->passenger_name = Customer::find($q->customer_id)->name;
//            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
//            unset($q->cancel_ticket, $q->schedule);
//        });
//        return $tickets;


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
            ], 200);
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
