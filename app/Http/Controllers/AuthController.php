<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(Request $request)
    {   
        if (!Auth::check()  && $request->path() != "login") {
            return redirect('/login');
        }
        if (Auth::check()  && $request->path() == "login") {
            return redirect('/');
        }
        // $user = Auth::user();
        // if ( $request->path()!="login" && !$this->checkForPermission($user,$request) ) {
        //     return abort(404);
        // }
        return view('admin.index');
    }
    public function checkForPermission($user, $request)
    {
        $permission = collect($user->role
            ->permissions);
        return $permission->where('name', $request->path())
            ->where('read', true)
            ->first();
    }
    public function logout()
    {
        Auth::logout();
        return redirect("/");
    }
    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // return $request;
        $attempt = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        if ($attempt) {
            return response()->json([
                'message' => 'You are Logged In Successfully',
                'success' => true,
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid Credentials !!!!',
                'success' => false,
            ], 401);
        }
    }
}







// $cities = City::leftjoin('cities as cities_to', 'cities.id', '!=', 'cities_to.id')
// ->select('cities.id as from_id', 'cities.name as from_name', 'cities_to.id as to_id', 'cities_to.name as to_name')->groupBy('from_name');

// foreach($cities as $i => $city){
// $cities[$i]->push([
//     "from_id"=> $city[0]->from_id,
//     "from_name"=> $i,
//     "to_id"=> $city[0]->to_id,
//     "to_name"=> $i
// ]);
// }
// $farePrices = FareTable::rightjoin(
// DB::raw('(' . $cities->toSql() . ') as cities'),
// function ($join) use ($cities) {
//     $join->on('fare_tables.from_city_id', '=', 'cities.from_id')
//         ->on('fare_tables.to_city_id', '=', 'cities.to_id');
// }
// )->select('cities.*', 'fare_tables.fare')->orderBy('from_name')->orderBy('to_name')->get()->groupBy('from_name');
