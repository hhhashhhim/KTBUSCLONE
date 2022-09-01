<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Route\RouteTerminal;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Symfony\Component\VarDumper\Caster\RdKafkaCaster;

class CityController extends Controller
{
    public $company_id;

    public function __construct(){
        $this->middleware(function ($request, $next){
            $this->company_id = auth()->user()->company_id;
            return $next( $request );
        });
    }

    public function index(){
        
        return City::orderBy('name')->select('name','id')->get();
        
    }

    public function city_routes_list(){
        
        $data = [
            'cities' => City::orderBy('name')->select('name','id')->get(),
            'routes' => Route::get() 
        ];

        return $data;
        
    }

    public function cityTerminals( Request $request ){
        return Terminal::where('company_id', $this->company_id)->where('city_id',$request->id)->get();
    }

    public function cityRoutes( Request $request ){
        $route = Route::create([
            'name'       =>  $request['route'],
            'company_id' => $this->company_id,
            'added_by' => auth()->user()->id  
        ]);

        foreach($request['terminals'] as $terminal){
            RouteTerminal::create([
                'route_id'     =>  $route->id,
                'terminal_id'  => $terminal,
                'company_id'   => $this->company_id,
                'added_by'     => auth()->user()->id  
            ]);
        }

        foreach($request['cities'] as $index => $city){
            if( isset($request['cities'][$index + 1] ) ){

                $fare = FareTable::where('from_city_id', $city)->where('to_city_id', $request['cities'][$index + 1])->get();
                if($fare->count() > 0){
                    foreach( $fare as $detail){

                        RouteFare::create([
                            'route_id'   => $route->id,
                            'fare_id'    => $detail->id,
                            'city_from_id' => $city,
                            'city_to_id' => $request['cities'][$index + 1],
                            'company_id' => $this->company_id,
                            'added_by'   => auth()->user()->id  
                        ]);
                    }
                }
            }
        }

        return ['message' => 'success'];
    }

    public function city_routes_details( Request $request){
        return RouteFare::with('city_from:id,name', 'city_to:id,name','fare_details')
        ->where('route_id', $request->id)
        ->get()->groupBy('city_from_id', 'city_to_id');
    }
}
