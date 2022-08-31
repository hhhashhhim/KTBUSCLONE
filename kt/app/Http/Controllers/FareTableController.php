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
        
        $cities = City::leftjoin('cities as cities_to', 'cities.id', '!=', 'cities_to.id')
            ->select('cities.id as from_id', 'cities.name as from_name', 'cities_to.id as to_id', 'cities_to.name as to_name');
        $cities2 = City::leftjoin('cities as cities_to', 'cities.id', 'cities_to.id')
            ->select('cities.id as from_id', 'cities.name as from_name', 'cities_to.id as to_id', 'cities_to.name as to_name');
        $cities->union($cities2);

        
        
        $farePrices = FareTable::rightjoin(
            DB::raw('(' . $cities->toSql() . ') as cities'),
            function ($join) use ($cities) {
                $join->on('fare_tables.from_city_id', '=', 'cities.from_id')
                    ->on('fare_tables.to_city_id', '=', 'cities.to_id');
        })
        ->orWhere('company_id',1)->orWhere('company_id',null)
        ->orWhere('fare_class',$fare_class)->orWhere('fare_class',null)
        ->select('cities.*', 'fare_tables.fare')->orderBy('from_name')->orderBy('to_name')->get()->groupBy('from_name');

        return $farePrices;
    }
}
