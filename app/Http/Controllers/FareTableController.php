<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use Illuminate\Http\Request;
use App\Models\Route\RouteFare;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateSchedulesTime;
use Illuminate\Support\Facades\Log;

class FareTableController extends Controller
{
    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
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
                DB::commit();
                return $this->getFarePrices($request->fare_class);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

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
        try {
                DB::beginTransaction();
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
                DB::commit();
                return response()->json([
                    'message' => 'Updated Successfully',
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateScheduleTimes(Request $request)
    {
        try {
                DB::beginTransaction();
                $job = (new UpdateSchedulesTime(Auth::user()))->onQueue("UpdateSchedulesTime");
                $id = $this->dispatch($job);
                // DB::table("jobs")->where("queue","default")->update([
                //     "progress" => 3233
                // ]);

                // return UpdateSchedulesTime::dispatch(Auth::user());
                DB::commit();
                return $id;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function farePrint(Request $request)
    {
        $routeFareCities = RouteFare::where('route_id', $request->route_id)->where('company_id', Auth::user()->company_id)->with('city_to:id,name', 'city_from:id,name', 'fare_details:id,fare,fare_class', 'fare_details.class:id,name')->get()->groupBy(['departure_city_id', 'destination_city_id']);
        $data = [];
        foreach ($routeFareCities as $cities) {
            foreach ($cities as $city) {
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['departure_city'] = $city[0]->city_from->name;
                $data[$city[0]->city_from->name][$city[0]->city_to->name]['destination_city'] = $city[0]->city_to->name;
                foreach ($city as $fare) {
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name] = $fare->fare_details->class->name . '---';
                    $data[$city[0]->city_from->name][$city[0]->city_to->name][$fare->fare_details->class->name . '_fare'] = $fare->fare_details->fare;
                }
            }
        }
        
        return view('reports.farePrintReport', [
            'data' => $data,
            'th' => FareClass::where('company_id', Auth::user()->company_id)->orderBY('name', 'ASC')->get(),
        ]);
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
