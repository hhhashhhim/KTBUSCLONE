<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Expense\TicketMergeExpense;
use App\Models\Schedule\Schedule;
use App\Models\Bus\Bus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        $mergeId = 39;
        $closings_ids = TicketClosing::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$mergeId])->pluck('id');
        $data = (object)[];
        $data->schedule_start = Ticket::where(["company_id"=>Auth::user()->company_id])->where("ticket_closing_id",$closings_ids[0])->with('terminal:id,name')->get()->groupBy(['terminal_id']);
        $data->schedule_return = Ticket::where(["company_id"=>Auth::user()->company_id])->where("ticket_closing_id",$closings_ids[1])->with('terminal:id,name')->get()->groupBy(['terminal_id']);
        $data->expense = TicketMergeExpense::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$mergeId])->with("expense_category:id,name")->get();

        // get bus number
        $busId = TicketClosingMerge::where("id",$mergeId)->first()->bus_id;
        $singleData = (object)[];
        $singleData->bus_number = Bus::where(["company_id"=>Auth::user()->company_id,"id"=>$busId])->first()->bus_number;

        // get route both side
        $schedule_ids = TicketClosing::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$mergeId])->pluck('schedule_id');
        $schedule = Schedule::where(["company_id"=>Auth::user()->company_id])->whereIn("id",$schedule_ids)->with("route")->get();
        $singleData->city_one =  explode("-",$schedule[0]->route->name)[0];
        $singleData->city_two =  explode("-",$schedule[1]->route->name)[0];
        // return $data;
        // return view('reports.dailySaleReport',[
        //     "singleData" => $singleData,
        //     "data" => $data
        // ]);
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
