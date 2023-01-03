<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FareTableController extends Controller
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

    public function store(Request $request)
    {
        $request->validate([
            'fare' => 'required',
            'fare_class' => 'required',
        ]);

        $company_id = auth()->user()->is_super_admin == 0 ? auth()->user()->company_id : $request->company_id;
        if ($request->created == 1) {
            updateFare($request, $company_id);
        } else {
           storeFare($request, $company_id);
        }
        return $this->getFarePrices($request->fare_class);

    }

    public function record(Request $request)
    {

        $request->validate([
            'fare_class' => 'required',
        ]);

        return $this->getFarePrices($request->fare_class);
    }

    public function getFarePrices($fare_class)
    {
        $cities = City::with(['city_to' => function ($q) { $q->orderBy('name')->where('cities.company_id', Auth::user()->company_id);
        }])
            ->where('cities.company_id', Auth::user()->company_id)
            ->orderBy('name')->get();

        $subRoutes = $cities->map(function ($city_from) use ($fare_class) {

            // Storing Destination Cities into new Array Index
            $city_from['destinationCities'] = $city_from->city_to;

            // Fetching and storing the fare of the Departure and the Destination city Fare.
            foreach ($city_from['destinationCities'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['destinationCities'][$j]['fare'] = FareTable::where('from_city_id', $routeCities->departure_city_id)
                    ->where('to_city_id', $routeCities->destination_city_id)
                    ->where('fare_class', $fare_class)
                    ->value('fare');
            }
            unset($city_from['city_to']);
            return $city_from;
        });

        return $subRoutes;
    }

    public function getFareClass()
    {
        return FareClass::where('company_id', Auth::user()->company_id)->orderBy('id')->select('id', 'name')->get(['name', 'id']);
    }

    public function check(Request $request)
    {
        $checkFare = FareTable::where('fare_class', $request->fare_class)->where('from_city_id', $request->from)->where('to_city_id', $request->to)->where('company_id', Auth::user()->company_id)->select('id', 'fare', 'distance_in_km', 'time_difference', 'fare_class')->first();
        return response($checkFare, 200);
    }


}
