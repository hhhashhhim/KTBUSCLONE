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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = auth()->user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return Schedule::with('single_bus_class', 'single_bus.busClass', 'selective_bus', 'singleRoute', 'singleCity', 'singleTerminal', 'updated_by', 'added_by')->orderBy('id')->get();
    }

    public function storeSchedule(Request $request)
    {
        $rules = [
            'name' => 'required',
            'DepartureDateTime' => 'required',
            'DestinationDateTime' => 'required',
            'class' => 'required',
            'route' => 'required',
            'noRows' => 'required',
            'busClass' => 'required',
            'bus' => 'required',
            'seatMap' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Schedule Name is Required',
            'DepartureDateTime.required' => 'Departure Date & Time is Required',
            'DestinationDateTime.required' => 'Destination Date & Time is Required',
            'class.required' => 'Class is Required',
            'route.required' => 'Route is Required',
            'noRows.required' => 'No of Rows is Required',
            'busClass.required' => 'Bus Class is Required',
            'bus.required' => 'Bus is Required',
            'seatMap.required' => 'Seat Map is Required',
        ];
        $this->validate($request, $rules, $customMessages);
        return Schedule::create([
            'name' => $request->name,
            'departure_datetime' => $request->DepartureDateTime,
            'destination_datetime' => $request->DestinationDateTime,
            'bus_class_id' => $request->class,
            'route_id' => $request->route,
            'surcharge_id' => $request->surcharge,
            'discount_id' => $request->discount,
            'route_city_terminal' => $request->addTerminalsOnClick,
            'bus_id' => $request->bus,
            'selected_bus_class_id' => $request->busClass,
            'no_of_rows' => $request->noRows,
            'company_id' => $this->company_id,
            'seat_map' => $request->seatMap,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function editSchedule(Request $request)
    {
        $schedule = Schedule::where('id', $request->id)->where('company_id', $this->company_id)->get();
        $dataArr = [];
        foreach ($schedule[0]->route_city_terminal as $key => $item) {
            $dataArr['city'][$key] = $item['city_id'];
            $dataArr['terminal'][$key] = $item['terminal_id'];
        }
        $cities_id = array_unique($dataArr['city']);
        $terminals_id = array_unique($dataArr['terminal']);
        $city = City::with('terminal')->whereIn('id', $cities_id)->where('company_id', $this->company_id)->get();
    return[
            'cities' => $city,
            'schedules' => $schedule[0],
            'compare_array' => $schedule[0]->route_city_terminal,
        ];
    
    


    }

    public function updateSchedule(Request $request)
    {
        $rules = [
            'name' => 'required',
            'departure_datetime' => 'required',
            'destination_datetime' => 'required',
            'bus_class_id' => 'required',
            'route_id' => 'required',
            'city_id' => 'required',
            'terminal_id' => 'required',
            'selected_bus_class_id' => 'required',
            'bus_id' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Schedule Name is Required',
            'departure_datetime.required' => 'Departure Date & Time is Required',
            'destination_datetime.required' => 'Destination Date & Time is Required',
            'bus_class_id.required' => 'Class is Required',
            'route_id.required' => 'Route is Required',
            'city_id.required' => 'City is Required',
            'terminal_id.required' => 'Terminal is Required',
            'selected_bus_class_id.required' => 'Bus Class is Required',
            'bus_id.required' => 'Bus is Required',
        ];
        $this->validate($request, $rules, $customMessages);

        return Schedule::where('id', $request->id)->update([
            'name' => $request->name,
            'departure_datetime' => $request->departure_datetime,
            'destination_datetime' => $request->destination_datetime,
            'bus_class_id' => $request->bus_class_id,
            'route_id' => $request->route_id,
            'city_id' => $request->city_id,
            'surcharge_id' => $request->surcharge_id,
            'discount_id' => $request->discount_id,
            'terminal_id' => $request->terminal_id,
            'bus_id' => $request->bus_id,
            'selected_bus_class_id' => $request->selected_bus_class_id,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSchedule(Request $request)
    {
        return Schedule::find($request->id)->delete();
    }

    public function getRoutes()
    {
        return Route::get();
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
        return RouteFare::with('fare_class')->where('route_id', $request->id)->select('fare_class_id')->distinct()->get();
    }


    public function getEntire(Request $request)
    {
        return [
            'busName' => Bus::where('id', $request->bus)->pluck('bus_number')->first(),
            'busClass' => FareClass::where('id', $request->busClass)->pluck('name')->first(),
            'city' => City::where('id', $request->city)->pluck('name')->first(),
            'class' => FareClass::where('id', $request->class)->pluck('name')->first(),
            'route' => Route::where('id', $request->route)->pluck('name')->first(),
            'terminal' => Terminal::where('id', $request->terminal)->pluck('name')->first(),
            'discount' => Discount::where('id', $request->discount)->pluck('percentage')->first(),
            'surcharge' => Surcharge::where('id', $request->surcharge)->pluck('percentage')->first(),

        ];
    }

    public function genericCommon()
    {
        return [
            'bus' => Bus::where('company_id', Auth::user()->company_id)->get(),
            'class' => FareClass::where('company_id', Auth::user()->company_id)->get(),
            'routeClass' => FareClass::where('company_id', Auth::user()->company_id)->get(),
            'city' => City::where('company_id', Auth::user()->company_id)->get(),
            'route' => Route::where('company_id', Auth::user()->company_id)->get(),
            'terminal' => Terminal::where('company_id', Auth::user()->company_id)->get(),
            'discount' => Discount::where('company_id', Auth::user()->company_id)->get(),
            'surcharge' => Surcharge::where('company_id', Auth::user()->company_id)->get(),

        ];
    }
}
