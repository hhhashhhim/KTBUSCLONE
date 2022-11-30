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

    public function index(){
        return TicketsTemplate::with('terminal.city')->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request){
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

    public function allTerminals(){
      return Terminal::with('city')->where('company_id', $this->company_id)->get();
    }




}
