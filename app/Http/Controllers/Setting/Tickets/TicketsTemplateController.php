<?php

namespace App\Http\Controllers\Setting\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Setting\Tickets\TicketTemplateTerminal;
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
        return $ticketsTemplates = TicketsTemplate::
            where('company_id', Auth::user()->company_id)
            ->get()
            ->map(function($template) {
                // Append terminal IDs as an array
                $template->terminal_ids = $template->template_terminals->pluck('terminal_id')->toArray();
                unset($template->template_terminals);
                return $template;
            });
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
                    'uanNumber' => 'required',
                    'termsCondition' => 'required',
                    'footerText' => 'required',
                ];

                $customMessages = [
                    'terminal.required' => 'Please Select Any Terminal',
                    'name.required' => 'Name is required',
                    'uanNumber.required' => 'UAN Number is required',
                    'termsCondition.required' => 'Terms & Condition is required',
                    'footerText.required' => 'Footer Text is required',
                ];
                $this->validate($request, $rules, $customMessages);

                $template = TicketsTemplate::create([
                    'company_id' => Auth::user()->company_id,
                    'name' => $request->name,
                    'uan' => $request->uanNumber,
                    'phone' => $request->phoneNumber,
                    'show_phone' => $request->show_phone,
                    'show_coupen' => $request->show_coupen,
                    'footer_text' => $request->footerText,
                    'address' => $request->address,
                    'terms_condition' => $request->termsCondition,
                    'status' => 1,
                    'added_by' => Auth::user()->id,
                ]);

                foreach($request->terminals as $terminal)
                {
                    TicketTemplateTerminal::create([
                        'ticket_template_id' => $template->id,
                        'terminal_id' => $terminal,
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
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
                    'terminal_ids' => 'required',
                    'name' => 'required',
                    'uan' => 'required',
                    'terms_condition' => 'required',
                    'footer_text' => 'required',
                ];

                $customMessages = [
                    'terminal_ids.required' => 'Please Select Any Terminal',
                    'name.required' => 'Name is required',
                    'uan.required' => 'UAN Number is required',
                    'terms_condition.required' => 'Terms & Condition is required',
                    'footer_text.required' => 'Footer Text is required',
                ];
                $this->validate($request, $rules, $customMessages);
                
                $template = TicketsTemplate::where('id', $request->id)->update([
                    'name' => $request->name,
                    'uan' => $request->uan,
                    'phone' => $request->phone,
                    'show_phone' => $request->show_phone,
                    'show_coupen' => $request->show_coupen,
                    'footer_text' => $request->footer_text,
                    'address' => $request->address,
                    'terms_condition' => $request->terms_condition,
                    'status' => $request->status,
                    'updated_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);

                TicketTemplateTerminal::where(['ticket_template_id' => $request->id, 'company_id'=> Auth::user()->company_id])->delete();
                TicketTemplateTerminal::whereIn('terminal_id', $request->terminal_ids)->delete();
                foreach($request->terminal_ids as $terminal)
                {
                    TicketTemplateTerminal::create([
                        'ticket_template_id' => $request->id,
                        'terminal_id' => $terminal,
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
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
