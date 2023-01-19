<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
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
        return ['users' => User::with('role:id,name', 'company:id,name', 'terminal:id,name,city_id', 'terminal.city:id,name')->where('company_id', Auth::user()->company_id)->where('id', '!=', Auth::user()->id)->latest('id')->get(),
            'authCheck' => is_null(Auth::user()->terminal_id) ? 0 : Auth::user()->terminal_id];
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'bail|required|email|unique:users',
            'password' => 'required',
            'role' => 'required',
            'contact' => 'required',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact' => plainContact($request->contact),
            'password' => Hash::make($request->password),
            'role_id' => $request->role,
            'terminal_id' => $request->terminal_id,
            'company_id' => Auth::user()->company_id,
        ]);
        return $this->index();

    }

//    public function delete(Request $request)
//    {
//        return User::find($request->id)->delete();
//    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'bail|required|email|unique:users,email,' . $request->id,
            'password' => 'min:8',
            'role_id' => 'required',
            'contact' => 'required',
        ]);
        $user = User::find($request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'contact' => !is_null($request->contact) ? plainContact($request->contact) : null,
            'role_id' => $request->role_id,
            'terminal_id' => $request->terminal_id,
            'company_id' => Auth::user()->company_id,
        ]);
        if ($request->password != "") {
            User::find($request->id)->update([
                'password' => Hash::make($request->password),
            ]);
        }
        return response()->json([
            'message' => 'Updated Successfully',
        ], 201);
    }

    public function updateTerminal(Request $request)
    {
        $user = User::where(['id' => Auth::user()->id, 'company_id' => Auth::user()->company_id])->first();
        $user->terminal_id = $request->terminal_id;
        $user->save();
        return $user;
    }

}
