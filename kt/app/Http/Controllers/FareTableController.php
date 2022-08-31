<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FareTableController extends Controller
{
    public function store( Request $request ){

        $request->validate([
            'fare'=>'required',
            'fare_class'=>'required',
        ]);
        $company_id = auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id;
        FareTable::create([
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
        
        return $this->getFarePrices( $company_id,$request->fare_class );
        
    }
    
    public function record( Request $request ){

        $request->validate([
            'fare_class'=>'required',
        ]);

        return $this->getFarePrices( 1,$request->fare_class );
    }

    public function getFarePrices( $company_id,$fare_class ){
        
        
        $cities = City::with('city_to:id,name')->get();
        $subRoutes = $cities->map(function ($city_from, $i){

            // Storing Destination Cities into new Array Index
            $city_from['destinationCities'] = $city_from->city_to;
            
            // Fetching and storing the fare of the Departure and the Destination city Fare.
            foreach ($city_from['destinationCities'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['destinationCities'][$j]['fare'] = FareTable::where('from_city_id',$routeCities->departure_city_id)
                ->where('to_city_id',$routeCities->destination_city_id)
                ->value('fare');
            }
            // 
            unset($city_from['city_to']);
            return $city_from;
        });
        
        return $subRoutes;
    }
}
