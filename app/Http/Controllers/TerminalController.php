<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TerminalController extends Controller
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
        return City::withCount('terminal')->with('addedBy')->where('company_id', $this->company_id)->get();
    }
    public function getTerminal(Request $request)
    {
        return Terminal::with('addedBy')->where('city_id', $request->id)->where('company_id', $this->company_id)->get();
    }
    public function store(Request $request)
    {

        $rules = [
            'name' => ['required', Rule::unique('terminals', 'name')->where('city_id', $request->city_id)->where('company_id', $this->company_id)->whereNull('deleted_at')],
            'city_id' => 'required',
            'contact' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Terminal Name already exist against This City',
            'city_id.required' => 'Please Select Any City ',
            'contact.required' => 'Please Enter your Phone Number   '
        ];
        $this->validate($request, $rules, $customMessages);
        if ($request->is_main) {
            $main = Terminal::where('city_id', $request->city_id)
                ->where('company_id', $this->company_id)
                ->where('is_main', 1)
                ->first();
            if ($main) {
                return response()->json([
                    'is_main' => 'City can have only One terminal as its main'
                ], 423);
            }
        }
        $contact_format = str_replace('-', '', $request->contact);
        Terminal::create([
            'name' => $request->name,
            'contact' => $contact_format,
            'address' => $request->address ?? " ",
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'time_difference' => $request->time_difference,
            'available_seats' => $request->available_seats,
            'advance_booking' => $request->advance_booking,
            'active_sms' => $request->active_sms ? 1 : 0,
            'city_id' => $request->city_id,
            'online_terminal_name' => $request->online_terminal_name ?? " ",
            'status' => $request->active ? 1 : 0,
            'is_main' => $request->is_main ? 1 : 0,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->is_super_admin == 0 ? $this->company_id : $request->company_id,
        ]);

        return $this->index();
    }
    public function  delete(Request $request)
    {
        return Terminal::find($request->id)->delete();
    }
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'contact' => 'required',
        ]);
        $contact_format = str_replace('-', '', $request->contact);
        Terminal::find($request->id)->update([
            'name' => $request->name,
            'contact' => $contact_format,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'time_difference' => $request->time_difference,
            'advance_booking' => $request->advance_booking,
            'available_seats' => $request->available_seats,
            'city_id' => $request->city_id,
            'online_terminal_name' => $request->online_terminal_name,
            'is_main' => (int) $request->is_main ,
            'active_sms' => $request->active_sms ? 1 : 0,
            'status' => (int) $request->status,
        ]);
        return response()->json([
            'message' => 'Updated Successfully',
        ], 201);
    }
}
