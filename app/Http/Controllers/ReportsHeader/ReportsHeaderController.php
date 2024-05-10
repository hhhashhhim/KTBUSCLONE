<?php

namespace App\Http\Controllers\ReportsHeader;

use App\Http\Controllers\Controller;
use App\Models\ReportsHeader;
use App\Models\ReportHeaderLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportsHeaderController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("report-header"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        if(!checkForSubmenu("report-header"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required' => Rule::unique('reports_headers', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Header is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);

                $header =  ReportsHeader::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added report header ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $header;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkForSubmenu("report-header"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required' => Rule::unique('reports_headers', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Header is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                $expCtg = ReportsHeader::find($request->id);
                $data = $expCtg->update([
                    'name' => $request->name,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated report header ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $data;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function linkGet(Request $request)
    {
        if(!checkForSubmenu("report-header"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $links = ReportHeaderLink::where(['company_id'=> Auth::user()->company_id,'ticket_merge_id'=>$request->ticket_merge_id])->get();
        $headers = ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
        if($links->count() > 0)
        {
            return [
                "headers" => $headers,
                "links" => $links
            ];
        }
        else
        {
            return [
                "headers" => $headers,
                "links" => null
            ];
        }
    }

    public function headerLink(Request $request)
    {
        if(!checkForSubmenu("report-header"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                ReportHeaderLink::where("ticket_merge_id", $request->ticket_merge_id)->delete();
                foreach ($request->headIds as $key => $value) {
                    ReportHeaderLink::create([
                        'ticket_merge_id' => $request->ticket_merge_id,
                        'header_id' => $request->headIds[$key],
                        'value' => $request->values[$key],
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | linked report header",
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
