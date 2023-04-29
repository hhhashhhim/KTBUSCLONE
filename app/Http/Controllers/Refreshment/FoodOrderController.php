<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Refreshment\HotelFoodDeal;
use App\Models\Refreshment\HotelFoodDealDetail;
use App\Models\Refreshment\HotelFood;
use App\Models\Refreshment\HotelFoodOrder;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMember;
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

    public function index(Request $request)
    {
        return Hotel::
            with('user:id,name,email','deals:id,name,price,description,hotel_id',
            'deals.dealDetails:id,food_id,food_deal_id,quantity','deals.dealDetails.food:id,name,unit')
            ->where(["id"=>$request->hotelId,"company_id"=>Auth::user()->company_id])->first();
    }
    // 
    
    public function orderFoodIndex(Request $request)
    {
        $checkHotelLogin = Hotel::where("user_id",Auth::user()->id)->where("company_id",Auth::user()->company_id)->first();
        if($checkHotelLogin)
        {
            // for hotel
            $TicketClosingId = [];
            $foodOrders = HotelFoodOrder::
            where(['hotel_id'=>$checkHotelLogin->id,'company_id'=>Auth::user()->company_id])
            ->where("created_at",'>',now()->subDays(1))
            ->with("hotel:id,name","bus:id,bus_number")
            ->get();

            $foodOrders = $foodOrders->map(function($q){
                if( $q->item_type == 1){
                    $q->food_record = HotelFood::where("id",$q->item_id)->first(['id','name','unit']);
                }
                if( $q->item_type == 2){
                    $q->food_record = HotelFoodDeal::where("id",$q->item_id)->with("dealDetails:id,food_id,food_deal_id,quantity","dealDetails.food:id,name,unit")->first(['id','name']);
                }
                return $q;
            })->groupBy("seat_no");
        }
        else
        {
            // for host
            $TicketClosingId = TicketClosingMember::
                where(["user_id"=>Auth::user()->id,"type"=>2,'company_id'=>Auth::user()->company_id])
                ->latest()->first()->ticket_closing_id??null;
            
            $foodOrders = HotelFoodOrder::
            where(['ticket_closing_id'=>$TicketClosingId,'company_id'=>Auth::user()->company_id])
            ->with("hotel:id,name","bus:id,bus_number")
            ->get();

            $foodOrders = $foodOrders->map(function($q){
                if( $q->item_type == 1){
                    $q->food_record = HotelFood::where("id",$q->item_id)->first(['id','name','unit']);
                }
                if( $q->item_type == 2){
                    $q->food_record = HotelFoodDeal::where("id",$q->item_id)->with("dealDetails:id,food_id,food_deal_id,quantity","dealDetails.food:id,name,unit")->first(['id','name']);
                }
                return $q;
            })->groupBy("seat_no");
        }

        $data = [
            "mainData" => $foodOrders,
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading"]),
            "hotelDrop" => Hotel::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","name"]),
            "hostData" => TicketClosing::find($TicketClosingId),
        ];
        return $data;
    }

    public function hotelItems(Request $request)
    {
        $food = HotelFood::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])->get(["id","name","price"]);
        $food->map(function($q){
            $q->cid = $q->id.'-1'; // 1 to identify food
        });
        $foodDeal = HotelFoodDeal::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])->get(["id","name","price"]);
        $foodDeal->map(function($q){
            $q->name = $q->name.' (Deal)';
            $q->cid = $q->id.'-2'; // 2 to identify deal
        });
        
        return $data =  [...$food,...$foodDeal];
    }
    
    public function hotelAllItems(Request $request)
    {
        $foods = HotelFood::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])->get();
        $deals = HotelFoodDeal::where(['company_id'=>Auth::user()->company_id,"hotel_id"=>$request->id])
        ->with("dealDetails:id,food_id,food_deal_id,quantity","dealDetails.food:id,name,unit")
        ->get();
       
        $data = [
            "foods" => $foods,
            "deals" => $deals,
        ];
        return $data;
    }

    public function orderBook(Request $request)
    {
        $request->validate([
            "ticketClosingId" => 'required',
            "estimatedTime" => 'required',
            "hotelId" => 'required',
            "item" => 'required',
            "quantity" => 'required',
            "seat" => 'required',
        ]);

        $TicketClosing = TicketClosing::find($request->ticketClosingId);

        foreach($request->item as $key=>$value)
        {
            $checkItem = explode("-",$request->item[$key]);
            $food = $checkItem[1] == 1 ? HotelFood::find($checkItem[0]) : HotelFoodDeal::find($checkItem[0]);

            HotelFoodOrder::create([
                "hotel_id" => $request->hotelId,
                "item_id" => $checkItem[0],
                "item_type" => $checkItem[1],
                "quantity" => $request->quantity[$key],
                "price" => $food->price,
                "amount" => ($food->price * $request->quantity[$key]),
                "seat_no" => $request->seat[$key],
                "estimated_time" => $request->estimatedTime,
                "ticket_closing_id" => $TicketClosing->id,
                "bus_id" => $TicketClosing->bus_id,
                "schedule_id" => $TicketClosing->schedule_id,
                "schedule_date" => $TicketClosing->schedule_date,
                "status" => "pending",
                "company_id" => Auth::user()->company_id,
                "added_by" => Auth::user()->id,
            ]);
        }

    }

    public function orderReceive(Request $request)
    {
        HotelFoodOrder::where("id",$request->id)->update([
            "status" => "received"
        ]);
    }
    
    public function orderReady(Request $request)
    {
        HotelFoodOrder::where("id",$request->id)->update([
            "status" => "ready"
        ]);
    
    }
    public function orderDelivered(Request $request)
    {
        HotelFoodOrder::where("id",$request->id)->update([
            "status" => "delivered"
        ]);
    }


}
