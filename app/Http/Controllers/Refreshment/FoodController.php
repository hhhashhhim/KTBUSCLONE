<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Refreshment\HotelFood;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
class FoodController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        return Hotel::with('user:id,name,email','foods:id,name,price,unit,description,hotel_id')
                ->where(["id"=>$request->hotelId,"company_id"=>$this->company_id])->first();
    }
    
    public function store(Request $request)
    {
        $request->validate([
            "name" => 'required|unique:hotel_foods,name,Null,id,hotel_id,'.$request->hotelId,
            "price" => 'required',
            "unit" => 'required',
        ]);

        return HotelFood::create([
            "name" => $request->name,
            "price" => $request->price,
            "unit" => $request->unit,
            "description" => $request->description,
            "hotel_id" => $request->hotelId,
            "company_id" => $this->company_id,
            "added_by" => Auth::user()->id,
        ]);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            "name" => 'required|unique:hotel_foods,name,'.$request->foodId.',id,hotel_id,'.$request->hotelId,
            "price" => 'required',
            "unit" => 'required',
        ]);

        return HotelFood::where("id",$request->foodId)->update([
            "name" => $request->name,
            "price" => $request->price,
            "unit" => $request->unit,
            "description" => $request->description,
        ]);
    }

    
    
}
