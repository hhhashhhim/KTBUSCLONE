<?php

namespace App\Http\Controllers;

use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public $company_id;

    public function __construct(){
        $this->middleware(function ($request, $next){
            $this->company_id = auth()->user()->company_id;
            return $next( $request );
        });
    }
    public function index(){
        return Company::orderBy('id','desc')->get();
    }
    public function store( Request $request ){

        $request->validate([
            'name'=>'required | unique:companies',
            'contact'=>'required | unique:companies',
            'userName'=>'required',
            'email'=>'required | unique:users',
            'password'=>'required',
        ]);
        $company = Company::create([
            'name'=>$request->name,
            'contact'=>$request->contact,
            'location'=>$request->location,
            'modules'=>$request->modules,
            // 'logo'=>$request->logo,
            'added_by'=>auth()->user()->id,
        ]);
        
        $role = Role::create([
            'name' => 'admin',
            'company_id' => $company->id,
            'permissions' => $request->modules,
        ]);

        $user = User::create([
            'name'=>$request->userName,
            'email'=>$request->email,
            'contact'=>$request->contact,
            'password'=>Hash::make($request->password),
            'role_id'=>$role->id,
            'company_id'=>$company->id,
        ]);
        
        return $company;

    }
    public function update( Request $request ){

        $request->validate([
            'name'=>'required',
            'contact'=>'required',
        ]);
        Company::find( $request->id )->update([
            'name'=>$request->name,
            'contact'=>$request->contact,
            'location'=>$request->location,
            'modules'=>$request->modules,
            'added_by'=>auth()->user()->id,
        ]);
        Role::where('company_id',$request->id)->where('name','admin')->update([
            'permissions' => $request->modules,
        ]);
        return response()->json([
            'message'=>"Updated Successfully",
        ],200);
        
    }
    public function delete( Request $request ){
        return Company::find($request->id)->delete();
    }
    public function company_roles( Request $request ){
        return Role::where('company_id',$this->company_id)->get();
    }
    public function company( Request $request ){
        return Company::find($request->id);
    }
    
}
