<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Terminal\TerminalVisibility;
use App\Models\ActivityLog;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Route\RouteFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $cities = City::with('city_to:id,name')->get();
        $subRoutes = $cities->map(function ($city_from, $i){
            $city_from['city_to_final'] = $city_from->city_to;
            foreach ($city_from['city_to_final'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['city_to_final'][$j]['fare'] = FareTable::where('from_city_id',$routeCities->departure_city_id)
                ->where('to_city_id',$routeCities->destination_city_id)
                ->value('fare');
            }
            unset($city_from['city_to']);
            return $city_from;
        });

        return $subRoutes;

    }
    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $request->validate([
                'routeStart' => 'required',
                'routeEnd' => 'required',
                'cities' => 'required',
            ], [
                'route.required' => 'Route Name is Required !!!!'
            ]);
            if (count($request->cities) < 2) {
                return response()->json(["errors" => ["Cities Error" => ["Please Select At leat 2 Cities !!!"]]], 422);
            }
            foreach ($request['cities'] as $index => $city) {
                $used_cities[] = $city;
                foreach ($request['cities'] as $innerIndex => $innerCity) {
                    if (in_array($innerCity, $used_cities)) {
                        continue;
                    } else {
                        $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();
                        $fareClasses = FareClass::where('company_id', Auth::user()->company_id)->count();

                        if ($fareClasses == 0 || $fare->count() < $fareClasses) {
                            return response()->json([
                                "errors" => [
                                    "Fare Error" => ["Please Fill the Fare Table Completely First ( For All Fare Classes ) !!!"]
                                ]
                            ], 422);
                        }
                    }
                }
            }
            $route = Route::create([
                'name' => $request['routeStart'] . '-' . $request['routeEnd'],
                'via' => $request['routeVia'],
                'company_id' => Auth::user()->company_id,
                'added_by' => auth()->user()->id
            ]);
            $used_cities = [];//key can't be same
            foreach ($request['cities'] as $index => $city) {
                $used_cities[] = $city;
                foreach ($request['cities'] as $innerIndex => $innerCity) {
                    if (in_array($innerCity, $used_cities)) {
                        continue;
                    } else {
                        $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();

                        if ($fare->count() > 0) {
                            foreach ($fare as $detail) {
                                RouteFare::create([
                                    'route_id' => $route->id,
                                    'fare_id' => $detail->id,
                                    'fare_class_id' => $detail->fare_class,
                                    'departure_city_id' => $used_cities[$index],
                                    'destination_city_id' => $innerCity,
                                    'company_id' => Auth::user()->company_id,
                                    'added_by' => auth()->user()->id
                                ]);
                            }
                        }

                        TerminalVisibility::create([
                            'route_id' => $route->id,
                            'departure_city_id' => $used_cities[$index],
                            'destination_city_id' => $innerCity,
                            'company_id' => Auth::user()->company_id,
                            'added_by' => auth()->user()->id
                        ]);

                    }
                }
            }
            
            if ($request['revereRoute'] == 1) {
                // Reverse Route
                $route = Route::create([
                    'name' => $request['routeEnd'] . '-' . $request['routeStart'],
                    'via' => $request['routeVia'],
                    'company_id' => Auth::user()->company_id,
                    'added_by' => auth()->user()->id
                ]);
                $used_cities = [];//key can't be same
                foreach (array_reverse($request['cities']) as $index => $city) {
                    $used_cities[] = $city;
                    foreach (array_reverse($request['cities']) as $innerIndex => $innerCity) {
                        if (in_array($innerCity, $used_cities)) {
                            continue;
                        } else {
                            $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();

                            if ($fare->count() > 0) {
                                foreach ($fare as $detail) {
                                    RouteFare::create([
                                        'route_id' => $route->id,
                                        'fare_id' => $detail->id,
                                        'fare_class_id' => $detail->fare_class,
                                        'departure_city_id' => $used_cities[$index],
                                        'destination_city_id' => $innerCity,
                                        'company_id' => Auth::user()->company_id,
                                        'added_by' => auth()->user()->id
                                    ]);
                                }
                            }

                            TerminalVisibility::create([
                                'route_id' => $route->id,
                                'departure_city_id' => $used_cities[$index],
                                'destination_city_id' => $innerCity,
                                'company_id' => Auth::user()->company_id,
                                'added_by' => auth()->user()->id
                            ]);
                        }
                    }
                }
            }
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | added route ($route->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return ['message' => 'success'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function edit(Request $request)
    {
        if(!checkPermissionButtons("edit-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $route = Route::find($request->id);

        $lastFare = $route->fares->last();
        $allCityRoute = $route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
        array_push($allCityRoute, $lastFare->destination_city_id);

        return [
            "route" => $route,
            "cityIds" => $allCityRoute,
        ];
    }
    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    'routeStartName' => 'required',
                    'routeEndName' => 'required',
                ]);
                Route::where([
                    'company_id' => Auth::user()->company_id,
                    'id' => $request->id,
                ])->update([
                    'name' => $request['routeStartName'] . '-' . $request['routeEndName'],
                    'via' => $request['routeVia'],
                    'online_seat_choices' => $request['online_seat_choices'],
                ]);
                // now we will delete all route detail and will insert new one
                RouteFare::where("route_id",$request->id)->delete();
                
                $used_cities = [];//key can't be same
                foreach ($request->cityIds as $index => $city) {
                    $used_cities[] = $city;
                    foreach ($request->cityIds as $innerIndex => $innerCity) {
                        if (in_array($innerCity, $used_cities)) {
                            continue;
                        } else {
                            $fare = FareTable::where('from_city_id', $used_cities[$index])->where('to_city_id', $innerCity)->get();

                            if ($fare->count() > 0) {
                                foreach ($fare as $detail) {
                                    RouteFare::create([
                                        'route_id' => $request->id,
                                        'fare_id' => $detail->id,
                                        'fare_class_id' => $detail->fare_class,
                                        'departure_city_id' => $used_cities[$index],
                                        'destination_city_id' => $innerCity,
                                        'company_id' => Auth::user()->company_id,
                                        'added_by' => auth()->user()->id
                                    ]);
                                }
                            }
                            
                            
                            $TerminalVisibility = TerminalVisibility::where(["route_id"=>$request->id,"departure_city_id"=> $used_cities[$index],"destination_city_id"=>$innerCity,"company_id" => Auth::user()->company_id])->first();
                            TerminalVisibility::create([
                                'route_id' => $request->id,
                                'departure_city_id' => $used_cities[$index],
                                'destination_city_id' => $innerCity,
                                'online_visibilty' => isset($TerminalVisibility) ? $TerminalVisibility->online_visibilty : 0,
                                'company_id' => Auth::user()->company_id,
                                'added_by' => auth()->user()->id
                            ]);
                            if(isset($TerminalVisibility))
                            {
                                $TerminalVisibility->delete();
                            }
                        }
                    }
                }

                // and after addition route detail we will update schedule detail also so that it will implent all schedules
                $schedules = Schedule::where(["company_id"=>Auth::user()->company_id,"route_id"=>$request->id])->get();
                
    
                foreach($schedules as $schedule)
                {
                    // getting schedule detail from today or upcoming schedule to get schedule start date
                    $start_date = ScheduleDetail::where(["company_id"=>Auth::user()->company_id,"schedule_id"=>$schedule->id])->where("schedule_date", '>=' , date("Y-m-d"))->orderBy("id","ASC")->first();
                    // getting schedule detail from today or upcoming schedule to get schedule end date
                    $end_date = ScheduleDetail::where(["company_id"=>Auth::user()->company_id,"schedule_id"=>$schedule->id])->where("schedule_date", '>=' , date("Y-m-d"))->orderBy("id",'DESC')->first();
                    if($start_date && $end_date)
                    {
                        // getting route detail like fsd -> mltn -> kch
                        $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
                        // how many days schedule exist
                        $days = $this->getDays($start_date->schedule_date, $end_date->schedule_date);
      
                        // to run loop equal to schedule existing days
                        for ($i = 0; $i <= $days; $i++) {
                            $date_wise_departure = ScheduleDetail::where(["company_id"=>Auth::user()->company_id,"schedule_id"=>$schedule->id,"schedule_date"=>date("Y-m-d",strtotime(date("$start_date->schedule_date"))+($i * 86400))])->orderBy("id","ASC")->first();
                            ScheduleDetail::where("schedule_id",$schedule->id)->where("schedule_date", date("Y-m-d",strtotime(date("$start_date->schedule_date"))+($i * 86400)))->delete();
                            $lastDepId = $routeDetails[0]->departure_city_id;
                            $totalTime = strtotime(date("$start_date->schedule_date $date_wise_departure->departure_time")) + ($i * 86400);
                            $scheduleStartDate = date("Y-m-d", $totalTime);
                            
                            
                            foreach ($routeDetails as $detail) {
                                if ($lastDepId == $detail->departure_city_id) {
                                    $departureTime = date("Y-m-d H:i", $totalTime);
                                } else {
                                    // getting time difference between two cities in a route
                                    $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference??"00:00";
                                    $timeDiff = explode(':', $fareTableTime);
                                    $totalTime = $totalTime + (($timeDiff[0] * 3600) + ($timeDiff[1] * 60));
                                    $departureTime = date("Y-m-d H:i", $totalTime);
                                    $lastDepId = $detail->departure_city_id;
                                }
                              
                                ScheduleDetail::create([
                                    'company_id' => Auth::user()->company_id,
                                    'added_by' => Auth::user()->id,
                                    'schedule_id' => $schedule->id,
                                    'departure_id' => $detail->departure_city_id,
                                    'bus_class_id' => $schedule->bus_class_id,
                                    'destination_id' => $detail->destination_city_id,
                                    'departure_time' => date('H:i', strtotime($departureTime)),
                                    'departure_date' => date('Y-m-d', strtotime($departureTime)),
                                    'schedule_date' => $scheduleStartDate, // schedule departure date
                                ]);
                            }
                        }
                    }
                }
             
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated route (".$request['routeStartName'] . '-' . $request['routeEndName'].")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return ['message' => 'success'];
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    public function hideRoute(Request $request)
    {
        if(!checkPermissionButtons("delete-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $route = Route::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | deleted route ($route->name)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $route->update([
            "hide" => 1
        ]);
    }
    public function routeVisibilities(Request $request)
    {
        if(!checkPermissionButtons("details-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return [
            'visibilities' => TerminalVisibility::where(["route_id"=>$request->id,"company_id"=>Auth::user()->company_id])->with("departure:id,name","destination:id,name")->get(),
        ];
    }
    public function visibilityUpdate(Request $request)
    {
        if(!checkPermissionButtons("details-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | update visibility",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                foreach($request->subroutes as $single)
                {
                    TerminalVisibility::where("id",$single['subroute_id'])->update([
                        "online_visibilty" => $single['visibility'],
                        "booking_minutes" => $single['booking_minutes']==null ? null : abs($single['booking_minutes'])
                    ]);
                }
                DB::commit();
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    public function list()
    {
        if(!checkForSubmenu("routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return [
            'cities' => City::orderBy('id')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->select('name', 'id')->get(),
            'routes' => Route::with('addedBy')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get()
        ];
    }
    public function details(Request $request){
        if(!checkPermissionButtons("details-routes"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $routeFareCities = RouteFare::where('route_id', $request->id)->where('company_id', Auth::user()->company_id)->with('city_to:id,name', 'city_from:id,name', 'fare_details:id,fare,fare_class,time_difference', 'fare_details.class:id,name')->get()->groupBy(['departure_city_id', 'destination_city_id']);
        $data = [];
        foreach ($routeFareCities as $cities) {
            foreach ($cities as $city) {
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['departure_city'] = $city[0]->city_from->name;
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['destination_city'] = $city[0]->city_to->name;
                foreach ($city as $fare) {
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name] = $fare->fare_details->class->name . '---';
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name . '_fare'] = $fare->fare_details->time_difference." | ".$fare->fare_details->fare;
                }
            }
        }
        return [
            'data' => $data,
            'th' => FareClass::where('company_id', Auth::user()->company_id)->orderBY('name', 'ASC')->get(),
        ];
    }
    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }
}
