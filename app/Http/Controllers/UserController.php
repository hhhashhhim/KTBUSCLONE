<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\User;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(): array
    {
        $users = User::with('role:id,name', 'company:id,name', 'terminal:id,name,city_id', 'terminal.city:id,name')->where('company_id', Auth::user()->company_id)->where('id', '!=', Auth::user()->id)->latest('id')->get();
        foreach ($users as $user) {
            $user->name = ucfirst($user->name);
        }
        return ['users' => $users,
            'authCheck' => is_null(Auth::user()->terminal_id) ? 0 : Auth::user()->terminal_id
        ];
    }

    public function getCities()
    {
        $cities = City::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
        foreach ($cities as $single) {
            $single->name = ucfirst($single->name);
        }
        return $cities;
    }

    public function store(Request $request): array
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
            'contact' => formatContact($request->contact),
            'password' => Hash::make($request->password),
            'role_id' => $request->role,
            'terminal_id' => $request->terminal_id,
            'destination_city_ids' => json_encode($request->destination),
            'departure_city_ids' => json_encode($request->departure),
            'company_id' => Auth::user()->company_id,
        ]);

        UserPassword::create([
            'user_id' => $user->id,
            'user_password' => $request->password,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);

        return $this->index();

    }

    public function edit(Request $request)
    {
        $user = User::with('userpass')->find($request->id);
        $user->departure_city_ids = json_decode($user->departure_city_ids);
        $user->destination_city_ids = json_decode($user->destination_city_ids);
        return $user;
    }

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
            'contact' => !is_null($request->contact) ? formatContact($request->contact) : null,
            'role_id' => $request->role_id,
            'terminal_id' => $request->terminal_id,
            'destination_city_ids' => json_encode($request->destination_city_ids),
            'departure_city_ids' => json_encode($request->departure_city_ids),
            'check_allowed_seats' => $request->check_allowed_seats,
            'company_id' => Auth::user()->company_id,
        ]);
        if ($request->password != "") {
            User::find($request->id)->update([
                'password' => Hash::make($request->password),
            ]);

            UserPassword::where("user_id",$request->id)->update([
                'user_password' => $request->password,
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
