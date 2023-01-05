<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Refreshment\HotelFoodDeal;
use App\Models\Refreshment\HotelFoodDealDetail;
use App\Models\Refreshment\HotelFood;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
class FoodOrderController extends Controller
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

    // public function index(Request $request)
    // {
    //     return Hotel::
    //         with('user:id,name,email','deals:id,name,price,description,hotel_id',
    //         'deals.dealDetails:id,food_id,food_deal_id,quantity','deals.dealDetails.food:id,name,unit')
    //         ->where(["id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
    // }
    // 
    
    public function orderFoodIndex(Request $request)
    {
        $data = [
            // "mainData" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading","reading_date"]),
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading"]),
            "hotelDrop" => Hotel::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","name"]),
        ];
        return $data;
    }

    public function hotelItems(Request $request)
    {
        $food = HotelFood::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])->get(["id","name"]);
        $food->map(function($q){
            $q->cid = $q->id.'-1';
        });
        $foodDeal = HotelFoodDeal::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])->get(["id","name"]);
        $foodDeal->map(function($q){
            $q->name = $q->name.' (Deal)';
            $q->cid = $q->id.'-2';
        });
        
        return $data =  [...$food,...$foodDeal];
    }
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         "name" => 'required|unique:hotel_food_deals,name,Null,id,hotel_id,'.$request->hotelId,
    //         "price" => 'required',
    //         "foods" => 'required',
    //         "qtys" => 'required',
    //     ]);

    //     $deal = HotelFoodDeal::create([
    //         "name" => $request->name,
    //         "price" => $request->price,
    //         "description" => $request->description,
    //         "hotel_id" => $request->hotelId,
    //         "company_id" => Auth::user()->company_id,
    //         "added_by" => Auth::user()->id,
    //     ]);

    //     foreach($request->foods as $key => $value)
    //     {
    //         $checkExist = HotelFoodDealDetail::where(["food_deal_id"=>$deal->id,"food_id"=>$request->foods[$key],"hotel_id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
    //         if(!$checkExist)
    //         {
    //             HotelFoodDealDetail::create([
    //                 "food_id" => $request->foods[$key],
    //                 "food_deal_id" => $deal->id,
    //                 "quantity" => $request->qtys[$key],
    //                 "hotel_id" => $request->hotelId,
    //                 "company_id" => Auth::user()->company_id,
    //                 "added_by" => Auth::user()->id,
    //             ]);
    //         }
    //     }
// 
    // }




}
