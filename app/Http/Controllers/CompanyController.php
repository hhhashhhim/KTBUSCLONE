<?php

namespace App\Http\Controllers;

use App\Models\admin\Role;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(){
        return Company::orderBy('id','desc')->get();
    }
    public function store( Request $request ){
        $request->validate([
            'name'=>'required',
            'contact'=>'required',
        ]);
        return Company::create([
            'name'=>$request->name,
            'contact'=>$request->contact,
            'location'=>$request->location,
            'modules'=>$request->modules,
            // 'logo'=>$request->logo,
            'added_by'=>auth()->user()->id,
        ]);
        
    }
    public function update( Request $request ){
        $request->validate([
            'name'=>'required',
            'contact'=>'required',
        ]);
        return Company::find( $request->id )->update([
            'name'=>$request->name,
            'contact'=>$request->contact,
            'location'=>$request->location,
            'modules'=>$request->modules,
            'added_by'=>auth()->user()->id,
        ]);
        
    }
    public function company_roles( Request $request ){
        return Role::where('company_id',$request->id)->get();
    }
}
