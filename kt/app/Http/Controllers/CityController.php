<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Route\RouteTerminal;
use App\Models\Terminal;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = auth()->user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return City::orderBy('name')->where('company_id', $this->company_id)->select('name', 'id')->get();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        $city = City::create([
            'name' => $request->name,
            'company_id' => $this->company_id,
            'added_by' => auth()->user()->id,
        ]);
        $this->cityCombinations($city);
        return $city;
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        return City::find($request->id)->update([
            'name' => $request->name,
            'added_by' => auth()->user()->id,
        ]);
    }

    public function delete(Request $request)
    {
        return City::find($request->id)->delete();
    }

    public function city_routes_list()
    {
        $data = [
            'cities' => City::orderBy('name')->where('company_id', $this->company_id)->select('name', 'id')->get(),
            'routes' => Route::where('company_id', $this->company_id)->get()
        ];

        return $data;
    }

    public function cityTerminals(Request $request)
    {
        return Terminal::where('company_id', $this->company_id)->where('city_id', $request->id)->get();
    }

    public function cityRoutes(Request $request)
    {
        $route = Route::create([
            'name' => $request['route'],
            'company_id' => $this->company_id,
            'added_by' => auth()->user()->id
        ]);
        foreach ($request['terminals'] as $terminal) {
            RouteTerminal::create([
                'route_id' => $route->id,
                'terminal_id' => $terminal,
                'company_id' => $this->company_id,
                'added_by' => auth()->user()->id
            ]);
        }

        foreach ($request['cities'] as $index => $city) {
            if (isset($request['cities'][$index + 1])) {

                $fare = FareTable::where('from_city_id', $city)->where('to_city_id', $request['cities'][$index + 1])->get();
                if ($fare->count() > 0) {
                    foreach ($fare as $detail) {

                        RouteFare::create([
                            'route_id' => $route->id,
                            'fare_id' => $detail->id,
                            'city_from_id' => $city,
                            'city_to_id' => $request['cities'][$index + 1],
                            'company_id' => $this->company_id,
                            'added_by' => auth()->user()->id
                        ]);
                    }
                }
            }
        }

        return ['message' => 'success'];
    }

    public function city_routes_details(Request $request)
    {
        
       $routeFareCities = RouteFare::where('route_id',1)->get();
       $allCombinations = [];
       foreach ($routeFareCities as $i => $routeFareCity) {

           $destinationCities = RouteFare::where('id','>',$routeFareCity->id)->pluck('city_to_id');
           $combination = [
               'departure_city_id'=>$routeFareCity->city_from_id,
               'destinationCities'=>[
                   $routeFareCity->city_to_id,
                   ...$destinationCities
               ],
           ];
           $combination;
           $allCombinations[] = $combination;
       }

       $fares = [];

       foreach ($allCombinations as $i => $combination) {

           $singleCityFares = FareTable::where('from_city_id',$combination['departure_city_id'])
           ->where('company_id',auth()->user()->company_id)
           ->whereIn('to_city_id',$combination['destinationCities'])
           ->select('fare','fare_class','from_city_id','to_city_id')
           ->with('class:id,name','city_to:id,name','city_from:id,name')
           ->get();

           array_push($fares,...$singleCityFares);

       }
       return collect($fares)->groupBy('fare_class');

//        $routeFares = RouteFare::with('city_from:id,name', 'city_to:id,name', 'fare_details:id,fare,fare_class', 'fare_details.class:id,name')
//            ->where('company_id', $this->company_id)
//            ->where('route_id', $request->id)
//            ->get()->groupBy('city_from_id', 'city_to_id');
//        return $routeFares->map(function ($routes, $i) {
//            $classes = [];
//            foreach ($routes as $i => $route) {
//                $classes[] = $route;
//            }
//            return $classes;
//        });
    }

    public function cityCombinations($city)
    {
        $cities = City::get();
        foreach ($cities as $i => $cityTo) {
            // Creating Relation of Newly added city with Other Cities
            CityToCity::create([
                'departure_city_id' => $city->id,
                'destination_city_id' => $cityTo->id,
            ]);
            // Creating Other Cities Relation with Newly added City
            if ($cityTo->id != $city->id) {
                CityToCity::create([
                    'departure_city_id' => $cityTo->id,
                    'destination_city_id' => $city->id,
                ]);
            }
        }
    }
}
