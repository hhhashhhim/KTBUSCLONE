<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\ScheduleTerminalSequence;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::
            with('fare_class', 'route', 'bus_class', 'addedBy')
            ->with(["schedule_time"=>function($q) use ($request){
                $q->where("schedule_date",'=',$request->departure_date??date("Y-m-d"))->select("schedule_id","schedule_date","departure_time");
            }])
            ->where(function($q) use ($request){
                if($request->bus_class)
                {
                    $q->where("bus_class_id",$request->bus_class);
                }
                if($request->route)
                {
                    $q->where("route_id",$request->route);
                }
                if ($request->departure_date) {
                    $q->whereDate("start_date", "<=", $request->departure_date)
                      ->whereDate("end_date", ">=", $request->departure_date);
                }
            })
            ->where(['company_id'=> Auth::user()->company_id,"hide"=>0])
            ->orderBy('id')
            ->get();

        return $schedules;
    }

    public function storeSchedule(Request $request)
    {
        try {
                DB::beginTransaction();
                // $cityIds = array_column($request->cities, 'id');
                // foreach ($cityIds as $first) {
                //     foreach ($cityIds as $second) {
                //         if ($first != $second) {
                //             $checkTimeDiff = FareTable::where([
                //                 "company_id" => Auth::user()->company_id,
                //                 "from_city_id" => $first,
                //                 "to_city_id" => $second,
                //                 "time_difference" => null
                //             ])->first();

                //             if ($checkTimeDiff) {
                //                 return response()->json([
                //                     "errors" => [
                //                         "Time Error" => ["Time difference should be added against these cities."]
                //                     ]
                //                 ], 422);
                //             }
                //         }
                //     }
                // }


                $rules = [
                    'name' => 'required',
                    'StartDate' => 'required',
                    'EndDate' => 'required',
                    'route' => 'required',
                    'busClass' => 'required',
                    'time' => 'required',
                ];

                $customMessages = [
                    'name.required' => 'Schedule Name is Required',
                    'StartDate.required' => 'Start Date is Required',
                    'EndDate.required' => 'End Date is Required',
                    'route.required' => 'Route is Required',
                    'time.required' => 'Time Field is Required',
                    'busClass.required' => 'Bus Class is Required',
                ];
                $this->validate($request, $rules, $customMessages);
                $schedule = Schedule::create([
                    'name' => $request->name,
                    'start_date' => $request->StartDate,
                    'end_date' => $request->EndDate,
                    'route_id' => $request->route,
                    'time' => $request->time,
                    'surcharge_id' => $request->surcharge,
                    'discount_id' => $request->discount,
                    'bus_class_id' => $request->busClass,
                    'route_city_terminal' => $request->addTerminalsOnClick ?? [],
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);

                foreach ($request->addTerminalsOnClick as $key => $single) {
                    ScheduleTerminalSequence::create([
                        'schedule_id' => $schedule->id,
                        'city_id' => $single['city_id'],
                        'terminal_id' => $single['terminal_id'],
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
                $days = $this->getDays($schedule->start_date, $schedule->end_date);

                for ($i = 0; $i <= $days; $i++) {
                    $lastDepId = $routeDetails[0]->departure_city_id;
                    $totalTime = strtotime(date("$schedule->start_date $schedule->time")) + ($i * 86400);
                    $scheduleStartDate = date("Y-m-d", $totalTime);
                    foreach ($routeDetails as $detail) {

                        if ($lastDepId == $detail->departure_city_id) {
                            $departureTime = date("Y-m-d H:i", $totalTime);
                        } else {
                            $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference??"00:00";
                            $timeDiff = explode(':', $fareTableTime);
                            $totalTime = $totalTime + (($timeDiff[0] * 3600) + ($timeDiff[1] * 60));
                            $departureTime = date("Y-m-d H:i", $totalTime);
                            $lastDepId = $detail->departure_city_id;
                        }
                        $scheduleEndDate = date("Y-m-d", $totalTime);
                        ScheduleDetail::create([
                            'company_id' => Auth::user()->company_id,
                            'added_by' => Auth::user()->id,
                            'schedule_id' => $schedule->id,
                            'departure_id' => $detail->departure_city_id,
                            'destination_id' => $detail->destination_city_id,
                            'departure_time' => date('H:i', strtotime($departureTime)),
                            'departure_date' => date('Y-m-d', strtotime($departureTime)),
                            'schedule_date' => $scheduleStartDate, // schedule departure date
                        ]);
                    }
                }
                // get completion days of schedule
                $schedule_days = $this->getDays($scheduleStartDate, $scheduleEndDate);
                Schedule::where("id", $schedule->id)->update([
                    'schedule_days' => $schedule_days,
                ]);
                DB::commit();
                return $schedule;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function editSchedule(Request $request)
    {
        $schedule = Schedule::find($request->id);
        $routeFares = RouteFare::where('route_id', $schedule->route_id)->select('departure_city_id', 'destination_city_id')->get();
        $data = [];
        foreach ($routeFares as $i => $routeFare) {
            if ($i == 0) {
                $data[] = $routeFare->departure_city_id;
            }
            $data[] = $routeFare->destination_city_id;
        }
        $data = collect($data)->unique();
        $compare = City::with(['terminal' => function ($q) {
            $q->where("is_online_terminal", null);
            return $q->orWhere("is_online_terminal", 0)->select('id', 'city_id', 'name');
        }])->whereIn('id', $data)->get();
        $terminalID = [];
        foreach ($schedule->route_city_terminal as $key => $item) {
            $terminalID[] = $item['terminal_id'];

        }

        $schedule['terminalId'] = $terminalID;
        $schedule['terminalName'] = Terminal::whereIn('id', $terminalID)->pluck('name');
        return [
            'schedules' => $schedule,
            'compare' => $compare,
        ];
    }

    public function updateScheduleTime(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'time' => 'required',
                ];

                $customMessages = [
                    'start_date.required' => 'Start Date is Required',
                    'end_date.required' => 'End Date is Required',
                    'time.required' => 'time is Required',
                ];
                $this->validate($request, $rules, $customMessages);

                $detail = ScheduleDetail::where(["company_id"=>Auth::user()->company_id,"schedule_id"=>$request->schedule_id])->whereBetween("schedule_date",[$request->start_date,$request->end_date])->get();
                foreach($detail as $single)
                {
                    $updatedTime = date("Y-m-d H:i:s",strtotime(($single->departure_date.' '.$single->departure_time)) + ($request->time*60));
                    $single->update([
                        "departure_date" => date("Y-m-d",strtotime($updatedTime)),
                        "departure_time" => date("H:i:s",strtotime($updatedTime)),
                    ]);  
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
        
    }
    public function updateSchedule(Request $request)
    {
        try {
                DB::beginTransaction();
                $req = $request->schedules;
        //        dd($req);
                $schedule = Schedule::where('id', $req['id'])->update([
                    'name' => $req['name'],
                    'start_date' => $req['start_date'],
                    'end_date' => $req['end_date'],
                    'surcharge_id' => $req['surcharge_id'],
                    'discount_id' => $req['discount_id'],
                    'updated_by' => Auth::user()->id,
                    'route_id' => $req['route_id'],
                    'bus_class_id' => $req['bus_class_id'],
                    'route_city_terminal' => $req['route_city_terminal'] ?? [],
                ]);
                ScheduleTerminalSequence::where('schedule_id', $req['id'])->delete();

                foreach ($req['route_city_terminal'] as $key => $single) {
                    ScheduleTerminalSequence::create([
                        'schedule_id' => $req['id'],
                        'city_id' => $single['city_id'],
                        'terminal_id' => $single['terminal_id'],
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                DB::commit();
                return $schedule;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function hideSchedule(Request $request)
    {
        return Schedule::find($request->id)->update([
            "hide" => 1
        ]);
    }

    public function getRoutes()
    {
        return Route::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get();
    }

    public function getCity(Request $request)
    {
        $routeFares = RouteFare::where('route_id', $request->id)->select('departure_city_id', 'destination_city_id')->get();
        $data = [];
        foreach ($routeFares as $i => $routeFare) {
            if ($i == 0) {
                $data[] = $routeFare->departure_city_id;
            }
            $data[] = $routeFare->destination_city_id;
        }
        $data = collect($data)->unique();
        return City::with(['terminal' => function ($q) {
            $q->where("is_online_terminal", null);
            return $q->orWhere("is_online_terminal", 0);
        }])->whereIn('id', $data)->get();
    }

    public function getRouteFareClass(Request $request)
    {
        return RouteFare::with('fare_class')->where('company_id', Auth::user()->company_id)->where('route_id', $request->id)->select('fare_class_id')->distinct()->get();
    }

    public function getEntire(Request $request)
    {
        return [
            //            'fareClass' => FareClass::where('company_id', Auth::user()->company_id)->where('id', $request->fareClass)->first()->name,
            'route' => Route::where('company_id', Auth::user()->company_id)->where('id', $request->route)->pluck('name')->first(),
            'city' => City::where('company_id', Auth::user()->company_id)->where('id', $request->city)->pluck('name')->first(),
            'busClass' => BusClass::where('company_id', Auth::user()->company_id)->where('id', $request->busClass)->pluck('name')->first(),
            'discount' => Discount::where('company_id', Auth::user()->company_id)->where('id', $request->discount)->first(),
            'surcharge' => Surcharge::where('company_id', Auth::user()->company_id)->where('id', $request->surcharge)->first(),
        ];
    }

    public function genericCommon()
    {
        return [
            'route' => Route::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(),
            'discount' => Discount::where('company_id', Auth::user()->company_id)->get(),
            'surcharge' => Surcharge::where('company_id', Auth::user()->company_id)->get(),
        ];
    }

    public function extend(Request $request)
    {
        try {
                DB::beginTransaction();
                $schedule = Schedule::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();
                $lastEndDate = date("Y-m-d", strtotime($schedule->end_date) + 86400);
                $schedule->update([
                    'end_date' => date("Y-m-d", strtotime(date("Y-m-d", strtotime($schedule->end_date)) . "+" . (int)$request->extended_days . "days")),
                    'extended_days' => (int)$request->extended_days,
                ]);
                $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
                $days = $this->getDays($lastEndDate, $schedule->end_date);

                for ($i = 0; $i <= $days; $i++) {
                    $lastDepId = $routeDetails[0]->departure_city_id;
                    $totalTime = strtotime(date("$lastEndDate $schedule->time")) + ($i * 86400);
                    $scheduleStartDate = date("Y-m-d", $totalTime);
                    foreach ($routeDetails as $key => $detail) {

                        if ($lastDepId == $detail->departure_city_id) {
                            $departureTime = date("Y-m-d H:i", $totalTime);
                        } else {
                            $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference;
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
                            'destination_id' => $detail->destination_city_id,
                            'departure_time' => date('H:i', strtotime($departureTime)),
                            'departure_date' => date('Y-m-d', strtotime($departureTime)),
                            'schedule_date' => $scheduleStartDate, // schedule departure date
                        ]);
                    }
                };
                DB::commit();
                return $schedule;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }

    public function allBuses(Request $request)
    {
        $busClassId = Schedule::where(['company_id' => Auth::user()->company_id, 'id' => $request['id']])->first(['bus_class_id'])->bus_class_id;
        return Bus::where('company_id', Auth::user()->company_id)->where('fare_class_id', $busClassId)->get(['id', 'bus_number']);
    }

    public function fareClasses()
    {
        return FareClass::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function busClasses()
    {
        return BusClass::with('addedBy')->orderBy('id')->where(['company_id'=> Auth::user()->company_id,"hide" => 0])->get();
    }

    public function surchargeSelective()
    {
        return Surcharge::where('company_id', Auth::user()->company_id)->where('is_active', 1)->get();
    }

    public function discountSelective()
    {
        return Discount::where('company_id', Auth::user()->company_id)->where('is_active', 1)->get();
    }
}
