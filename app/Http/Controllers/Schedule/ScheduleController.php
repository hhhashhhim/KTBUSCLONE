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
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return Schedule::with('fare_class', 'route', 'bus_class', 'addedBy')->where('company_id', $this->company_id)->orderBy('id')->get();
    }

    public function storeSchedule(Request $request)
    {
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
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
        $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
        $days = $this->getDays($schedule->start_date, $schedule->end_date);

        for ($i = 0; $i <= $days; $i++) {
            $lastDepId = $routeDetails[0]->departure_city_id;
            $totalTime = strtotime(date("$schedule->start_date $schedule->time")) + ($i * 86400);

            foreach ($routeDetails as $detail) {

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
                    'company_id' => $this->company_id,
                    'added_by' => Auth::user()->id,
                    'schedule_id' => $schedule->id,
                    'departure_id' => $detail->departure_city_id,
                    'destination_id' => $detail->destination_city_id,
                    'departure_time' => date('H:i', strtotime($departureTime)),
                    'departure_date' => date('Y-m-d', strtotime($departureTime)),
                ]);
            }

        }
        return $schedule;
    }

    public function editSchedule(Request $request)
    {
        $schedule = Schedule::where('id', $request->id)->where('company_id', $this->company_id)->first();
        $dataArr = [];
        if (!is_null($schedule->route_city_terminal)) {
            foreach ($schedule->route_city_terminal as $key => $item) {
                $dataArr['city'][$key] = $item['city_id'];
                $dataArr['terminal'][$key] = $item['terminal_id'];
            }
            $cities_id = array_unique($dataArr['city']);
            $city = City::with('terminal')->whereIn('id', $cities_id)->where('company_id', $this->company_id)->get();
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
        $existSchedule = Schedule::where('id', $req['id'])->where('company_id', $this->company_id)->first();
        return Schedule::where('id', $req['id'])->update([
            'name' => $req['name'],
            'start_date' => $req['start_date'],
            'end_date' => $req['end_date'],
            'bus_class_id' => $req['bus_class_id'],
            'route_id' => $req['route_id'],
            'surcharge_id' => $req['surcharge_id'],
            'discount_id' => $req['discount_id'],
            'route_city_terminal' => !isset($request->updated_route_city_terminal) ? $existSchedule->route_city_terminal : $request->updated_route_city_terminal,
//            'fare_class_id' => $req['fare_class_id'],
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSchedule(Request $request)
    {
        return Schedule::find($request->id)->delete();
    }

    public function getRoutes()
    {
        return Route::where('company_id', $this->company_id)->get();
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
        $cities = City::whereIn('id', $data)->get();

        $finalData = [];
        foreach ($cities as $key => $city) {
            $finalData['cities'] = $cities;
            $finalData['terminal'][$key] = Terminal::with('city')->where('city_id', $city->id)->where('company_id', $this->company_id)->get();
        }
        return $finalData;
    }

    public function getRouteFareClass(Request $request)
    {
        return RouteFare::with('fare_class')->where('company_id', $this->company_id)->where('route_id', $request->id)->select('fare_class_id')->distinct()->get();
    }

    public function getEntire(Request $request)
    {
        return [
            //            'fareClass' => FareClass::where('company_id', $this->company_id)->where('id', $request->fareClass)->first()->name,
            'route' => Route::where('company_id', $this->company_id)->where('id', $request->route)->pluck('name')->first(),
            'city' => City::where('company_id', $this->company_id)->where('id', $request->city)->pluck('name')->first(),
            'busClass' => BusClass::where('company_id', $this->company_id)->where('id', $request->busClass)->pluck('name')->first(),
            'discount' => Discount::where('company_id', $this->company_id)->where('id', $request->discount)->first(),
            'surcharge' => Surcharge::where('company_id', $this->company_id)->where('id', $request->surcharge)->first(),
        ];
    }

    public function genericCommon()
    {
        return [
            'route' => Route::where('company_id', $this->company_id)->get(),
            'discount' => Discount::where('company_id', $this->company_id)->get(),
            'surcharge' => Surcharge::where('company_id', $this->company_id)->get(),
        ];
    }

    public function extend(Request $request)
    {
        $schedule = Schedule::where('id', $request->id)->where('company_id', $this->company_id)->first();
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
                    'company_id' => $this->company_id,
                    'added_by' => Auth::user()->id,
                    'schedule_id' => $schedule->id,
                    'departure_id' => $detail->departure_city_id,
                    'destination_id' => $detail->destination_city_id,
                    'departure_time' => date('H:i', strtotime($departureTime)),
                    'departure_date' => date('Y-m-d', strtotime($departureTime)),
                ]);
            }

        };

        return $schedule;
    }

    public function selected(Request $request)
    {
        if (!$request->departureCity || !$request->destinationCity || !$request->date) {
            echo "Error";
            return [];
        }
        // Getting Already Booked Tickets
        $tickets = Ticket::with('departure_city', 'destination_city', 'schedule', 'customer', 'company', 'addedBy')
            ->where('company_id', $this->company_id)->where('schedule_id', $request->id)
            ->whereDate('date', $request->date)->get();
        $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
        // Getting Already Booked Tickets
        $scheduleDetail = ScheduleDetail::where('schedule_id', $request->id)->where('company_id', $this->company_id)->where('departure_id', $request->departureCity)->where('destination_id', $request->destinationCity)->first();


        $schedule = Schedule::where('id', $request->id)
            ->where('company_id', $this->company_id)
            ->select('id', 'route_id', 'bus_class_id', 'time')
            ->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')
            ->first();

        $start_datetime = new DateTime(date('Y-m-d H:i:s'));
        $end_datetime = new DateTime(date('Y-m-d') . ' ' . $scheduleDetail->departure_time);
        $diffInMins = ($end_datetime->getTimestamp() - $start_datetime->getTimestamp()) / 60;
        $leavingIn30Min = $diffInMins > 30 ? false : true;
        // return dd($leavingIn30Min);
        // Fare Fetching About the Schedule

        // $route_departure_city_id = $schedule->route->fares->first()->departure_city_id;
        // $route_destination_city_id = $schedule->route->fares->last()->destination_city_id;
//        dd($route_destination_city_id);
        $fareForAllClasses = FareTable::where('from_city_id', $request->departureCity)->where('to_city_id', $request->destinationCity)
            ->where('company_id', $this->company_id)
            ->get()->unique('fare_class');

        // getting cities sequence for checking which city will be after other one
        $lastFare = $schedule->route->fares->last();
        $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
        array_push($allFaresOfRoute, $lastFare->destination_city_id);


        $fareClasses = FareClass::where('company_id', $this->company_id)->get();
        // return ( $fareForAllClasses );
        if (count($fareClasses) != count($fareForAllClasses)) {
            return response()->json([
                "errors" => [
                    "Fare Error" => ["Please Fill the Fare Table Completely First ( For All Fare Classes ) !!!"]
                ]
            ], 422);
        }

//        dd($schedule->fare_class_id, $schedule->bus_class->seat_map);
//        $fare = (float)$fareForAllClasses->where('fare_class', $schedule->fare_class_id)->first()->fare;
        // Looping Through the seat of the bus
        $seatMap = $schedule->bus_class->seat_map;

        for ($i = 0; $i < count($seatMap); $i++) {
            foreach ($seatMap[$i] as $j => $column) {
                // adding fare to each seat
                if ($column['reserved']) {
                    $seatMap[$i][$j]['fare'] = (float)$fareForAllClasses->where('fare_class', $column['class'])->first()->fare;
                }
                $result = isset($column['seatNo']) ? array_search($column['seatNo'], $ticketSeatNumbers) : false;


                if ($result !== false && $leavingIn30Min != true) {
                    $seatMap[$i][$j]['id'] = $tickets[$result]['id'];
                    $seatMap[$i][$j]['gender'] = $tickets[$result]['gender'];
                    $seatMap[$i][$j]['partial'] = $tickets[$result]['is_partial'];
                    $seatMap[$i][$j]['type'] = $tickets[$result]['type'];
                    $seatMap[$i][$j]['remarks'] = $tickets[$result]['remarks'];
                    $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                    $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                    $seatMap[$i][$j]['booked_by'] = $tickets[$result]['addedBy']['name'];
                    $seatMap[$i][$j]['departure_city_name'] = $tickets[$result]['departure_city']['name'];
                    $seatMap[$i][$j]['destination_city_name'] = $tickets[$result]['destination_city']['name'];
                    $seatMap[$i][$j]['fare'] = 0;

                    if ($tickets[$result]['is_partial'] == 1) {

                        // Condition for validation that departure city and destination city in the request should be "before" the partial seat's targeted cities
                        $before = (array_search($request->departureCity, $allFaresOfRoute) < array_search($tickets[$result]['departure_city_id'], $allFaresOfRoute) &&
                            array_search($request->departureCity, $allFaresOfRoute) < array_search($tickets[$result]['destination_city_id'], $allFaresOfRoute) &&
                            array_search($request->destinationCity, $allFaresOfRoute) <= array_search($tickets[$result]['departure_city_id'], $allFaresOfRoute) &&
                            array_search($request->destinationCity, $allFaresOfRoute) < array_search($tickets[$result]['destination_city_id'], $allFaresOfRoute));

                        // Condition for validation that departure city and destination city in the request should be "After" the partial seat's targeted cities
                        $after = (
                            array_search($request->departureCity, $allFaresOfRoute) > array_search($tickets[$result]['departure_city_id'], $allFaresOfRoute) &&
                            array_search($request->departureCity, $allFaresOfRoute) >= array_search($tickets[$result]['destination_city_id'], $allFaresOfRoute) &&
                            array_search($request->destinationCity, $allFaresOfRoute) > array_search($tickets[$result]['departure_city_id'], $allFaresOfRoute) &&
                            array_search($request->destinationCity, $allFaresOfRoute) > array_search($tickets[$result]['destination_city_id'], $allFaresOfRoute)
                        );


                        if ($before || $after) {
                            // removing partial tag for that seats which fullfill the conditions
                            unset($seatMap[$i][$j]['partial']);
                            unset($seatMap[$i][$j]['type']);
                            unset($seatMap[$i][$j]['gender']);

                        }

                        $seatMap[$i][$j]['departure_city'] = $tickets[$result]['departure_city']->name;
                        $seatMap[$i][$j]['destination_city'] = $tickets[$result]['destination_city']->name;
                    }
                }
                if ($result !== false && $leavingIn30Min) {
                    $seatMap[$i][$j]['over_issue'] = true;
                    $seatMap[$i][$j]['departure_city'] = $tickets[$result]['departure_city']->id;
                    $seatMap[$i][$j]['destination_city'] = $tickets[$result]['destination_city']->id;
                    $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                    $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                    $seatMap[$i][$j]['booked_by'] = $tickets[$result]['addedBy']['name'];
                    $seatMap[$i][$j]['departure_city_name'] = $tickets[$result]['departure_city']['name'];
                    $seatMap[$i][$j]['destination_city_name'] = $tickets[$result]['destination_city']['name'];
                }
//                 print_r($column);
                if (isset($column['class'])) {
                    $class = $fareClasses->where('id', $column['class'])->first();
                    $seatMap[$i][$j]['color'] = $class ? $class->color : '';
                    if ($class && $class->is_active == 0) {
                        return response()->json([
                            "errors" => [
                                "Fare Error" => ["This Bus Class Includes a Class Which is't Active Please Active That Class First !!!"]
                            ]
                        ], 422);
                    }
                    if ($class) {
                        $seatMap[$i][$j]['fare'] = (float)$fareForAllClasses->where('fare_class', $class->id)->first()->fare;
                    }
                }
            }
        }
        $schedule->bus_class->seat_map = $seatMap;
        unset($schedule->route);
        return $schedule;
    }

    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }

}
