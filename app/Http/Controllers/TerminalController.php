<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Models\TerminalAllowedSeatsAdvance;
use App\Models\TerminalAvailableSeat;
use App\Models\TerminalCommission;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    public function index(){
        
        return Terminal::with('added_by')->latest('id')->get();
        
    }
    public function store( Request $request ){
        $this->validate( $request,[
            'name'=>'required',
            'company_id'=>'required_if:'. auth()->user()->is_super_admin.",==,1",
            'city_id'=>'required',
            'contact'=>'required',
        ]);
        
        $terminal = Terminal::create([
            'name'=>$request->name,
            'contact'=>$request->contact,
            'address'=>$request->address??" ",
            'longitude'=>$request->longitude,
            'latitude'=>$request->latitude,
            'time_difference'=>$request->time_difference,
            'order'=>$request->order,
            'active_sms'=>$request->active_sms?1:0,
            'city_id'=>$request->city_id,
            'online_terminal_name'=>$request->online_terminal_name??" ",
            'status'=>$request->active?1:0,
            'added_by'=>auth()->user()->id,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
        ]);

        // TerminalAllowedSeatsAdvance::create([
        //     'terminal_id'=>$terminal->id,
        //     'seats'=>$request->advance_booking,
        //     'added_by'=>$terminal->id,
        // ]);
        // TerminalAvailableSeat::create([
        //     'terminal_id'=>$terminal->id,
        //     'seats'=>$request->advance_booking,
        //     'added_by'=>$terminal->id,
        // ]);
        // TerminalCommission::create([
        //     'terminal_id'=>$terminal->id,
        //     'amount'=>$request->amount,
        //     'added_by'=>$terminal->id,
        //     'per_seat'=>$request->per_seat,
        // ]);
        
        return $this->index();
        
    }
    public function delete( Request $request ){
        return Terminal::find($request->id)->delete();
    }
    public function update( Request $request ){
        
        $this->validate( $request,[
            'name'=>'required',
            'email'=>'bail|required|email|unique:users,email,'.$request->id,
            'password'=>'min:8',
            'role'=>'required',
            'contact'=>'required',
        ]);
        $user = Terminal::find($request->id)->update([
            'name'=>$request->name,
            'email'=>$request->email,
            'contact'=>$request->contact,
            'role_id'=>$request->role,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
        ]);
        return response()->json([
            'message'=>'Updated Successfully',
        ],201);

    }

}
