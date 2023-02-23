<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RouteController extends Controller
{
    public function index(){

        $cities = City::with('city_to:id,name')->get();
        $subRoutes = $cities->map(function ($city_from, $i){
            $city_from['city_to_final'] = $city_from->city_to;
            foreach ($city_from['city_to_final'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['city_to_final'][$j]['fare'] = FareTable::where('from_city_id',$routeCities->departure_city_id)
                ->where('to_city_id',$routeCities->destination_city_id)
                ->value('fare');
            }
            unset($city_from['city_to']);
            return $city_from;
        });

        return $subRoutes;

    }
     public function store(Request $request)
     {
        $request->validate([
            'routeStart' => 'required',
            'routeEnd' => 'required',
            'cities' => 'required',
        ], [
            'route.required' => 'Route Name is Required !!!!'
        ]);
        if (count($request->cities) < 2) {
            return response()->json(["errors" => ["Cities Error" => ["Please Select At leat 2 Cities !!!"]]], 422);
        }
        foreach ($request['cities'] as $index => $city) {
            $used_cities[] = $city;
            foreach ($request['cities'] as $innerIndex => $innerCity) {
                if (in_array($innerCity, $used_cities)) {
                    continue;
                } else {
                    $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();
                    $fareClasses = FareClass::where('company_id', Auth::user()->company_id)->count();

                    if ($fareClasses == 0 || $fare->count() < $fareClasses) {
                        return response()->json([
                            "errors" => [
                                "Fare Error" => ["Please Fill the Fare Table Completely First ( For All Fare Classes ) !!!"]
                            ]
                        ], 422);
                    }
                }
            }
        }
        $route = Route::create([
            'name' => $request['routeStart'] . '-' . $request['routeEnd'],
            'company_id' => Auth::user()->company_id,
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
                                'fare_class_id' => $detail->fare_class,
                                'departure_city_id' => $used_cities[$index],
                                'destination_city_id' => $innerCity,
                                'company_id' => Auth::user()->company_id,
                                'added_by' => auth()->user()->id
                            ]);
                        }
                    }
                }
            }
        }
        if ($request['revereRoute'] == 1) {
            // Reverse Route
            $route = Route::create([
                'name' => $request['routeEnd'] . '-' . $request['routeStart'],
                'company_id' => Auth::user()->company_id,
                'added_by' => auth()->user()->id
            ]);
            $used_cities = [];//key can't be same
            foreach (array_reverse($request['cities']) as $index => $city) {
                $used_cities[] = $city;
                foreach (array_reverse($request['cities']) as $innerIndex => $innerCity) {
                    if (in_array($innerCity, $used_cities)) {
                        continue;
                    } else {
                        $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();

                        if ($fare->count() > 0) {
                            foreach ($fare as $detail) {
                                RouteFare::create([
                                    'route_id' => $route->id,
                                    'fare_id' => $detail->id,
                                    'fare_class_id' => $detail->fare_class,
                                    'departure_city_id' => $used_cities[$index],
                                    'destination_city_id' => $innerCity,
                                    'company_id' => Auth::user()->company_id,
                                    'added_by' => auth()->user()->id
                                ]);
                            }
                        }
                    }
                }
            }
        }
        return ['message' => 'success'];
    }

    public function update(Request $request)
    {
        $request->validate([
            'routeStartName' => 'required',
            'routeEndName' => 'required',
        ]);
        Route::where([
            'company_id' => Auth::user()->company_id,
            'id' => $request->id,
        ])->update([
            'name' => $request['routeStartName'] . '-' . $request['routeEndName'],
        ]);
        return ['message' => 'success'];
    }
    public function list()
    {
        return [
            'cities' => City::orderBy('id')->where('company_id', Auth::user()->company_id)->select('name', 'id')->get(),
            'routes' => Route::with('addedBy')->where('company_id', Auth::user()->company_id)->get()
        ];
    }
    public function details(Request $request){
        $routeFareCities = RouteFare::where('route_id', $request->id)->where('company_id', Auth::user()->company_id)->with('city_to:id,name', 'city_from:id,name', 'fare_details:id,fare,fare_class', 'fare_details.class:id,name')->get()->groupBy(['departure_city_id', 'destination_city_id']);
        $data = [];
        foreach ($routeFareCities as $cities) {
            foreach ($cities as $city) {
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['departure_city'] = $city[0]->city_from->name;
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['destination_city'] = $city[0]->city_to->name;
                foreach ($city as $fare) {
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name] = $fare->fare_details->class->name . '---';
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name . '_fare'] = $fare->fare_details->fare;
                }
            }
        }
        return [
            'data' => $data,
            'th' => FareClass::where('company_id', Auth::user()->company_id)->orderBY('name', 'ASC')->get(),
        ];
    }
}
