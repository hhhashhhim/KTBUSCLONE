<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $urlName =  request()->segment(count(request()->segments()));
            foreach (Auth::user()->role->permissions as $key => $single) {
                foreach ($single['childs'] as $index => $item) {
                    if ($item['name']  == $urlName && !$item['allow']) {
                        return response()->json(['error' => 'Not authorized.'], 403);
                    } else {
                        return $next($request);
                    }
                }
            }
        });
    }
    public function index()
    {
        return City::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
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
        return City::with('addedBy')->find($city->id);
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('cities', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'City Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        return City::find($request->id)->update([
            'name' => $request->name,
        ]);
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
