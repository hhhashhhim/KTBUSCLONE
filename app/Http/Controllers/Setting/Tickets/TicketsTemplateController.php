<?php

namespace App\Http\Controllers\Setting\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketsTemplateController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });


    }

    public function index()
    {
        return TicketsTemplate::with('terminal.city')->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'terminal' => 'required',
            'uanNumber' => 'required',
            'phoneNumber' => 'required',
            'address' => 'required',
            'termsCondition' => 'required',
        ];

        $customMessages = [
            'terminal.required' => 'Please Select Any Terminal',
            'uanNumber.required' => 'UAN Number is required',
            'phoneNumber.required' => 'Phone Number is required',
            'address.required' => 'Terminal Address is required',
            'termsCondition.required' => 'Terms & Condition is required',
        ];
        $this->validate($request, $rules, $customMessages);
        TicketsTemplate::where('company_id', $this->company_id)->where('status', 1)->update(array('status' => 0));
        return TicketsTemplate::create([
            'company_id' => $this->company_id,
            'terminal_id' => $request->terminal,
            'uan' => str_replace('-', '', $request->uanNumber),
            'phone' => str_replace('-', '', $request->phoneNumber),
            'address' => $request->address,
            'terms_condition' => $request->termsCondition,
            'status' => 1,
            'added_by' => Auth::user()->id,
        ]);

    }

    public function allTerminals()
    {
        return Terminal::with('city')->where('company_id', $this->company_id)->get();
    }


    public function update(Request $request)
    {

        $rules = [
            'terminal_id' => 'required',
            'uan' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'terms_condition' => 'required',
        ];

        $customMessages = [
            'terminal_id.required' => 'Please Select Any Terminal',
            'uan.required' => 'UAN Number is required',
            'phone.required' => 'Phone Number is required',
            'address.required' => 'Terminal Address is required',
            'terms_condition.required' => 'Terms & Condition is required',
        ];
        $this->validate($request, $rules, $customMessages);
        TicketsTemplate::where('company_id', $this->company_id)->where('status', 1)->update(array('status' => 0));
        return TicketsTemplate::where('id', $request->id)->update([
            'terminal_id' => $request->terminal_id,
            'uan' => str_replace('-', '', $request->uan),
            'phone' => str_replace('-', '', $request->phone),
            'address' => $request->address,
            'terms_condition' => $request->terms_condition,
            'status' => $request->status,
            'updated_by' => Auth::user()->id,
        ]);
    }


}
