<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Terminal\TerminalVisibility;
use App\Models\ActivityLog;
use App\Models\LimitedSeat;
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
                'online_seats' => $request['routeSeat']??0,
                'commission_route' => $request['commissioRoute'],
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
                    'online_seats' => $request['routeSeat']??0,
                    'commission_route' => $request['commissioRoute'],
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
    if (!checkPermissionButtons("edit-routes")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    try {
        

        DB::beginTransaction();

        $request->validate([
            'routeStartName' => 'required',
            'routeEndName' => 'required',
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;
        $userId = $user->id;
        $routeId = $request->id;
        $today = now()->format('Y-m-d');
        $cityIds = $request->cityIds ?? [];

        Route::where([
            'company_id' => $companyId,
            'id' => $routeId,
        ])->update([
            'name' => $request->routeStartName . '-' . $request->routeEndName,
            'via' => $request->routeVia,
            'online_seats' => $request->routeSeat ?? 0,
            'commission_route' => $request->commissionRoute,
            'online_seat_choices' => $request->online_seat_choices,
        ]);

        RouteFare::where('route_id', $routeId)->delete();

        $fareTables = FareTable::whereIn('from_city_id', $cityIds)
            ->whereIn('to_city_id', $cityIds)
            ->get();

        $fareTablesGrouped = $fareTables->groupBy(function ($item) {
            return $item->from_city_id . '_' . $item->to_city_id;
        });

        $timeDifferences = $fareTables->keyBy(function ($item) {
            return $item->from_city_id . '_' . $item->to_city_id;
        });

        $oldTerminalVisibilities = TerminalVisibility::where([
            'route_id' => $routeId,
            'company_id' => $companyId,
        ])->get()->keyBy(function ($item) {
            return $item->departure_city_id . '_' . $item->destination_city_id;
        });

        $routeFareData = [];
        $terminalVisibilityData = [];
        $usedCities = [];

        foreach ($cityIds as $index => $city) {
            $usedCities[] = $city;

            foreach ($cityIds as $innerCity) {
                if (in_array($innerCity, $usedCities)) {
                    continue;
                }

                $fromCity = $usedCities[$index];
                $fareKey = $fromCity . '_' . $innerCity;

                if (isset($fareTablesGrouped[$fareKey])) {
                    foreach ($fareTablesGrouped[$fareKey] as $detail) {
                        $routeFareData[] = [
                            'route_id' => $routeId,
                            'fare_id' => $detail->id,
                            'fare_class_id' => $detail->fare_class,
                            'departure_city_id' => $fromCity,
                            'destination_city_id' => $innerCity,
                            'company_id' => $companyId,
                            'added_by' => $userId,
                        ];
                    }
                }

                $terminalKey = $fromCity . '_' . $innerCity;
                $oldVisibility = $oldTerminalVisibilities[$terminalKey] ?? null;

                $terminalVisibilityData[] = [
                    'route_id' => $routeId,
                    'departure_city_id' => $fromCity,
                    'destination_city_id' => $innerCity,
                    'online_visibilty' => $oldVisibility ? $oldVisibility->online_visibilty : 0,
                    'company_id' => $companyId,
                    'added_by' => $userId,
                ];
            }
        }

        if (!empty($routeFareData)) {
            foreach (array_chunk($routeFareData, 1000) as $chunk) {
                RouteFare::insert($chunk);
            }
        }

        TerminalVisibility::where([
            'route_id' => $routeId,
            'company_id' => $companyId,
        ])->delete();

        if (!empty($terminalVisibilityData)) {
            foreach (array_chunk($terminalVisibilityData, 1000) as $chunk) {
                TerminalVisibility::insert($chunk);
            }
        }

        // original logic jaisa: first fare_class group
        $routeDetailsGrouped = collect($routeFareData)->groupBy('fare_class_id');
        $routeDetails = $routeDetailsGrouped->first();

        if ($routeDetails && count($routeDetails) > 0) {
            $routeDetails = collect($routeDetails)->sortBy('departure_city_id')->values();

            $schedules = Schedule::where([
                'company_id' => $companyId,
                'route_id' => $routeId,
            ])->get();

            foreach ($schedules as $schedule) {
                // Sirf har date ki first row nikalo, full details nahi
                $firstRows = DB::table('schedule_details as sd')
                    ->join(
                        DB::raw('(SELECT MIN(id) as id FROM schedule_details WHERE company_id = ' . (int)$companyId . ' AND schedule_id = ' . (int)$schedule->id . ' AND schedule_date >= "' . $today . '" GROUP BY schedule_date) as x'),
                        'sd.id',
                        '=',
                        'x.id'
                    )
                    ->select('sd.schedule_date', 'sd.departure_time')
                    ->orderBy('sd.schedule_date', 'ASC')
                    ->get();

                if ($firstRows->isEmpty()) {
                    continue;
                }

                $startDate = $firstRows->first()->schedule_date;
                $endDate = $firstRows->last()->schedule_date;

                if (!$startDate || !$endDate) {
                    continue;
                }

                $firstRowsByDate = $firstRows->keyBy('schedule_date');
                $days = $this->getDays($startDate, $endDate);

                // Purane saare future records ek dafa delete
                ScheduleDetail::where('company_id', $companyId)
                    ->where('schedule_id', $schedule->id)
                    ->where('schedule_date', '>=', $today)
                    ->delete();

                $insertScheduleDetails = [];

                for ($i = 0; $i <= $days; $i++) {
                    $currentDate = date('Y-m-d', strtotime($startDate . " +{$i} days"));

                    if (!isset($firstRowsByDate[$currentDate])) {
                        continue;
                    }

                    $dateWiseDeparture = $firstRowsByDate[$currentDate];
                    $lastDepId = $routeDetails->first()['departure_city_id'];

                    $totalTime = strtotime($startDate . ' ' . $dateWiseDeparture->departure_time) + ($i * 86400);
                    $scheduleStartDate = date('Y-m-d', $totalTime);

                    foreach ($routeDetails as $detail) {
                        $departureCityId = $detail['departure_city_id'];
                        $destinationCityId = $detail['destination_city_id'];

                        if ($lastDepId == $departureCityId) {
                            $departureTime = date('Y-m-d H:i', $totalTime);
                        } else {
                            $timeKey = $lastDepId . '_' . $departureCityId;
                            $fareTime = isset($timeDifferences[$timeKey]) ? $timeDifferences[$timeKey]->time_difference : "00:00";

                            $timeDiff = explode(':', $fareTime);
                            $hours = (int)($timeDiff[0] ?? 0);
                            $minutes = (int)($timeDiff[1] ?? 0);

                            $totalTime += (($hours * 3600) + ($minutes * 60));
                            $departureTime = date('Y-m-d H:i', $totalTime);
                            $lastDepId = $departureCityId;
                        }

                        $insertScheduleDetails[] = [
                            'company_id' => $companyId,
                            'added_by' => $userId,
                            'schedule_id' => $schedule->id,
                            'departure_id' => $departureCityId,
                            'bus_class_id' => $schedule->bus_class_id,
                            'destination_id' => $destinationCityId,
                            'departure_time' => date('H:i', strtotime($departureTime)),
                            'departure_date' => date('Y-m-d', strtotime($departureTime)),
                            'schedule_date' => $scheduleStartDate,
                        ];
                    }
                }

                if (!empty($insertScheduleDetails)) {
                    foreach (array_chunk($insertScheduleDetails, 1000) as $chunk) {
                        ScheduleDetail::insert($chunk);
                    }
                }
            }
        }

        ActivityLog::create([
            "activity_by" => $userId,
            "message" => $user->name . " | updated route (" . $request->routeStartName . '-' . $request->routeEndName . ")",
            "requested_host" => $request->ip(),
            "company_id" => $companyId
        ]);

        DB::commit();

        return ['message' => 'success'];

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Database transaction error: ' . $e->getMessage());
        return response()->json([
            "errors" => [
                "Error" => ['An error occurred during the database transaction.'],
                "debug" => [$e->getMessage()]
            ]
        ], 422);
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
            'limitedSeats' => LimitedSeat::where(["route_id"=>$request->id,"company_id"=>Auth::user()->company_id])->with("departure:id,name","destination:id,name")->get(),
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
                $tervis = TerminalVisibility::where("id",$request->subroutes[0]['subroute_id'])->first();
                LimitedSeat::where(["route_id"=>$tervis->route_id])->delete();
                foreach($request->subroutes as $single)
                {
                    $visibility = TerminalVisibility::where("id",$single['subroute_id'])->first();
                    LimitedSeat::create([
                        "route_id" => $visibility->route_id,
                        "departure_city_id" => $visibility->departure_city_id,
                        "destination_city_id" => $visibility->destination_city_id,
                        "limited_seat" => $single['seat'],
                        "company_id" => Auth::user()->company_id,
                        "added_by" => Auth::user()->id,
                    ]);
                    $visibility->update([
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
