<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(){

        return view('super-admin.index');
        
    }
    public function add_company(){

        return view('super-admin.add-company');
        
    }
}
