<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateSchedulesTime;

class FareTableController extends Controller
{
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
        return $this->getFarePrices($request->fare_class);
    }

    public function getUpdateCities()
    {
        return City::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function getFarePrices($fare_class)
    {
        $cities = City::with(['city_to' => function ($q) {
            $q->orderBy('name')->where('cities.company_id', Auth::user()->company_id);
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

    public function fareUpdate(Request $request)
    {

        FareTable::where(['from_city_id' => $request->fromCity, 'to_city_id' => $request->toCity, 'fare_class' => $request->fareClass, 'company_id' => Auth::user()->company_id])->update([
            'fare' => $request->updatedFare,
            'updated_by' => Auth::user()->id,
        ]);
        if ($request->reverse == true) {
            FareTable::where(['from_city_id' => $request->toCity, 'to_city_id' => $request->fromCity, 'fare_class' => $request->fareClass, 'company_id' => Auth::user()->company_id])->update([
                'fare' => $request->updatedFare,
                'updated_by' => Auth::user()->id,
            ]);
        }
        return response()->json([
            'message' => 'Updated Successfully',
        ], 200);
    }

    public function updateScheduleTimes(Request $request)
    {
        $job = (new UpdateSchedulesTime(Auth::user()))->onQueue("UpdateSchedulesTime");
        $id = $this->dispatch($job);
        // DB::table("jobs")->where("queue","default")->update([
        //     "progress" => 3233
        // ]);

        // return UpdateSchedulesTime::dispatch(Auth::user());
        return $id;
    }

    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }

    public function updateScheduleTimesProgress()
    {
        $data = DB::table("jobs")->where("queue", "UpdateSchedulesTime")->latest()->first();
        if ($data) {
            return $data;
        } else {
            return 0;
        }
    }

}
