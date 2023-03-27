<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Schedule\Schedule;
use App\Models\Bus\Bus;
use App\Models\Ticket;
use App\Models\Expense\TicketMergeExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{

//    public $company_id;
//
//    public function __construct()
//    {
//        $this->middleware(function ($request, $next) {
//            Auth::user()->company_id = Auth::user()->company_id;
//            return $next($request);
//        });
//    }

    public function index(Request $request)
    {
        return TicketMergeExpense::where(["ticket_merge_id" => $request->ticket_merge_id, 'company_id' => Auth::user()->company_id])->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            "ticket_merge_id" => 'required',
            "category" => 'required',
            "description" => 'required',
            "amount" => 'required',
            "invoice" => 'required',
        ]);

        TicketMergeExpense::where("ticket_merge_id", $request->ticket_merge_id)->delete();
        foreach ($request->category as $key => $value) {
            TicketMergeExpense::create([
                'ticket_merge_id' => $request->ticket_merge_id,
                'expense_category_id' => $request->category[$key],
                'description' => $request->description[$key],
                'amount' => $request->amount[$key],
                'invoice' => $request->invoice[$key],
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }
    }


    public function dailySummery(Request $request)
    {
        $closings_ids = TicketClosing::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$request->ticket_merge_id])->pluck('id');
        $data = (object)[];
        $data->schedule_start = Ticket::where(["company_id"=>Auth::user()->company_id])->where("ticket_closing_id",$closings_ids[0])->with('terminal:id,name')->get()->groupBy(['terminal_id']);
        $data->schedule_return = Ticket::where(["company_id"=>Auth::user()->company_id])->where("ticket_closing_id",$closings_ids[1])->with('terminal:id,name')->get()->groupBy(['terminal_id']);
        $data->expense = TicketMergeExpense::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$request->ticket_merge_id])->with("expense_category:id,name")->get();

        // get bus number
        $busId = TicketClosingMerge::where("id",$request->ticket_merge_id)->first()->bus_id;
        $singleData = (object)[];
        $singleData->bus_number = Bus::where(["company_id"=>Auth::user()->company_id,"id"=>$busId])->first()->bus_number;

        // get route both side
        $schedule_ids = TicketClosing::where(["company_id"=>Auth::user()->company_id,"ticket_merge_id"=>$request->ticket_merge_id])->pluck('schedule_id');
        $schedule = Schedule::where(["company_id"=>Auth::user()->company_id])->whereIn("id",$schedule_ids)->with("route")->get();
        $singleData->city_one =  explode("-",$schedule[0]->route->name)[0];
        $singleData->city_two =  explode("-",$schedule[1]->route->name)[0];
         return view('reports.dailySaleReport',[
             "singleData" => $singleData,
             "data" => $data
         ]);
    }
    // public function update(Request $request)
    // {
    //     $rules = [
    //         'name' => ['required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
    //     ];

    //     $customMessages = [
    //         'name.required' => 'Name Field is Required!',
    //         'name.unique' => 'Category Name is Already Exist',
    //     ];
    //     $this->validate($request, $rules, $customMessages);
    //     return ExpenseCategory::find($request->id)->update([
    //         'name' => $request->name,
    //     ]);
    // }

    // public function delete(Request $request)
    // {
    //     return City::find($request->id)->delete();
    // }
}
