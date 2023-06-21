<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\Bus\BusSeatMap;
use App\Models\FareClass;
use App\Models\Schedule\TicketClosing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusController extends Controller
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

    public function index()
    {
        return Bus::with('addedBy', 'busClass')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }

    public function storeBus(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'busNumber' => ['required', Rule::unique('buses', 'bus_number')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'fare_class' => 'required|integer',
                    //            'chassisNumber' => 'required',
                    //            'insuranceNumber' => 'required',
                    //            'routePermit' => 'required',
                ];

                $customMessages = [
                    'busNumber.required' => 'Bus Number is Required!',
                    'busNumber.unique' => 'Bus Number is already exist!',
                    'fare_class.required' => 'Bus Class is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $bus =  Bus::create([
                    'bus_number' => $request->busNumber,
                    'fare_class_id' => $request->fare_class,
                    'chassis_number' => $request->chassisNumber,
                    'insurance_number' => $request->insuranceNumber,
                    'route_permit_number' => $request->routePermit,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                DB::commit();
                return $bus;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateBus(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'bus_number' => ['required', Rule::unique('buses', 'bus_number')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],
                    'fare_class_id' => 'required|integer',
                ];

                $customMessages = [
                    'bus_number.required' => 'Bus Number is Required!',
                    'bus_number.unique' => 'Bus Number is already exist!',
                    'fare_class_id.required' => 'Fare Class is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $bus = Bus::where('id', $request->id)->update([
                    'bus_number' => $request->bus_number,
                    'chassis_number' => $request->chassis_number,
                    'insurance_number' => $request->insurance_number,
                    'route_permit_number' => $request->route_permit_number,
                    'fare_class_id' => $request->fare_class_id,
                    'company_id' => Auth::user()->company_id,
                    'updated_by' => Auth::user()->id,
                ]);
                DB::commit();
                return $bus;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function deleteBus(Request $request)
    {
        return Bus::find($request->id)->delete();
    }

    public function getBusData(Request $request)
    {
        return Bus::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();
    }

    public function getBusSchedule(Request $request)
    {
        return TicketClosing::where('bus_id', $request->id)->where('company_id', Auth::user()->company_id)->latest()->first(['id', 'schedule_id', 'schedule_date', 'schedule_time']);
    }
    public function busClasses()
    {
        return BusClass::with('addedBy')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }
}
