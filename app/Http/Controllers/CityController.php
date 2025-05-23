<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    public function index(Request $request)
    {
        if(!checkForSubmenu("cities"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return City::with('addedBy')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-city"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        DB::beginTransaction();
        $rules = [
            'name' => ['required', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->where("hide",0)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'City Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        try {
                $city = City::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                // $this->cityCombinations($city);
                updateFareTable(Auth::user()->company_id);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added city ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return City::with('addedBy')->find($city->id);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-city"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'City Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                City::find($request->id)->update([
                    'name' => $request->name,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated city ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function hideCity(Request $request)
    {
        if(!checkPermissionButtons("delete-city"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $city = City::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | deleted city ($city->name)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $city->update([
            "hide" => 1
        ]);
    }

    public function cityTerminals(Request $request)
    {
        return Terminal::where('company_id', Auth::user()->company_id)->where('city_id', $request->id)->get();
    }

    public function cityCombinations($city)
    {
        $cities = City::get();
        foreach ($cities as $i => $cityTo) {
            // Creating Relation of Newly added city with Other Cities
            CityToCity::create([
                'departure_city_id' => $city->id,
                'destination_city_id' => $cityTo->id,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,

            ]);
            // Creating Other Cities Relation with Newly added City
            if ($cityTo->id != $city->id) {
                CityToCity::create([
                    'departure_city_id' => $cityTo->id,
                    'destination_city_id' => $city->id,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
        }
    }
}
