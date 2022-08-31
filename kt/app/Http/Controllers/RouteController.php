<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

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
}
