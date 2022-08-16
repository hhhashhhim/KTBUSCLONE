<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index( Request $request ){
        // return Hash::make("admin123");
        if ( !Auth::check()  && $request->path()!="login" ) {
            return redirect('/login');
        }
        if ( Auth::check()  && $request->path()=="login" ) {
            return redirect('/');
        }
        // $user = Auth::user();
        // if ( $request->path()!="login" && !$this->checkForPermission($user,$request) ) {
        //     return abort(404);
        // }
        return view('admin.index');
    }
    public function checkForPermission( $user,$request ){
        $permission = collect($user->role
        ->permissions);
        return $permission->where('name',$request->path())
        ->where('read',true)
        ->first();
    }
    public function logout(){
        Auth::logout();
        return redirect("/");
    }
    public function login( Request $request ){

        $this->validate( $request,[
            'email'=>'required|email',
            'password'=>'required|min:8',
        ]);

        // return $request;
        $attempt = Auth::attempt(['email'=>$request->email,'password'=>$request->password]);
        if ($attempt) {
            return response()->json([
                'message'=>'You are Logged In Successfully',
                'success'=>true,
            ]);
        }else {
            return response()->json([
                'message'=>'Invalid Credentials !!!!',
                'success'=>false,
            ],401);
        }
    }
}
