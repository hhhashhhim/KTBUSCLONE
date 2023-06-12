<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin\Role;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ValidationResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\EmptyResource;
use App\Models\Schedule\Schedule;
use App\Models\City;
use App\Models\Schedule\ScheduleDetail;
use App\Http\Resources\BreakResource;

class BookingApiController extends Controller
{
    public function availableSchedules(Request $request)
    {
        try {
                $validator = Validator::make($request->all(), [
                    'departure_city' => 'required|string',
                    'destination_city' => 'required|string',
                    'date' => 'required',
                ]);
            
                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }
              
                // Data
                $departure_id = City::where(["name"=>$request->departure_city,"company_id"=>1])->first()->id??0;
                $destination_id = City::where(["name"=>$request->destination_city,"company_id"=>1])->first()->id??0;

                $data = ScheduleDetail::with('schedule:id,name')->where(['departure_id' => $departure_id, 'destination_id' => $destination_id, 'departure_date' => $request->date,'company_id' => 1])->get(["id","schedule_id","departure_id","destination_id","departure_time","departure_date","schedule_id","schedule_date"]);
                foreach ($data as $single) {
                    
                    $exactDate = date("Y-m-d h:i A", strtotime($single->departure_date . ' ' . $single->departure_time));
                    $single->departure_date = date("m/d/Y", strtotime($exactDate));
                    $single->departure_time = date("h:i A", strtotime($exactDate));
                
                }
                
                // data found | not found
                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }
                
            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
        
    }
    
}
