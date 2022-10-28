<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use stdClass;

class AuthController extends Controller
{

    public function index(Request $request)
    {
        // // Getting Already Booked Tickets
        // $tickets = Ticket::where('company_id',1)->where('schedule_id', $request->id)
        // ->whereDate('date', "2022-10-18")->get();
        // $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();

        // // Getting Already Booked Tickets
        // $schedule = Schedule::where('id', 1)
        // ->where('company_id',1)
        // ->select('id', 'selected_bus_class_id','route_id')
        // ->with('bus_class:id,seat_map','singleRoute:id,name','singleRoute.fares:id,route_id,departure_city_id,destination_city_id')->first();

        // // Fare Fetching About the Schedule
        // $departure_city_id = $schedule->singleRoute->fares->first()->departure_city_id;
        // $destination_city_id = $schedule->singleRoute->fares->last()->destination_city_id;
        // $fare =(float) FareTable::where('from_city_id',$departure_city_id)->where('to_city_id',$destination_city_id)
        // ->where('fare_class',$schedule->selected_bus_class_id)
        // ->value('fare');

        // // Looping Throug the each seat of the bus
        // $seatMap = $schedule->bus_class->seat_map;
        // $busClasses = FareClass::get();
        // for ($i = 0; $i < count($seatMap); $i++) {
        //     foreach ($seatMap[$i] as $j => $column) {
        //         $result = isset($column['seatNo'])?array_search($column['seatNo'], $ticketSeatNumbers):false;
        //         if ($result !== false) {
        //             $seatMap[$i][$j]['id'] = $tickets[$result]['id'];
        //             $seatMap[$i][$j]['gender'] = $tickets[$result]['gender'];
        //             $seatMap[$i][$j]['type'] = $tickets[$result]['type'];
        //         }
        //         // print_r($column);
        //         if ( isset($column['class']) ) {
        //             $seatMap[$i][$j]['color'] = $busClasses->where('id',$column['class'])->first()?$busClasses->where('id',$column['class'])->first()->color:'';
        //         }
        //         if ($column['reserved']) {
        //             $seatMap[$i][$j]['fare'] = $fare;
        //         }

        //     }
        // }
        // $schedule->bus_class->seat_map = $seatMap;
        // return $schedule;



        if (!Auth::check() && $request->path() != "login") {
            return redirect('/login');
        }
        if (Auth::check() && $request->path() == "login") {
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

    public function doubleCheck(Request $request)
    {

        $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($request->password, auth()->user()->password)) {
            return response()->json([], 200);
        } else {
            return response()->json([], 403);
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


//  For Route City Mapping
// $city_from['city_to_final'] = $city_from->city_to->whereIn('id',[8,3,1]);
// ["fare"=> "150.00",
//        "fare_class"=> 1,
//        "from_city_id"=> 7,
//        "to_city_id"=> 10,
//        "class"=> [
//        "id"=> 1,
//        "name"=> "Economy"
//        ],
//        "city_to"=> [
//        "id"=> 10,
//        "name"=> "Kharachi"
//        ],
//        "city_from"=> [
//        "id"=> 7,
//        "name"=> "Fsd"
//        ]]
