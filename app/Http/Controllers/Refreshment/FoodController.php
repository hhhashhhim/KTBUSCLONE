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
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FoodController extends Controller
{
    public function index(Request $request)
    {
        if(!checkPermissionButtons("food"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Hotel::with('user:id,name,email','foods:id,name,price,unit,description,hotel_id')
                ->where(["id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("food-add-food"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required|unique:hotel_foods,name,Null,id,hotel_id,'.$request->hotelId,
                    "price" => 'required',
                    "unit" => 'required',
                ]);

                $hotelFood = HotelFood::create([
                    "name" => $request->name,
                    "price" => $request->price,
                    "unit" => $request->unit,
                    "description" => $request->description,
                    "hotel_id" => $request->hotelId,
                    "company_id" => Auth::user()->company_id,
                    "added_by" => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added food ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $hotelFood;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("food-edit-food"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required|unique:hotel_foods,name,'.$request->foodId.',id,hotel_id,'.$request->hotelId,
                    "price" => 'required',
                    "unit" => 'required',
                ]);

                $hotelFood = HotelFood::where("id",$request->foodId)->update([
                    "name" => $request->name,
                    "price" => $request->price,
                    "unit" => $request->unit,
                    "description" => $request->description,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated food ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $hotelFood;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }



}
