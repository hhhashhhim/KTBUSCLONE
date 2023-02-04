<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Route\RouteFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateSchedulesTime;

class FareTableController extends Controller
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

    public function store(Request $request)
    {
        $request->validate([
            'fare' => 'required',
            'fare_class' => 'required',
        ]);

        $company_id = auth()->user()->is_super_admin == 0 ? auth()->user()->company_id : $request->company_id;
        if ($request->created == 1) {
            updateFare($request, $company_id);
        } else {
           storeFare($request, $company_id);
        }
        return $this->getFarePrices($request->fare_class);

    }

    public function record(Request $request)
    {

        $request->validate([
            'fare_class' => 'required',
        ]);

        return $this->getFarePrices($request->fare_class);
    }

    public function getFarePrices($fare_class)
    {
        $cities = City::with(['city_to' => function ($q) { $q->orderBy('name')->where('cities.company_id', Auth::user()->company_id);
        }])
            ->where('cities.company_id', Auth::user()->company_id)
            ->orderBy('name')->get();

        $subRoutes = $cities->map(function ($city_from) use ($fare_class) {

            // Storing Destination Cities into new Array Index
            $city_from['destinationCities'] = $city_from->city_to;

            // Fetching and storing the fare of the Departure and the Destination city Fare.
            foreach ($city_from['destinationCities'] as $j => $city_to) {
                $routeCities = $city_to->pivot;
                $city_from['destinationCities'][$j]['fare'] = FareTable::where('from_city_id', $routeCities->departure_city_id)
                    ->where('to_city_id', $routeCities->destination_city_id)
                    ->where('fare_class', $fare_class)
                    ->value('fare');
                $city_from['destinationCities'][$j]['time_difference'] = FareTable::where('from_city_id', $routeCities->departure_city_id)
                    ->where('to_city_id', $routeCities->destination_city_id)
                    ->where('fare_class', $fare_class)
                    ->value('time_difference');
            }
            unset($city_from['city_to']);
            return $city_from;
        });

        return $subRoutes;
    }

    public function getFareClass()
    {
        return FareClass::where('company_id', Auth::user()->company_id)->orderBy('id')->select('id', 'name')->get(['name', 'id']);
    }

    public function check(Request $request)
    {
        $checkFare = FareTable::where('fare_class', $request->fare_class)->where('from_city_id', $request->from)->where('to_city_id', $request->to)->where('company_id', Auth::user()->company_id)->select('id', 'fare', 'distance_in_km', 'time_difference', 'fare_class')->first();
        return response($checkFare, 200);
    }
    
    public function updateScheduleTimes(Request $request)
    {
        // $start_date = date("Y-m-d");
        // $schedules = Schedule::where("end_date",'>=', $start_date)->where(["company_id"=>Auth::user()->company_id])->get();
        
        // foreach($schedules as $schedule)
        // {
        //     $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
        //     $days = $this->getDays($start_date, $schedule->end_date);

        //     for ($i = 0; $i <= $days; $i++) {
        //         $lastDepId = $routeDetails[0]->departure_city_id;
        //         $totalTime = strtotime(date("$start_date $schedule->time")) + ($i * 86400);
        //         $scheduleStartDate = date("Y-m-d", $totalTime);
        //         foreach ($routeDetails as $detail) {

        //             if ($lastDepId == $detail->departure_city_id) {
        //                 $departureTime = date("Y-m-d H:i", $totalTime);
        //             } else {
        //                 $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference;
        //                 $timeDiff = explode(':', $fareTableTime);
        //                 $totalTime = $totalTime + (($timeDiff[0] * 3600) + ($timeDiff[1] * 60));
        //                 $departureTime = date("Y-m-d H:i", $totalTime);
        //                 $lastDepId = $detail->departure_city_id;
        //                 // this is single schedule end date to calculate schedule completion days
        //             }
        //             $scheduleEndDate = date("Y-m-d", $totalTime);
                    
        //             ScheduleDetail::where([
        //                 'company_id' => Auth::user()->company_id,
        //                 'schedule_id' => $schedule->id,
        //                 'departure_id' => $detail->departure_city_id,
        //                 'destination_id' => $detail->destination_city_id,
        //                 'schedule_date' => $scheduleStartDate,// schedule departure date
        //             ])->update([
        //                 'departure_time' => date('H:i', strtotime($departureTime)),
        //                 'departure_date' => date('Y-m-d', strtotime($departureTime)),
        //             ]);
        //         }

        //     }
        //     // get completion days of schedule
        //     $schedule_days = $this->getDays($scheduleStartDate, $scheduleEndDate);
        //     Schedule::where("id", $schedule->id)->update([
        //         'schedule_days' => $schedule_days,
        //     ]);
        // }
        UpdateSchedulesTime::dispatch();
        
    }

    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }


}
