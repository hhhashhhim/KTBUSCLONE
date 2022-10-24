<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return City::with('addedBy')->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request)
    {
        // return Rule::unique('cities')->where('company_id', $this->company_id)->whereNull('deleted_at');
        $rules = [
            'name' => ['required', Rule::unique('cities','name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'City Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        $city = City::create([
            'name' => $request->name,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
        $this->cityCombinations($city);
        return City::with('addedBy')->find($city->id);
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('cities', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'City Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        return City::find($request->id)->update([
            'name' => $request->name,
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
            'routes' => Route::with('addedBy')->where('company_id', $this->company_id)->get()
        ];

        return $data;
    }

    public function cityTerminals(Request $request)
    {
        return Terminal::where('company_id', $this->company_id)->where('city_id', $request->id)->get();
    }

    public function cityRoutes(Request $request)
    {
        $request->validate([
            'route'=>'required',
            'cities'=>'required',
        ],[
            'route.required'=>'Route Name is Required !!!!'
        ]);
        if ( count( $request->cities ) < 2 ) {
            return response()->json([
                "errors"=>[
                    "Cities Error"=>["Please Select At leat 2 Cities !!!"]
                ]
            ],422);
        }
        foreach ($request['cities'] as $index => $city) {
            $used_cities[] = $city;
            foreach ($request['cities'] as $innerIndex => $innerCity) {
                if (in_array($innerCity, $used_cities)) {
                    continue;
                } else {
                    $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();
                    $fareClasses = FareClass::where('company_id',$this->company_id)->count();
                    //   echo $fare->count()."==";
                    //   echo $fareClasses."<br>";
                    //   echo "<br>line end";
                    
                    if ( $fareClasses==0 || $fare->count() < $fareClasses) {
                        return response()->json([
                            "errors"=>[
                                "Fare Error"=>["Please Fill the Fare Table Completelly First ( For All Fare Classes ) !!!"]
                            ]
                        ],422);
                    }
                }
            }
        }
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
                                'fare_class_id' => $detail->fare_class,
                                'departure_city_id' => $used_cities[$index],
                                'destination_city_id' => $innerCity,
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
        $routeFareCities = RouteFare::where('route_id', $request->id)->where('company_id', $this->company_id)->with('city_to:id,name', 'city_from:id,name', 'fare_details:id,fare,fare_class', 'fare_details.class:id,name')->get()->groupBy(['departure_city_id', 'destination_city_id']);
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
            'th' => FareClass::where('company_id', $this->company_id)->orderBY('name', 'ASC')->get(),
        ];

    }

    public function cityCombinations($city)
    {
        $cities = City::get();
        foreach ($cities as $i => $cityTo) {
            // Creating Relation of Newly added city with Other Cities
            CityToCity::create([
                'departure_city_id' => $city->id,
                'destination_city_id' => $cityTo->id,
                'company_id' => $this->company_id,
                'added_by' => Auth::user()->id,

            ]);
            // Creating Other Cities Relation with Newly added City
            if ($cityTo->id != $city->id) {
                CityToCity::create([
                    'departure_city_id' => $cityTo->id,
                    'destination_city_id' => $city->id,
                    'company_id' => $this->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
        }
    }
}
