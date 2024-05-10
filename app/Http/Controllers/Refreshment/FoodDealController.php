<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Refreshment\HotelFoodDeal;
use App\Models\Refreshment\HotelFoodDealDetail;
use App\Models\Refreshment\HotelFood;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FoodDealController extends Controller
{
    public function index(Request $request)
    {
        if(!checkPermissionButtons("deal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Hotel::
            with('user:id,name,email','deals:id,name,price,description,hotel_id',
            'deals.dealDetails:id,food_id,food_deal_id,quantity','deals.dealDetails.food:id,name,unit')
            ->where(["id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("deal-add-deal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required|unique:hotel_food_deals,name,Null,id,hotel_id,'.$request->hotelId,
                    "price" => 'required',
                    "foods" => 'required',
                    "qtys" => 'required',
                ]);

                $deal = HotelFoodDeal::create([
                    "name" => $request->name,
                    "price" => $request->price,
                    "description" => $request->description,
                    "hotel_id" => $request->hotelId,
                    "company_id" => Auth::user()->company_id,
                    "added_by" => Auth::user()->id,
                ]);

                foreach($request->foods as $key => $value)
                {
                    $checkExist = HotelFoodDealDetail::where(["food_deal_id"=>$deal->id,"food_id"=>$request->foods[$key],"hotel_id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
                    if(!$checkExist)
                    {
                        HotelFoodDealDetail::create([
                            "food_id" => $request->foods[$key],
                            "food_deal_id" => $deal->id,
                            "quantity" => $request->qtys[$key],
                            "hotel_id" => $request->hotelId,
                            "company_id" => Auth::user()->company_id,
                            "added_by" => Auth::user()->id,
                        ]);
                    }
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added food deal ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();   
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("deal-edit-deal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required|unique:hotel_food_deals,name,'.$request->dealId.',id,hotel_id,'.$request->hotelId,
                    "price" => 'required',
                    "foods" => 'required',
                    "qtys" => 'required',
                ]);

                HotelFoodDeal::where("id",$request->dealId)->update([
                    "name" => $request->name,
                    "price" => $request->price,
                    "description" => $request->description,
                ]);

                HotelFoodDealDetail::where(["food_deal_id"=>$request->dealId,"hotel_id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->delete();
                foreach($request->foods as $key => $value)
                {
                    $checkExist = HotelFoodDealDetail::where(["food_deal_id"=>$request->dealId,"food_id"=>$request->foods[$key],"hotel_id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
                    if(!$checkExist)
                    {
                        HotelFoodDealDetail::create([
                            "food_id" => $request->foods[$key],
                            "food_deal_id" => $request->dealId,
                            "quantity" => $request->qtys[$key],
                            "hotel_id" => $request->hotelId,
                            "company_id" => Auth::user()->company_id,
                            "added_by" => Auth::user()->id,
                        ]);
                    }
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated food deal ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }



}
