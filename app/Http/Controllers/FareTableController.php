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
        FareTable::create([
            'fare'=>$request->fare,
            'from_city_id'=>$request->from,
            'to_city_id'=>$request->to,
            'fare_class'=>$request->fare_class,
            'company_id'=>auth()->user()->is_super_admin==0?auth()->user()->company_id:$request->company_id,
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
        
        return response()->json([
            'message'=>'Stored Successfully',
        ],200);
        
    }
    public function record( Request $request ){

        $request->validate([
            'company_id'=>'required',
            'fare_class'=>'required',
        ]);
       
        $cities = City::leftjoin('cities as cities_to', 'cities.id', '!=', 'cities_to.id')
            ->select('cities.id as from_id', 'cities.name as from_name', 'cities_to.id as to_id', 'cities_to.name as to_name')
            ->groupBy('from_id', 'from_name', 'to_id', 'to_name');
        $farePrices = FareTable::rightjoin(
            DB::raw('(' . $cities->toSql() . ') as cities'),
            function ($join) use ($cities) {
                $join->on('fare_tables.from_city_id', '=', 'cities.from_id')
                    ->on('fare_tables.to_city_id', '=', 'cities.to_id');
            }
        )->select('cities.*', 'fare_tables.fare')->get()->groupBy('from_id');

        return $farePrices;
    }
}
