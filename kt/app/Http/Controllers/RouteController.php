<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(){
        
        $cities = City::with('city_to')->get();

        $subRoutes = $cities->map(function ($city_from, $i){
            $city_from['city_to_final'] = $city_from->city_to->whereIn('id',[8,3,1]);
            unset($city_from['city_to']);
            return $city_from;
        });
        
        return $subRoutes;

    }
}
