<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(){
        
        return User::with('role:id,name','company:id,name')->where('id','!=',auth()->user()->id)->latest('id')->get();
        
    }
    public function store( Request $request ){
        
        $this->validate( $request,[
            'name'=>'required',
            'email'=>'bail|required|email|unique:users',
            'password'=>'required',
            'role'=>'required',
            'contact'=>'required',
        ]);
        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'contact'=>$request->contact,
            'password'=>Hash::make($request->password),
            'role_id'=>$request->role,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
        ]);
        return $this->index();
        
    }
    public function delete( Request $request ){
        return User::find($request->id)->delete();
    }
    public function update( Request $request ){
        
        $this->validate( $request,[
            'name'=>'required',
            'email'=>'bail|required|email|unique:users,email,'.$request->id,
            'password'=>'min:8',
            'role'=>'required',
            'contact'=>'required',
        ]);
        $user = User::find($request->id)->update([
            'name'=>$request->name,
            'email'=>$request->email,
            'contact'=>$request->contact,
            'role_id'=>$request->role,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
        ]);
        if ($request->password!="") {
            User::find($request->id)->update([
                'password'=>Hash::make($request->password),
            ]);
        }
        return response()->json([
            'message'=>'Updated Successfully',
        ],201);
    }

    
    
}
