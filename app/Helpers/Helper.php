<?php

use App\Models\FareTable;
use App\Models\Route\RouteFare;

if (!function_exists('storeFare')) {
    function storeFare($request, $company_id)
    {
        $fare1Side = FareTable::create([
            'fare' => $request->fare,
            'from_city_id' => $request->from,
            'to_city_id' => $request->to,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => auth()->user()->id,
        ]);
        /*Creating Route Fares those fare added after creating the route of one side*/
        $routeFares1Side = RouteFare::where('departure_city_id', $request->from_city_id)
            ->where('destination_city_id', $request->to_city_id)
            ->get();
        foreach ($routeFares1Side as $i => $routeFare) {
            $routeFare->fare_id = $fare1Side->id;
            RouteFare::create($routeFare->toArray());
        }

        $fare2Side = FareTable::create([
            'fare' => $request->fare,
            'from_city_id' => $request->to,
            'to_city_id' => $request->from,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => auth()->user()->id,
        ]);

        $routeFares2Side = RouteFare::where('departure_city_id', $fare2Side->from_city_id)
            ->where('destination_city_id', $fare2Side->to_city_id)
            ->get();
        foreach ($routeFares2Side as $i => $routeFare) {
            RouteFare::create([
                ...$routeFare,
                'fare_id' => $fare2Side->id
            ]);
        }
    }
}


if (!function_exists('format_phone')) {
    function format_phone(string $phone_no) {
        return preg_replace(
            "/.*(\d{4})[^\d]{0,7}(\d{7})/",
            '$1-$2',
            $phone_no
        );
    }
}

if (!function_exists('format_cnic')) {
    function format_cnic(string $phone_no) {
        return preg_replace(
            "/.*(\d{5})[^\d]{0,7}(\d{7})[^\d]{0,7}(\d{1})/",
            '$1-$2-$3',
            $phone_no
        );
    }
}

if (!function_exists('format_uan')) {
    function format_uan(string $phone_no) {
        return preg_replace(
            "/.*(\d{2})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{3})/",
            '$1-$2-$3-$4',
            $phone_no
        );
    }
}
if (!function_exists('updateFare')) {
    function updateFare($request, $company_id)
    {
        $fare1Side = FareTable::where('id', $request->id)->update([
            'fare' => $request->fare,
            'from_city_id' => $request->from,
            'to_city_id' => $request->to,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => auth()->user()->id,
        ]);
         FareTable::where('from_city_id', $request->to)->where('to_city_id', $request->from)->where('fare_class', $request->fare_class)->update([
            'fare' => $request->fare,
            'from_city_id' => $request->to,
            'to_city_id' => $request->from,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => auth()->user()->id,
        ]);
//        $routeFares1Side = RouteFare::where('departure_city_id', $request->from_city_id)
//            ->where('destination_city_id', $request->to_city_id)
//            ->get();
//        foreach ($routeFares1Side as $i => $routeFare) {
//            $routeFare->fare_id = $fare1Side->id;
//            RouteFare::create($routeFare->toArray());
//        }
//
//        $routeFares2Side = RouteFare::where('departure_city_id', $request->to_city_id)
//            ->where('destination_city_id', $request->to_city_id)
//            ->get();
//        foreach ($routeFares2Side as $i => $routeFare) {
//            RouteFare::create([
//                ...$routeFare,
//                'fare_id' => $request->id
//            ]);
//        }
    }
}




