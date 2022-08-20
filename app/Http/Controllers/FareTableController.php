<?php

namespace App\Http\Controllers;

use App\Models\FareTable;
use Illuminate\Http\Request;

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
    public function index(){

    }
}
