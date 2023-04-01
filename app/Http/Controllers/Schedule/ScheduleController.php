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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        return Schedule::with('fare_class', 'route', 'bus_class', 'addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function storeSchedule(Request $request)
    {
        // this for check time difference added or not against these cities
        $cityIds = array_column($request->cities, 'id');
        foreach ($cityIds as $first) {
            foreach ($cityIds as $second) {
                if ($first != $second) {
                    $checkTimeDiff = FareTable::where([
                        "company_id" => Auth::user()->company_id,
                        "from_city_id" => $first,
                        "to_city_id" => $second,
                        "time_difference" => null
                    ])->first();

                    if ($checkTimeDiff) {
                        return response()->json([
                            "errors" => [
                                "Time Error" => ["Time difference should be added against these cities."]
                            ]
                        ], 422);
                    }
                }
            }
        }


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
                    $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference;
                    $timeDiff = explode(':', $fareTableTime);
                    $totalTime = $totalTime + (($timeDiff[0] * 3600) + ($timeDiff[1] * 60));
                    $departureTime = date("Y-m-d H:i", $totalTime);
                    $lastDepId = $detail->departure_city_id;
                    // this is single schedule end date to calculate schedule completion days
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

        return $schedule;
    }

    public function editSchedule(Request $request)
    {
        $schedule = Schedule::find($request->id);
        $dataArr = [];
        if (!is_null($schedule->route_city_terminal)) {
            foreach ($schedule->route_city_terminal as $key => $item) {
                $dataArr['city'][$key] = $item['city_id'];
                $dataArr['terminal'][$key] = $item['terminal_id'];
            }
            $cities_id = array_unique($dataArr['city']);
            $city = City::with('terminal')->whereIn('id', $cities_id)->where('company_id', Auth::user()->company_id)->get();
            return [
                'cities' => $city,
                'schedules' => $schedule,
                'compare_array' => $schedule->route_city_terminal,
            ];
        } else {
            return response()->json(['message' => 'Please Select Terminals while Adding Schedule'], 422);
        }
    }

    public function updateSchedule(Request $request)
    {
        $req = $request->schedules;
        return Schedule::where('id', $req['id'])->update([
            'name' => $req['name'],
            'start_date' => $req['start_date'],
            'end_date' => $req['end_date'],
            'time' => $req['time'],
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSchedule(Request $request)
    {
        return Schedule::find($request->id)->delete();
    }

    public function getRoutes()
    {
        return Route::where('company_id', Auth::user()->company_id)->get();
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
        return City::with('terminal')->whereIn('id', $data)->get();
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
            'route' => Route::where('company_id', Auth::user()->company_id)->get(),
            'discount' => Discount::where('company_id', Auth::user()->company_id)->get(),
            'surcharge' => Surcharge::where('company_id', Auth::user()->company_id)->get(),
        ];
    }

    public function extend(Request $request)
    {
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

        return $schedule;
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
        return BusClass::with('addedBy')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }

    public function surchargeSelective()
    {
        return Surcharge::where('company_id', Auth::user()->company_id)->get();
    }

    public function discountSelective()
    {
        return Discount::where('company_id', Auth::user()->company_id)->get();
    }
}
