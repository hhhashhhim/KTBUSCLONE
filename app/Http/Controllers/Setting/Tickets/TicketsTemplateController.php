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
        if(!checkForSubmenu("tickets"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return TicketsTemplate::with('terminal.city')->where(['company_id' => Auth::user()->company_id])->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-template"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                if (Auth::user()->terminal_id == null) {
                    return response()->json(["errors" => ["users Error" => ["Your Account Don't Have Default Terminal, Assign Terminal First"]]], 422);
                }
                $rules = [
                    'terminal' => 'required',
                    'name' => 'required',
                    'address' => 'required',
                    'uanNumber' => 'required',
                    'termsCondition' => 'required',
                    'footerText' => 'required',
                ];

                $customMessages = [
                    'terminal.required' => 'Please Select Any Terminal',
                    'name.required' => 'Name is required',
                    'address.required' => 'Address is required',
                    'uanNumber.required' => 'UAN Number is required',
                    'termsCondition.required' => 'Terms & Condition is required',
                    'footerText.required' => 'Footer Text is required',
                ];
                $this->validate($request, $rules, $customMessages);
                TicketsTemplate::where('company_id', Auth::user()->company_id)->where('terminal_id', $request->terminal)->where('status', 1)->update(array('status' => 0));
                $template = TicketsTemplate::create([
                    'company_id' => Auth::user()->company_id,
                    'name' => $request->name,
                    'terminal_id' => $request->terminal ?? Auth::user()->terminal_id,
                    'uan' => $request->uanNumber,
                    'phone' => $request->phoneNumber,
                    'show_phone' => $request->show_phone,
                    'footer_text' => $request->footerText,
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
        if(!checkForSubmenu("tickets"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::with('city')->where('company_id', Auth::user()->company_id)->get();
    }


    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-template"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'terminal_id' => 'required',
                    'name' => 'required',
                    'uan' => 'required',
                    'phone' => 'required',
                    'address' => 'required',
                    'terms_condition' => 'required',
                    'footer_text' => 'required',
                ];

                $customMessages = [
                    'terminal_id.required' => 'Please Select Any Terminal',
                    'name.required' => 'Name is required',
                    'uan.required' => 'UAN Number is required',
                    'phone.required' => 'Phone Number is required',
                    'address.required' => 'Terminal Address is required',
                    'terms_condition.required' => 'Terms & Condition is required',
                    'footer_text.required' => 'Footer Text is required',
                ];
                $this->validate($request, $rules, $customMessages);
                TicketsTemplate::where(['terminal_id' => $request->terminal_id, 'company_id'=> Auth::user()->company_id])->where('status', 1)->update(array('status' => 0));
                $template = TicketsTemplate::where('id', $request->id)->update([
                    'terminal_id' => $request->terminal_id,
                    'name' => $request->name,
                    'uan' => plainContactAndCnic($request->uan),
                    'phone' => plainContactAndCnic($request->phone),
                    'show_phone' => $request->show_phone,
                    'footer_text' => $request->footer_text,
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
        if(!checkForSubmenu("ActivityLog"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ActivityLog::with("activity")
            ->where("created_at" , '>', now()->subDays(3))
            ->where("company_id",Auth::user()->company_id) 
            ->select(['*', DB::raw('DATE_FORMAT(created_at, "%h:%i %p | %Y-%m-%d") as formatted_created_at')])
            ->orderBy("created_at","DESC")
            ->get();
    }
}
