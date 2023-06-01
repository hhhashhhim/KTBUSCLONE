<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use App\Models\Maintenance\FleetMaintenance;
use App\Models\Bus\Bus;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Illuminate\Support\Facades\Log;

class FleetMaintenanceController extends Controller
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
        $data = [
            "mainData" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading","reading_date"]),
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","name"]),
        ];
        return $data;

    }

    public function fleetSinglePartLink(Request $request)
    {
        return Bus::
            with("maintenancePartLink:id,bus_id,part_id,maintenance_after,maintenance_at,maintenance_date",
                "maintenancePartLink.MaintenancePart:id,name")
            ->where("id",$request->id)
            ->where("company_id",Auth::user()->company_id)
            ->select("id","bus_number","current_reading")
            ->first();

    }

    public function fleetPartLink(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'fleetId' => 'required',
                    'currentReading' => 'required',
                    'fleetPart' => 'required',
                    'maintenanceAfter' => 'required',
                    'maintenanceAt' => 'required',
                ];
                $this->validate($request, $rules);

                Bus::where("id",$request->fleetId)->update([
                    "current_reading" => $request->currentReading
                ]);

                foreach($request->fleetPart as $key => $value)
                {
                    $checkExist = MaintenancePartLink::where(["bus_id"=>$request->fleetId,"part_id"=>$request->fleetPart[$key],"company_id"=>Auth::user()->company_id])->first();
                    if(!$checkExist)
                    {
                        MaintenancePartLink::create([
                            "bus_id" => $request->fleetId,
                            "part_id" => $request->fleetPart[$key],
                            "maintenance_after" => $request->maintenanceAfter[$key],
                            "maintenance_at" => $request->maintenanceAt[$key],
                            'added_by' => Auth::user()->id,
                            'company_id' => Auth::user()->company_id,
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateFleetPartLink(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'fleetId' => 'required',
                    'currentReading' => 'required',
                    'fleetPart' => 'required',
                    'maintenanceAfter' => 'required',
                    'maintenanceAt' => 'required',
                ];
                $this->validate($request, $rules);

                Bus::where("id",$request->fleetId)->update([
                    "current_reading" => $request->currentReading
                ]);

                MaintenancePartLink::where(["bus_id"=>$request->fleetId,"company_id"=>Auth::user()->company_id])->delete();

                foreach($request->fleetPart as $key => $value)
                {
                    $checkExist = MaintenancePartLink::where(["bus_id"=>$request->fleetId,"part_id"=>$value,"company_id"=>Auth::user()->company_id])->first();
                    if(!$checkExist)
                    {
                        MaintenancePartLink::create([
                            "bus_id" => $request->fleetId,
                            "part_id" => $request->fleetPart[$key],
                            "maintenance_after" => $request->maintenanceAfter[$key],
                            "maintenance_at" => $request->maintenanceAt[$key],
                            'added_by' => Auth::user()->id,
                            'company_id' => Auth::user()->company_id,
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }


    public function dueMaintenance()
    {
        $due = Bus::
        where("buses.company_id",Auth::user()->company_id)
        ->join("maintenance_part_links","maintenance_part_links.bus_id","buses.id")
        ->join("fleet_maintenance_parts","fleet_maintenance_parts.id","maintenance_part_links.part_id")
        ->whereRaw('buses.current_reading >= maintenance_part_links.maintenance_after + maintenance_part_links.maintenance_at')
        ->select('buses.bus_number','buses.current_reading','maintenance_part_links.bus_id','maintenance_part_links.part_id',
                'maintenance_part_links.maintenance_after','maintenance_part_links.maintenance_at',
                'maintenance_part_links.maintenance_date','fleet_maintenance_parts.name')
        ->get();

        $data = [
            "mainData" => $due,
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","name"]),
        ];
        return $data;
    }

    public function dueMaintenanceAdd(Request $request)
    {
        try {
                DB::beginTransaction();
                Bus::where("id",$request->fleetId)->update([
                    "current_reading" => $request->currentReading,
                    "reading_date" => date('Y-m-d'),
                ]);

                MaintenancePartLink::where(["bus_id"=>$request->fleetId,"part_id"=>$request->partId])->update([
                    "maintenance_at" => $request->currentReading,
                    "maintenance_date" => date("Y-m-d")
                ]);

                $maintenance = FleetMaintenance::create([
                    "bus_id" => $request->fleetId,
                    "part_id" => $request->partId,
                    "amount" => $request->amount,
                    "company_paid" => $request->companyPaid,
                    "evidence" => $this->image($request->evidence)??null,
                    "detail" => $request->detail,
                    "maintenance_type" => $request->maintenanceType,
                    'company_id' => Auth::user()->company_id,
                ]);
                DB::commit();
                return $maintenance;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function dueMaintenanceUpdate(Request $request)
    {
        try {
                DB::beginTransaction();
                if($request->evidence)
                {
                    FleetMaintenance::where("id",$request->maintenanceId)->update([
                        "evidence" => $this->image($request->evidence)??null,
                    ]);
                }

                $maintenance = FleetMaintenance::where("id",$request->maintenanceId)->update([
                    "amount" => $request->amount,
                    "company_paid" => $request->companyPaid,
                    "detail" => $request->detail,
                ]);
                DB::commit();
                return $maintenance;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateMeterReading(Request $request)
    {
        try {
                DB::beginTransaction();
                $fleet = Bus::find($request->fleetId);

                if($fleet->current_reading > $request->currentReading)
                {
                    return response()->json([
                        "errors" => [
                            "Reading Error" => ["New Reading should be greater than current reading"]
                        ]
                    ], 422);
                }
                Bus::where("id",$request->fleetId)->update([
                    "current_reading" => $request->currentReading,
                    "reading_date" => date("Y-m-d")
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function maintenanceRecord()
    {
        $maintenances = FleetMaintenance::
            where("company_id", Auth::user()->company_id)
            ->with("busName:id,bus_number,current_reading","partName:id,name")
            ->orderBy('time','DESC')
            ->get();

        $data = [
            "mainData" => $maintenances,
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number","current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","name"]),
        ];
        return $data;
    }

    // Image Upload
    public function image($image){

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename        = pathinfo($filenameWithExt);
        //get just extension
        $extension       = $image->extension();
        $nameToStore     = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path            = $image->move(public_path('uploads/maintenance/'), $nameToStore);
        return $nameToStore;
    }

}
