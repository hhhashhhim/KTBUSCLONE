<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\City;
use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
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
        return Schedule::with('single_bus_class', 'single_bus.busClass', 'selective_bus', 'singleRoute', 'singleCity', 'singleTerminal', 'addedBy')->where('company_id', $this->company_id)->orderBy('id')->get();
    }

    public function storeSchedule(Request $request)
    {
        $rules = [
            'name' => 'required',
            'StartDate' => 'required',
            'EndDate' => 'required',
            'class' => 'required',
            'route' => 'required',
            'noRows' => 'required',
            'noCols' => 'required',
            'busClass' => 'required',
            'bus' => 'required',
            'seatMap' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Schedule Name is Required',
            'StartDate.required' => 'Start Date is Required',
            'EndDate.required' => 'End Date is Required',
            'class.required' => 'Class is Required',
            'route.required' => 'Route is Required',
            'noRows.required' => 'No of Rows is Required',
            'noCols.required' => 'No of Cols is Required',
            'busClass.required' => 'Bus Class is Required',
            'bus.required' => 'Bus is Required',
            'seatMap.required' => 'Seat Map is Required',
        ];
        $this->validate($request, $rules, $customMessages);
        return Schedule::create([
            'name' => $request->name,
            'start_date' => $request->StartDate,
            'end_date' => $request->EndDate,
            'bus_class_id' => $request->class,
            'route_id' => $request->route,
            'surcharge_id' => $request->surcharge,
            'discount_id' => $request->discount,
            'route_city_terminal' => $request->addTerminalsOnClick,
            'bus_id' => $request->bus,
            'selected_bus_class_id' => $request->busClass,
            'no_of_rows' => $request->noRows,
            'no_of_cols' => $request->noCols,
            'company_id' => $this->company_id,
            'seat_map' => $request->seatMap,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function editSchedule(Request $request)
    {
        $schedule = Schedule::where('id', $request->id)->where('company_id', $this->company_id)->first();

        $dataArr = [];
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


    }

    public function updateSchedule(Request $request)
    {
//        dd($request->all());
        $req = $request->schedules;
        return Schedule::where('id', $req['id'])->update([
            'name' => $req['name'],
            'start_date' => $req['start_date'],
            'end_date' => $req['end_date'],
            'bus_class_id' => $req['bus_class_id'],
            'route_id' => $req['route_id'],
            'surcharge_id' => $req['surcharge_id'],
            'discount_id' => $req['discount_id'],
            'route_city_terminal' => $request->updated_route_city_terminal,
            'bus_id' => $req['bus_id'],
            'selected_bus_class_id' => $req['selected_bus_class_id'],
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
            'busName' => Bus::where('company_id', $this->company_id)->where('id', $request->bus)->pluck('bus_number')->first(),
            'busClass' => FareClass::where('company_id', $this->company_id)->where('id', $request->busClass)->pluck('name')->first(),
            'city' => City::where('company_id', $this->company_id)->where('id', $request->city)->pluck('name')->first(),
            'class' => FareClass::where('company_id', $this->company_id)->where('id', $request->class)->pluck('name')->first(),
            'route' => Route::where('company_id', $this->company_id)->where('id', $request->route)->pluck('name')->first(),
            'terminal' => Terminal::where('company_id', $this->company_id)->where('id', $request->terminal)->pluck('name')->first(),
            'discount' => Discount::where('company_id', $this->company_id)->where('id', $request->discount)->pluck('percentage')->first(),
            'surcharge' => Surcharge::where('company_id', $this->company_id)->where('id', $request->surcharge)->pluck('percentage')->first(),

        ];
    }

    public function genericCommon()
    {
        return [
            'bus' => Bus::where('company_id', $this->company_id)->get(),
            'class' => FareClass::where('company_id', $this->company_id)->get(),
            'routeClass' => FareClass::where('company_id', $this->company_id)->get(),
            'city' => City::where('company_id', $this->company_id)->get(),
            'route' => Route::where('company_id', $this->company_id)->get(),
            'terminal' => Terminal::where('company_id', $this->company_id)->get(),
            'discount' => Discount::where('company_id', $this->company_id)->get(),
            'surcharge' => Surcharge::where('company_id', $this->company_id)->get(),

        ];
    }

    public function selected( Request $request ){

        $tickets = Ticket::where('schedule_id',$request->id)->whereDate('date',$request->date)->get();
        $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
        $schedule = Schedule::where('id',$request->id)->select('id','bus_id')
        ->with('single_bus')->first();
        $seatMap = collect($schedule->single_bus->seat_map);
        
        for($i=0;$i<count($seatMap);$i++) {

            $seatMap[$i] = collect($seatMap[$i]);

            foreach ($seatMap[$i] as $j => $column) {
                $seatMap[$i][$j] = collect($seatMap[$i][$j]);
                $result = array_search($column['seatNo'], $ticketSeatNumbers);
                if ($result!==false) {
                    $seatMap[$i][$j]['gender']=$tickets[$result]->gender;
                    $seatMap[$i][$j]['type']=$tickets[$result]->type;
                }
            }

        }
        $schedule->single_bus->seat_map = $seatMap;
        return $schedule;

    }

}
