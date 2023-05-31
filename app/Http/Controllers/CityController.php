<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    public function index()
    {
        return City::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', 'alpha', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.alpha' => 'City Name must be in Alphabets',
                    'name.unique' => 'City Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                $city = City::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                $this->cityCombinations($city);
                updateFareTable(Auth::user()->company_id);
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
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', 'alpha', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.alpha' => 'City Name must be in Alphabets',
                    'name.unique' => 'City Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                City::find($request->id)->update([
                    'name' => $request->name,
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function delete(Request $request)
    {
        return City::find($request->id)->delete();
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
