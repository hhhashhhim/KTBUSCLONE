<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Route\SubRoute;
use App\Models\CityToCity;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubRoutecontroller extends Controller
{
    public function index(Request $request)
    {
        if(!checkForSubmenu("sub-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $cities = City::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->orderBy('id')->get();
        $subRoutes = SubRoute::with("from_city_data:id,name","to_city_data:id,name","added_by_data:id,name")->where(['company_id'=> Auth::user()->company_id])->get();

        return [
            "cities" => $cities,
            "subRoutes" => $subRoutes,
        ];
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-sub-route"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'fromCity' => ['required'],
                    'toCity' => ['required'],
                    'message' => ['required'],
                ];

                $customMessages = [
                    'fromCity.required' => 'From City Field is Required!',
                    'toCity.required' => 'To Name is Field is Required',
                    'message.required' => 'Message Field is Required',
                ];
                $this->validate($request, $rules, $customMessages);

                SubRoute::where(["from_city"=>$request->fromCity,"to_city"=>$request->toCity])->delete();
                $subRoute = SubRoute::create([
                    'from_city' => $request->fromCity,
                    'to_city' => $request->toCity,
                    'cancel_message' => $request->message,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                
                
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added sub route",
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
        if(!checkPermissionButtons("edit-sub-route"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'from_city' => ['required'],
                    'to_city' => ['required'],
                    'cancel_message' => ['required'],
                ];

                $customMessages = [
                    'from_city.required' => 'From City Field is Required!',
                    'to_city.required' => 'To Name is Field is Required',
                    'cancel_message.required' => 'Message Field is Required',
                ];
                $this->validate($request, $rules, $customMessages);

                $subRoute = SubRoute::where(["from_city"=>$request->from_city,"to_city"=>$request->to_city])->first();
                if(isset($subRoute) && $subRoute->id != $request->id)
                {
                    SubRoute::where(["from_city"=>$request->from_city,"to_city"=>$request->to_city])->delete();
                }
                $subRoute = SubRoute::where(["id"=>$request->id])->update([
                    'from_city' => $request->from_city,
                    'to_city' => $request->to_city,
                    'cancel_message' => $request->cancel_message,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated sub route",
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
