<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    
    public function index(){
        
        return City::orderBy('name')->select('name','id')->get();
        
    }
}
