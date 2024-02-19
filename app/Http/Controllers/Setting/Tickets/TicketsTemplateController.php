<?php

namespace App\Http\Controllers\Setting\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Terminal;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketsTemplateController extends Controller
{
    public function index()
    {
        return TicketsTemplate::with('terminal.city')->where(['company_id' => Auth::user()->company_id])->get();
    }

    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                if (Auth::user()->terminal_id == null) {
                    return response()->json(["errors" => ["users Error" => ["Your Account Don't Have Default Terminal, Assign Terminal First"]]], 422);
                }
                $rules = [
                    'terminal' => 'required',
        //            'uanNumber' => 'required',
                    'termsCondition' => 'required',
                ];

                $customMessages = [
                    'terminal.required' => 'Please Select Any Terminal',
        //            'uanNumber.required' => 'UAN Number is required',
                    'termsCondition.required' => 'Terms & Condition is required',
                ];
                $this->validate($request, $rules, $customMessages);
        //        $terminal = Terminal::where('company_id', Auth::user()->company_id)->where('id',Auth::user()->terminal_id)->first();
                TicketsTemplate::where('company_id', Auth::user()->company_id)->where('terminal_id', $request->terminal)->where('status', 1)->update(array('status' => 0));
                $template = TicketsTemplate::create([
                    'company_id' => Auth::user()->company_id,
                    'terminal_id' => $request->terminal ?? Auth::user()->terminal_id,
                    'uan' => '03111777333',
                    'phone' => $request->phoneNumber,
                    'address' => $request->address,
                    'terms_condition' => $request->termsCondition,
                    'status' => 1,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added ticket template",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $template;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function allTerminals()
    {
        return Terminal::with('city')->where('company_id', Auth::user()->company_id)->get();
    }


    public function update(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'terminal_id' => 'required',
        //            'uan' => 'required',
                    'phone' => 'required',
                    'address' => 'required',
                    'terms_condition' => 'required',
                ];

                $customMessages = [
                    'terminal_id.required' => 'Please Select Any Terminal',
        //            'uan.required' => 'UAN Number is required',
                    'phone.required' => 'Phone Number is required',
                    'address.required' => 'Terminal Address is required',
                    'terms_condition.required' => 'Terms & Condition is required',
                ];
                $this->validate($request, $rules, $customMessages);
                TicketsTemplate::where('company_id', Auth::user()->company_id)->where('status', 1)->update(array('status' => 0));
                $template = TicketsTemplate::where('id', $request->id)->update([
                    'terminal_id' => $request->terminal_id,
        //            'uan' => plainContactAndCnic($request->uan),
                    'phone' => plainContactAndCnic($request->phone),
                    'address' => $request->address,
                    'terms_condition' => $request->terms_condition,
                    'status' => $request->status,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated ticket template",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $template;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function activityLog(Request $request)
    {
        return ActivityLog::with("activity")
            ->limit(15)
            ->offset(1)
            ->where("company_id",Auth::user()->company_id) 
            ->select(['*', DB::raw('DATE_FORMAT(created_at, "%h:%i %p | %Y-%m-%d") as formatted_created_at')])
            ->orderBy("created_at","DESC")
            ->get();
    }
}
