<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    public function index(){
        
        return Terminal::with('company:id,name')->where('id','!=',auth()->user()->id)->latest('id')->get();
        
    }
    public function store( Request $request ){
        $this->validate( $request,[
            'name'=>'required',
            'company_id'=>'required_if:'. auth()->user()->is_super_admin.",==,1",
            'city_id'=>'required',
        ]);
        
        $user = Terminal::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'contact'=>$request->contact,
            'role_id'=>$request->role,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
        ]);
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
