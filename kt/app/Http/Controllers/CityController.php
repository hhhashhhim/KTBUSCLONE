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

        $used_cities = [];//key can't be same
        foreach ($request['cities'] as $index => $city) {
            $used_cities[] = $city;
            foreach ($request['cities'] as $innerIndex => $innerCity) {
                if (in_array($innerCity, $used_cities)) {
                    continue;
                } else {
                    $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();
                    if ($fare->count() > 0) {
                        foreach ($fare as $detail) {
                            RouteFare::create([
                                'route_id' => $route->id,
                                'fare_id' => $detail->id,
                                'city_from_id' => $used_cities[$index],
                                'city_to_id' => $innerCity,
                                'company_id' => $this->company_id,
                                'added_by' => auth()->user()->id
                            ]);
                        }
                    }
                }
            }
        }
        return ['message' => 'success'];
    }

    public function city_routes_details(Request $request)
    {
        $routeFareCities = RouteFare::with('city_from:id,name', 'city_to:id,name', 'fare_details:id,fare,fare_class')->where('route_id', $request->id)->get()->groupBy('city_from_id');
        $data = [];
        foreach ($routeFareCities as $i => $single) {

            $data[] = $single->unique('city_to_id');


        }
        return $data;
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
