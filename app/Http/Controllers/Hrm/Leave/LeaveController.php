<?php

namespace App\Http\Controllers\Hrm\Leave;

use App\Http\Controllers\Controller;
use App\Models\admin\Role;
use App\Models\Hrm\Leave\Leave;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("leaves"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $role = Role::where('company_id', Auth::user()->company_id)->where('id', Auth::user()->role_id)->get(['name']);
        if($role == 'admin') {
            return Leave::with('addedBy', 'company', 'decision')->where('company_id', Auth::user()->company_id)->get();
        }
        if($role != 'admin'){
            return Leave::with('addedBy', 'company', 'decision')->where('company_id', Auth::user()->company_id)->where('added_by', Auth::user()->id)->get();
        }
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("apply-leave"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'from' => 'required',
                    'to' => 'required',
                    'reason' => 'required',
                ];

                $customMessages = [
                    'from.required' => 'From/Start Date is Required!',
                    'to.required' => 'To / End Date is Required is Required!',
                    'reason.required' => 'Please Enter Leave Reason!',
                ];
                $this->validate($request, $rules, $customMessages);
                $leave =  Leave::create([
                    'from' => $request->from,
                    'to' => $request->to,
                    'reason' => $request->reason,
                    'days' => $this->dateDifferenceInDays($request->from, $request->to),
                    'status' => 'P',
                    'applied_by' => Auth::user()->id,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | applied leave from $request->from to $request->to",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $leave;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-leave"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'from' => 'required',
                    'to' => 'required',
                    'reason' => 'required',
                ];

                $customMessages = [
                    'from.required' => 'From/Start Date is Required!',
                    'to.required' => 'To / End Date is Required is Required!',
                    'reason.required' => 'Please Enter Leave Reason!',
                ];
                $this->validate($request, $rules, $customMessages);
                $leave =  Leave::where('id', $request->id)->update([
                    'from' => $request->from,
                    'to' => $request->to,
                    'reason' => $request->reason,
                    'days' => $this->dateDifferenceInDays($request->from, $request->to),
                    'status' => 'P',
                    'applied_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated leave from $request->from to $request->to",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $leave;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }


    }

    // public function delete(Request $request)
    // {
    //     return Leave::find($request->id)->delete();
    // }

    public function dateDifferenceInDays($from, $to)
    {
        $diff = strtotime($from) - strtotime($to);
        $finalDate = (int)round(abs(round($diff / 86400)));
        return $finalDate == 0 ? 1 : $finalDate + 1;
    }

    public function approval(Request $request)
    {
        if(!checkForSubmenu("leaves"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
         Leave::where('id', $request->id)->update([
            'status'=>$request->status,
            'decider_id'=>Auth::user()->id,

        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | change status of leave $request->status",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return Leave::with('addedBy', 'company', 'decision')->where('id', $request->id)->where('company_id', Auth::user()->company_id)->get();
    }

}
