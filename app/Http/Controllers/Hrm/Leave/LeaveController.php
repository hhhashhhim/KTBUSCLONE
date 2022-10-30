<?php

namespace App\Http\Controllers\Hrm\Leave;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Leave\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    Public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }
    public function index(){

    }
    public function store(Request $request){
    dd($this->dateDifferenceInDays($request->from, $request->to));
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
            'from'=>$request->from,
            'to'=>$request->to,
            'reason'=>$request->reason,
            'added_by'=>Auth::user()->id,
            'company_id'=>$this->company_id,
            'company_id'=>$this->company_id,
        ]);

    }
    public function update(Request $request){

    }
    public function delete(Request $request){

    }
    public function dateDifferenceInDays($from, $to){
        $diff = strtotime($from) -  strtotime($to);
        return round(abs(round($diff / 86400)));
    }

}
