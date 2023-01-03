<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
class HotelController extends Controller
{

//    public $company_id;
//
//    public function __construct()
//    {
//        $this->middleware(function ($request, $next) {
//            Auth::user()->company_id = Auth::user()->company_id;
//            return $next($request);
//        });
//    }

    public function index()
    {
        return Hotel::with("user")->where("company_id",Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            "name" => 'required',
            // unique:table,column,except,idColumn,anotherColumn,anotherColumnValue
            "hotelName" => 'required|unique:hotels,name,Null,id,company_id,'.Auth::user()->company_id,
            "email" => 'required|email|unique:users',
            "password" => 'required',
            "contact" => 'required',
            "commission" => 'required',
            "location" => 'required',
        ]);

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "contact" => str_replace('-', '', $request->contact),
            "role_id" => 0,
            'company_id' => Auth::user()->company_id,
        ]);

        return Hotel::create([
            "user_id" => $user->id,
            "name" => $request->hotelName,
            "contact" => str_replace('-', '', $request->contact),
            "logo" => $request->logo ? $this->image($request->logo) : null,
            "location" => $request->location,
            "commission" => $request->commission,
            "balance" => $request->balance??0,
            "company_id" => Auth::user()->company_id,
            "added_by" => Auth::user()->id,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            "name" => 'required',
            // unique:table,column,except,idColumn,anotherColumn,anotherColumnValue
            "hotelName" => 'required|unique:hotels,name,'.$request->hotelId.',id,company_id,'.Auth::user()->company_id,
            "email" => 'required|email|unique:users,email,'.$request->userId,
            "contact" => 'required',
            "commission" => 'required',
            "location" => 'required',
        ]);

        User::where("id",$request->userId)->update([
            "name" => $request->name,
            "email" => $request->email,
            "contact" => str_replace('-', '', $request->contact),
        ]);

        if($request->password)
        {
            User::where("id",$request->userId)->update([
                "password" => Hash::make($request->password),
            ]);
        }

        Hotel::where("id",$request->hotelId)->update([
            "name" => $request->hotelName,
            "contact" => str_replace('-', '', $request->contact),
            "location" => $request->location,
            "commission" => $request->commission,
            "balance" => $request->balance??0,
            "company_id" => Auth::user()->company_id,
            "added_by" => Auth::user()->id,
        ]);

        if($request->logo)
        {
            Hotel::where("id",$request->hotelId)->update([
                "logo" => $request->logo ? $this->image($request->logo) : null,
            ]);
        }
    }

    // Image Upload
    public function image($image){

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename        = pathinfo($filenameWithExt);
        //get just extension
        $extension       = $image->extension();
        $nameToStore     = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path            = $image->move(public_path('uploads/refreshment/hotel'), $nameToStore);
        return $nameToStore;
    }

}
