<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Terminal;
use App\Models\TerminalCommission;
use App\Models\TerminalDiscount;
use App\Models\Route\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TerminalController extends Controller
{
    public function index()
    {
        return City::withCount('terminal')->with('addedBy')->where('company_id', Auth::user()->company_id)->get();
    }

    public function allTerminals()
    {
        return [
            'terminals' => Terminal::with('city')->where('company_id', Auth::user()->company_id)->get(['id', 'name', 'city_id']),
            'authTerminalId' => Auth::user()->terminal_id,
        ];
    }

    public function getTerminal(Request $request)
    {
        return Terminal::with('addedBy')->where('city_id', $request->id)->where('company_id', Auth::user()->company_id)->get();
    }

    public function getRoutes(Request $request)
    {
        return Route::where('company_id', Auth::user()->company_id)->get(["id", "name"]);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('terminals', 'name')->where('city_id', $request->city_id)->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            'city_id' => 'required',
            'contact' => 'required',
//            'commission' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Terminal Name already exist against This City',
            'city_id.required' => 'Please Select Any City ',
            'contact.required' => 'Please Enter your Phone Number',
//            'commission.required' => 'Please Enter Commission',
//            'flatCommission.required' => 'Please Enter Flat Commission',
//            'percentageCommission.required' => 'Please Enter Percentage Commission',
        ];
        $this->validate($request, $rules, $customMessages);
        if ($request->is_main) {
            $main = Terminal::where('city_id', $request->city_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('is_main', 1)
                ->first();
            if ($main) {
                return response()->json([
                    'is_main' => 'City can have only One terminal as its main'
                ], 423);
            }
        }
        Terminal::create([
            'name' => $request->name,
            'contact' => plainContactAndCnic($request->contact),
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
            'fixed_commission' => $request->commission ?? 0,
            'ticket_flat_commission' => $request->flatCommission ?? 0,
            'ticket_percentage_commission' => $request->percentageCommission ?? 0,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->is_super_admin == 0 ? Auth::user()->company_id : $request->company_id,
        ]);

        return $this->index();
    }

    public function delete(Request $request)
    {
        return Terminal::find($request->id)->delete();
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'contact' => 'required',
        ]);
        if ($request->is_main) {
            $main = Terminal::where('city_id', $request->city_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('is_main', 1)
                ->where('id', '!=', $request->id)
                ->first();
            if ($main) {
                return response()->json([
                    'is_main' => 'City can have only One terminal as its main'
                ], 423);
            }
        }
        Terminal::find($request->id)->update([
            'name' => $request->name,
            'contact' => plainContactAndCnic($request->contact),
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'time_difference' => $request->time_difference,
            'advance_booking' => $request->advance_booking,
            'available_seats' => $request->available_seats,
            'city_id' => $request->city_id,
            'online_terminal_name' => $request->online_terminal_name,
            'is_main' => (int) $request->is_main,
            'fixed_commission' => $request->fixed_commission ?? 0,
            'ticket_flat_commission' => $request->ticket_flat_commission ?? 0,
            'ticket_percentage_commission' => $request->ticket_percentage_commission ?? 0,
            'active_sms' => $request->active_sms ? 1 : 0,
            'status' => (int) $request->status,
        ]);
        return response()->json([
            'message' => 'Updated Successfully',
        ], 201);
    }
    public function terminalCommissions(Request $request)
    {

        $terminalCommission = TerminalCommission::where(["terminal_id"=>$request->terminal_id,'company_id'=> Auth::user()->company_id])->orderBy('id')->get();
        $terminal = Terminal::where(["id"=>$request->terminal_id,'company_id'=> Auth::user()->company_id])->first();
        return [
            "terminalCommission" => $terminalCommission,
            "terminal" => $terminal,
        ];
    }
    public function commissionStore(Request $request)
    {
        $request->validate([
            "terminal_id" => 'required',
            "route" => 'required',
            "fixCommission" => 'required',
            "flatCommission" => 'required',
            "percentCommission" => 'required',
            "adjustmentCommission" => 'required',
        ]);
        TerminalCommission::where("terminal_id",$request->terminal_id)->delete();
        foreach ($request->route as $key => $value) {
            $checkExist = TerminalCommission::where(["terminal_id"=>$request->terminal_id,"route_id"=>$request->route[$key],'company_id'=> Auth::user()->company_id])->first();
            if(!$checkExist)
            {
                TerminalCommission::create([
                    'terminal_id' => $request->terminal_id,
                    'route_id' => $request->route[$key],
                    'fix_commission' => $request->fixCommission[$key],
                    'flat_commission' => $request->flatCommission[$key],
                    'percentage_commission' => $request->percentCommission[$key],
                    'adjustment_commission' => $request->adjustmentCommission[$key],
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
        }
    }
    public function terminalDiscounts(Request $request)
    {

        $terminalDiscount = TerminalDiscount::where(["terminal_id"=>$request->terminal_id,'company_id'=> Auth::user()->company_id])->orderBy('id')->get();
        $terminal = Terminal::where(["id"=>$request->terminal_id,'company_id'=> Auth::user()->company_id])->first();
        return [
            "terminalDiscount" => $terminalDiscount,
            "terminal" => $terminal,
        ];
    }
    public function discountStore(Request $request)
    {
        $request->validate([
            "terminal_id" => 'required',
            "route" => 'required',
            "discount" => 'required',
            "startDate" => 'required',
            "endDate" => 'required',
        ]);

        TerminalDiscount::where("terminal_id",$request->terminal_id)->delete();
        foreach ($request->route as $key => $value) {
            $checkExist = TerminalDiscount::where(["terminal_id"=>$request->terminal_id,"route_id"=>$request->route[$key],'company_id'=> Auth::user()->company_id])->first();
            if(!$checkExist)
            {
                TerminalDiscount::create([
                    'terminal_id' => $request->terminal_id,
                    'route_id' => $request->route[$key],
                    'discount' => $request->discount[$key],
                    'start_date' => $request->startDate[$key],
                    'end_date' => $request->endDate[$key],
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
        }
    }

}
