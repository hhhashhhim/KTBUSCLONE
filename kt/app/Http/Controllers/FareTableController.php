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
    public $company_id;

    public function __construct(){
        $this->middleware(function ($request, $next){
            $this->company_id = auth()->user()->company_id;
            return $next( $request );
        });
    }
    public function store( Request $request ){

        $request->validate([
            'fare'=>'required',
            'fare_class'=>'required',
        ]);
        $company_id = auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id;
        $fare1Side = FareTable::create([
            'fare'=>$request->fare,
            'from_city_id'=>$request->from,
            'to_city_id'=>$request->to,
            'fare_class'=>$request->fare_class,
            'company_id'=>$company_id,
            'commission_flat'=>$request->commission_flat,
            'commission_percentage'=>$request->commission_percentage,
            'terminal_commission'=>$request->terminal_commission,
            'time_difference'=>$request->time_difference,
            'surcharge'=>$request->surcharge,
            'surcharge_start_date'=>$request->surcharge_start_date,
            'surcharge_end_date'=>$request->surcharge_end_date,
            'advance_availability'=>$request->advance_availability,
            'added_by'=>auth()->user()->id,
        ]);

        $routeFares1Side = RouteFare::where('city_from_id',$fare1Side->from_city_id)
        ->where('city_to_id',$fare1Side->to_city_id)
        ->get();
        foreach ($routeFares1Side as $i => $routeFare) {
            $routeFare->fare_id = $fare1Side->id;
            RouteFare::create($routeFare->toArray());
        }

        $fare2Side = FareTable::create([
            'fare'=>$request->fare,
            'from_city_id'=>$request->to,
            'to_city_id'=>$request->from,
            'fare_class'=>$request->fare_class,
            'company_id'=>$company_id,
            'commission_flat'=>$request->commission_flat,
            'commission_percentage'=>$request->commission_percentage,
            'terminal_commission'=>$request->terminal_commission,
            'time_difference'=>$request->time_difference,
            'surcharge'=>$request->surcharge,
            'surcharge_start_date'=>$request->surcharge_start_date,
            'surcharge_end_date'=>$request->surcharge_end_date,
            'advance_availability'=>$request->advance_availability,
            'added_by'=>auth()->user()->id,
        ]);

        $routeFares2Side = RouteFare::where('city_from_id',$fare2Side->from_city_id)
        ->where('city_to_id',$fare2Side->to_city_id)
        ->get();
        foreach ($routeFares2Side as $i => $routeFare) {
            RouteFare::create([
                ...$routeFare,
                'fare_id'=>$fare2Side->id
            ]);
        }

        return $this->getFarePrices( $request->fare_class );

    }

    public function record( Request $request ){

        $request->validate([
            'fare_class'=>'required',
        ]);

        return $this->getFarePrices( $request->fare_class );
    }

    public function getFarePrices( $fare_class ){


        $cities = City::with(['city_to'=>function($q){
            $q->orderBy('name')->where('company_id',$this->company_id);;
        }])
        ->where('company_id',$this->company_id)
        ->orderBy('name')->get();

        $subRoutes = $cities->map(function ($city_from) use ($fare_class){

            // Storing Destination Cities into new Array Index
            $city_from['destinationCities'] = $city_from->city_to;

            // Fetching and storing the fare of the Departure and the Destination city Fare.
            foreach ($city_from['destinationCities'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['destinationCities'][$j]['fare'] = FareTable::where('from_city_id',$routeCities->departure_city_id)
                ->where('to_city_id',$routeCities->destination_city_id)
                ->where('fare_class',$fare_class)
                ->value('fare');
            }
            //
            unset($city_from['city_to']);
            return $city_from;
        });

        return $subRoutes;
    }

    public function getFareClass()
    {
        return FareClass::where('is_active',1)->orderBy('id')->select('id','name')->get(['name', 'id']);
    }


}
