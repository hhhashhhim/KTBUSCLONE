<?php

namespace App\Http\Controllers\Hrm\Leave;

use App\Http\Controllers\Controller;
use App\Models\admin\Role;
use App\Models\Hrm\Leave\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
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

    public function index()
    {
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
        return Leave::create([
            'from' => $request->from,
            'to' => $request->to,
            'reason' => $request->reason,
            'days' => $this->dateDifferenceInDays($request->from, $request->to),
            'status' => 'P',
            'applied_by' => Auth::user()->id,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);

    }

    public function update(Request $request)
    {
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
        return Leave::where('id', $request->id)->update([
            'from' => $request->from,
            'to' => $request->to,
            'reason' => $request->reason,
            'days' => $this->dateDifferenceInDays($request->from, $request->to),
            'status' => 'P',
            'applied_by' => Auth::user()->id,
        ]);


    }

    public function delete(Request $request)
    {
        return Leave::find($request->id)->delete();
    }

    public function dateDifferenceInDays($from, $to)
    {
        $diff = strtotime($from) - strtotime($to);
        $finalDate = (int)round(abs(round($diff / 86400)));
        return $finalDate == 0 ? 1 : $finalDate + 1;
    }

    public function approval(Request $request)
    {
         Leave::where('id', $request->id)->update([
            'status'=>$request->status,
            'decider_id'=>Auth::user()->id,

        ]);
        return Leave::with('addedBy', 'company', 'decision')->where('id', $request->id)->where('company_id', Auth::user()->company_id)->get();
    }

}
