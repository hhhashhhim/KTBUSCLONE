<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Terminal\TerminalVisibility;
use App\Models\ActivityLog;
use App\Models\LimitedSeat;
use App\Models\Terminal;
use App\Models\Route\Route;
use App\Models\Route\RouteOnlineTerminal;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Route\RouteFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
            $request->validate([
                'routeStart' => 'required',
                'routeEnd' => 'required',
                'cities' => 'required',
                'terminals' => 'nullable|array',
                'terminals.*' => [
                    'distinct',
                    Rule::exists('terminals', 'id')->where(function ($query) {
                        $query->where('company_id', Auth::user()->company_id)
                            ->where('is_online_terminal', 1)
                            ->where('hide', 0)
                            ->whereNull('deleted_at');
                    }),
                ],
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
            DB::beginTransaction();
            $route = Route::create([
                'name' => $request['routeStart'] . '-' . $request['routeEnd'],
                'via' => $request['routeVia'],
                'online_seats' => $request['routeSeat']??0,
                'commission_route' => $request['commissioRoute'],
                'company_id' => Auth::user()->company_id,
                'added_by' => auth()->user()->id
            ]);
            $this->storeOnlineTerminals($route->id, $request->input('terminals', []));
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
                $this->storeOnlineTerminals($route->id, $request->input('terminals', []));
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
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
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
        $route = Route::with('onlineTerminals')->where([
            'id' => $request->id,
            'company_id' => Auth::user()->company_id,
        ])->firstOrFail();

        $lastFare = $route->fares->last();
        $allCityRoute = $route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
        array_push($allCityRoute, $lastFare->destination_city_id);

        return [
            "route" => $route,
            "cityIds" => $allCityRoute,
            "terminalIds" => $route->onlineTerminals->pluck('id')->toArray(),
        ];
    }
    public function update(Request $request)
{
    if (!checkPermissionButtons("edit-routes")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    // Increase execution time for this specific heavy request
    set_time_limit(300);

    try {
        $request->validate([
            'routeStartName' => 'required',
            'routeEndName' => 'required',
            'id' => [
                'required',
                Rule::exists('routes', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id)
                        ->where('hide', 0)
                        ->whereNull('deleted_at');
                }),
            ],
            'cityIds' => 'required|array',
            'terminals' => 'nullable|array',
            'terminals.*' => [
                'distinct',
                Rule::exists('terminals', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id)
                        ->where('is_online_terminal', 1)
                        ->where('hide', 0)
                        ->whereNull('deleted_at');
                }),
            ],
        ]);

        $companyId = Auth::user()->company_id;
        $userId = Auth::user()->id;
        $routeId = $request->id;
        $existingRouteFares = RouteFare::where([
            'route_id' => $routeId,
            'company_id' => $companyId,
        ])->orderBy('id')->get(['departure_city_id', 'destination_city_id']);
        $currentCityIds = $existingRouteFares->pluck('departure_city_id')->unique()->values()->toArray();
        if ($existingRouteFares->isNotEmpty()) {
            $currentCityIds[] = $existingRouteFares->last()->destination_city_id;
        }
        $citiesChanged = array_map('intval', $currentCityIds) !== array_map('intval', $request->cityIds);

        DB::beginTransaction();

        // 1. Update Main Route
        Route::where(['company_id' => $companyId, 'id' => $routeId])->update([
            'name' => $request['routeStartName'] . '-' . $request['routeEndName'],
            'via' => $request['routeVia'],
            'online_seats' => $request['routeSeat'] ?? 0,
            'commission_route' => $request['commissionRoute'],
            'online_seat_choices' => $request['online_seat_choices'],
        ]);
        RouteOnlineTerminal::where([
            'route_id' => $routeId,
            'company_id' => $companyId,
        ])->delete();
        $this->storeOnlineTerminals($routeId, $request->input('terminals', []));

        if ($citiesChanged) {
            // 2. Clear Old Route Data
            RouteFare::where("route_id", $routeId)->delete();

            // Pre-fetch all possible fares for the cities provided to avoid queries in the loop
            $allFares = FareTable::whereIn('from_city_id', $request->cityIds)
                ->whereIn('to_city_id', $request->cityIds)
                ->get()
                ->groupBy(['from_city_id', 'to_city_id']);

            // Pre-fetch visibility settings to preserve them
            $existingVis = TerminalVisibility::where("route_id", $routeId)
                ->where("company_id", $companyId)
                ->get()
                ->keyBy(fn($item) => $item->departure_city_id . '-' . $item->destination_city_id);

            $routeFareData = [];
            $visibilityData = [];
            $used_cities = [];

            foreach ($request->cityIds as $city) {
                $used_cities[] = $city;
                foreach ($request->cityIds as $innerCity) {
                    if (in_array($innerCity, $used_cities)) continue;

                    // Map Fares from Memory
                    if (isset($allFares[$city][$innerCity])) {
                        foreach ($allFares[$city][$innerCity] as $fare) {
                            $routeFareData[] = [
                            'route_id' => $routeId,
                            'fare_id' => $fare->id,
                            'fare_class_id' => $fare->fare_class,
                            'departure_city_id' => $city,
                            'destination_city_id' => $innerCity,
                            'company_id' => $companyId,
                            'added_by' => $userId
                            ];
                        }
                    }

                    // Map Visibility from Memory
                    $visKey = "$city-$innerCity";
                    $visibilityData[] = [
                    'route_id' => $routeId,
                    'departure_city_id' => $city,
                    'destination_city_id' => $innerCity,
                    'online_visibilty' => $existingVis->has($visKey) ? $existingVis[$visKey]->online_visibilty : 0,
                    'company_id' => $companyId,
                    'added_by' => $userId
                    ];
                }
            }

            // Bulk Insert Route Fares and Visibility
            if (!empty($routeFareData)) {
                foreach (array_chunk($routeFareData, 500) as $chunk) RouteFare::insert($chunk);
            }

            TerminalVisibility::where("route_id", $routeId)->delete();
            if (!empty($visibilityData)) {
                foreach (array_chunk($visibilityData, 500) as $chunk) TerminalVisibility::insert($chunk);
            }

            // 3. Update Schedules
            $schedules = Schedule::where(["company_id" => $companyId, "route_id" => $routeId])->get();
            $routeDetails = RouteFare::where('route_id', $routeId)->get()->groupBy('fare_class_id')->first();

            if ($schedules->isNotEmpty() && $routeDetails) {
                $newScheduleDetails = [];

                foreach ($schedules as $schedule) {
                // Get unique dates that need updating
                $existingDates = ScheduleDetail::where([
                        "company_id" => $companyId,
                        "schedule_id" => $schedule->id
                    ])
                    ->where("schedule_date", '>=', date("Y-m-d"))
                    ->orderBy('id', 'ASC')
                    ->get();

                if ($existingDates->isEmpty()) continue;

                $uniqueDates = $existingDates->pluck('schedule_date')->unique();
                $firstDepTime = $existingDates->first()->departure_time;

                // Delete all details for these dates at once
                ScheduleDetail::where("schedule_id", $schedule->id)
                    ->whereIn("schedule_date", $uniqueDates)
                    ->delete();

                foreach ($uniqueDates as $currentDate) {
                    $lastDepId = $routeDetails[0]->departure_city_id;
                    $totalTime = strtotime("$currentDate $firstDepTime");

                    foreach ($routeDetails as $detail) {
                        if ($lastDepId != $detail->departure_city_id) {
                            // Find time difference from the pre-fetched Fare collection
                            $ft = isset($allFares[$lastDepId][$detail->departure_city_id])
                                    ? $allFares[$lastDepId][$detail->departure_city_id]->first()
                                    : null;

                            $timeDiff = explode(':', $ft->time_difference ?? "00:00");
                            $totalTime += ($timeDiff[0] * 3600) + ($timeDiff[1] * 60);
                            $lastDepId = $detail->departure_city_id;
                        }

                        $newScheduleDetails[] = [
                            'company_id' => $companyId,
                            'added_by' => $userId,
                            'schedule_id' => $schedule->id,
                            'departure_id' => $detail->departure_city_id,
                            'bus_class_id' => $schedule->bus_class_id,
                            'destination_id' => $detail->destination_city_id,
                            'departure_time' => date('H:i', $totalTime),
                            'departure_date' => date('Y-m-d', $totalTime),
                            'schedule_date' => $currentDate,
                        ];

                        // Chunk insert to prevent memory limit issues
                        if (count($newScheduleDetails) >= 1000) {
                            ScheduleDetail::insert($newScheduleDetails);
                            $newScheduleDetails = [];
                        }
                    }
                }
                }
                // Final chunk insert
                if (!empty($newScheduleDetails)) ScheduleDetail::insert($newScheduleDetails);
            }
        }

        ActivityLog::create([
            "activity_by" => $userId,
            "message" => Auth::user()->name . " | updated route (" . $request['routeStartName'] . '-' . $request['routeEndName'] . ")",
            "requested_host" => $request->ip(),
            "company_id" => $companyId
        ]);

        DB::commit();
        return ['message' => 'success'];

    } catch (\Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error('Route Update Error: ' . $e->getMessage());
        return response()->json(["errors" => ["Error" => [$e->getMessage()]]], 422);
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
            'routes' => Route::with('addedBy')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(),
            'terminals' => Terminal::orderBy('name')->where([
                'company_id' => Auth::user()->company_id,
                'is_online_terminal' => 1,
                'hide' => 0,
            ])->select('id', 'name')->get(),
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

    private function storeOnlineTerminals($routeId, array $terminalIds)
    {
        foreach ($terminalIds as $terminalId) {
            RouteOnlineTerminal::create([
                'route_id' => $routeId,
                'terminal_id' => $terminalId,
                'company_id' => Auth::user()->company_id,
            ]);
        }
    }
}
