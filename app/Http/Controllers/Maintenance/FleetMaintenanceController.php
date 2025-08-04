<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use App\Models\Maintenance\FleetMaintenance;
use App\Models\Bus\Bus;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FleetMaintenanceController extends Controller
{
    public function index()
    {
        if (!checkForSubmenu("linking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $data = [
            "mainData" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "bus_number", "current_reading", "reading_date"]),
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "bus_number", "current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "name"]),
        ];
        return $data;
    }

    public function fleetSinglePartLink(Request $request)
    {


        if (!checkForSubmenu("part")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Bus::with([
            "maintenancePartLink:id,bus_id,part_id,maintenance_after,maintenance_at,maintenance_date,maintenance_days,maintenance_days_date", // ✅ added
            "maintenancePartLink.MaintenancePart:id,name"
        ])
            ->where("id", $request->id)
            ->where("company_id", Auth::user()->company_id)
            ->select("id", "bus_number", "current_reading")
            ->first();
    }


    public function fleetDueDetail(Request $request)
    {
        $bus = Bus::with('maintenancePartLink.maintenancePart')->findOrFail($request->id);
        $currentReading = $bus->current_reading;

      $bus->sortedPartLink = $bus->maintenancePartLink->map(function ($part) use ($currentReading) {

    if ($part->maintenance_days && $part->maintenance_days_date) {
        $nextMaintenanceDate = Carbon::parse($part->maintenance_days_date)->addDays($part->maintenance_days);
        $part->next_maintenance_date = $nextMaintenanceDate->toDateString(); // pass this separately
        $part->due = Carbon::now()->greaterThanOrEqualTo($nextMaintenanceDate);
    } else {
        $part->next_maintenance_date = null;
        $part->due = $currentReading >= ($part->maintenance_after + $part->maintenance_at);
    }

    $part->current_reading = $currentReading;
    $part->alert_reading = $part->maintenance_after + $part->maintenance_at;

   if ($part->maintenance_days && $part->maintenance_days_date) {
    // Date-based progress
    $startDate = Carbon::parse($part->maintenance_days_date);
    $endDate = $startDate->copy()->addDays($part->maintenance_days);
    $now = Carbon::now();

    if ($now->lessThanOrEqualTo($startDate)) {
        $part->percentage = 100;
    } elseif ($now->greaterThanOrEqualTo($endDate)) {
        $part->percentage = 0;
    } else {
        $totalDays = $startDate->diffInDays($endDate);
        $passedDays = $startDate->diffInDays($now);
        $remainingPercentage = 100 - intval(($passedDays / $totalDays) * 100);
        $part->percentage = max(min($remainingPercentage, 100), 0);
    }
} else {
    // Reading-based progress
    if ($part->current_reading <= $part->maintenance_at) {
        $part->percentage = 100;
    } elseif ($part->current_reading >= $part->alert_reading) {
        $part->percentage = 0;
    } else {
        $calculated = (($part->current_reading - $part->maintenance_at) / ($part->alert_reading - $part->maintenance_at)) * 100;
        $part->percentage = intval(100 - $calculated);
    }
}


    return $part;
})->sortByDesc('due')->values();


        // Chart data
        $partLinks = MaintenancePartLink::with('bus')->where("bus_id", $request->id)->get();
        $duePartCount = 0;
        $updatePartCount = 0;

        foreach ($partLinks as $single) {
            if ($single->maintenance_days && $single->maintenance_date) {
                $nextMaintenanceDate = Carbon::parse($single->maintenance_date)->addDays($single->maintenance_days);
                $hasDuePart = Carbon::now()->greaterThanOrEqualTo($nextMaintenanceDate);
            } else {
                $hasDuePart = $single->bus->current_reading >= ($single->maintenance_after + $single->maintenance_at);
            }

            if ($hasDuePart) {
                $duePartCount++;
            } else {
                $updatePartCount++;
            }
        }

        $partChart = (object)[
            'labels' => ['Due Part', 'Update Part'],
            'series' => [$duePartCount, $updatePartCount]
        ];

        return [
            "due_bus" => $bus,
            "singleBusChart" => $partChart
        ];
    }
    public function fleetPartLink(Request $request)
    {
        // return $request;

        if (!checkPermissionButtons("link-maintenance")) {
            return response()->json(["Error" => ['You are not authorized to access this URL']], 403);
        }

        try {
            DB::beginTransaction();

            $rules = [
                'fleetId' => 'required',
                'currentReading' => 'required',
                'fleetPart' => 'required|array',
                'useDaysPerRow' => 'required|array',
                'maintenanceAfter' => 'nullable|array',
                'maintenanceAt' => 'nullable|array',
                'maintenanceDays' => 'nullable|array',
                'maintenanceDateDays' => 'nullable|array',
            ];
            $this->validate($request, $rules);

            // Update bus current reading
            Bus::where("id", $request->fleetId)->update([
                "current_reading" => $request->currentReading
            ]);
            foreach ($request->fleetPart as $key => $partId) {
                $checkExist = MaintenancePartLink::where([
                    "bus_id" => $request->fleetId,
                    "part_id" => $partId,
                    "company_id" => Auth::user()->company_id
                ])->first();

                if (!$checkExist) {
                    // return "f";
                    $useDays = isset($request->useDaysPerRow[$key]) ? $request->useDaysPerRow[$key] : false;

                    $createData = [
                        "bus_id" => $request->fleetId,
                        "part_id" => $partId,
                        "added_by" => Auth::user()->id,
                        "company_id" => Auth::user()->company_id,
                    ];

                    if ($useDays) {
                        $createData['maintenance_days'] = $request->maintenanceDays[$key] ?? null;
                        $createData['maintenance_days_date'] = $request->maintenanceDateDays[$key] ?? null;
                    } else {
                        $createData['maintenance_after'] = $request->maintenanceAfter[$key] ?? null;
                        $createData['maintenance_at'] = $request->maintenanceAt[$key] ?? null;
                    }

                    MaintenancePartLink::create($createData);
                }
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | linked maintenance part at reading (" . $request->currentReading . ")",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();

            return response()->json(["message" => "Maintenance parts linked successfully."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }


    public function updateFleetPartLink(Request $request)
    {

        if (!checkPermissionButtons("edit-link-maintenance")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        try {
            DB::beginTransaction();

            $rules = [
                'fleetId' => 'required',
                'currentReading' => 'required',
                'fleetPart' => 'required|array',
                'maintenanceAfter' => 'required|array',
                'maintenanceAt' => 'required|array',
                'maintenanceDays' => 'nullable|array',
                'maintenanceDateDays' => 'nullable|array',
            ];
            $this->validate($request, $rules);

            Bus::where("id", $request->fleetId)->update([
                "current_reading" => $request->currentReading
            ]);

            MaintenancePartLink::where([
                "bus_id" => $request->fleetId,
                "company_id" => Auth::user()->company_id
            ])->delete();

            foreach ($request->fleetPart as $key => $value) {
                $checkExist = MaintenancePartLink::where([
                    "bus_id" => $request->fleetId,
                    "part_id" => $value,
                    "company_id" => Auth::user()->company_id
                ])->first();

                if (!$checkExist) {
                    MaintenancePartLink::create([
                        "bus_id" => $request->fleetId,
                        "part_id" => $value,
                        "maintenance_after" => $request->maintenanceAfter[$key],
                        "maintenance_at" => $request->maintenanceAt[$key],
                        "maintenance_days" => $request->maintenanceDays[$key] ?? null,
                        "maintenance_days_date" => $request->maintenanceDateDays[$key] ?? null,
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                    ]);
                }
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated maintenance part at reading ($request->currentReading)",
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



    public function dueMaintenance()
    {
       
        if (!checkForSubmenu("dues")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $due = Bus::with('maintenancePartLink')
            ->get()
            ->map(function ($bus) {
                $dueParts = $bus->maintenancePartLink->filter(function ($part) use ($bus) {
                    return $bus->current_reading >= ($part->maintenance_after + $part->maintenance_at);
                });

                return [
                    'bus_id' => $bus->id,
                    'bus_number' => $bus->bus_number,
                    'current_reading' => $bus->current_reading,
                    'due_parts' => $dueParts->count(),
                    'total_parts' => $bus->maintenancePartLink->count(),
                ];
            })
            ->sortByDesc('due_parts')
            ->values(); // Reset keys (optional)

        $buses = Bus::with('maintenancePartLink')->get();
        $dueBusCount = 0;
        $updateBusCount = 0;
        foreach ($buses as $bus) {
            $hasDuePart = $bus->maintenancePartLink->contains(function ($part) use ($bus) {
                return $bus->current_reading >= ($part->maintenance_after + $part->maintenance_at);
            });

            if ($hasDuePart) {
                $dueBusCount++;
            } else {
                $updateBusCount++;
            }
        }
        $busChart = (object)[
            'labels' => ['Due Buss', 'Update Buss'],
            'series' => [$dueBusCount, $updateBusCount]
        ];

        $partLinks = MaintenancePartLink::with('bus')->get();
        $duePartCount = 0;
        $updatePartCount = 0;
        foreach ($partLinks as $single) {

            $hasDuePart = $single->bus->current_reading >= ($single->maintenance_after + $single->maintenance_at);

            if ($hasDuePart) {
                $duePartCount++;
            } else {
                $updatePartCount++;
            }
        }
        $partChart = (object)[
            'labels' => ['Due Part', 'Update Part'],
            'series' => [$duePartCount, $updatePartCount]
        ];


        $data = [
            "mainData" => $due,
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "bus_number", "current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "name"]),
            "busChart" => $busChart,
            "partChart" => $partChart,
        ];
        return $data;
    }

    public function dueMaintenanceAdd(Request $request)
    {
        if (!checkPermissionButtons("add-maintenance")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $bus = Bus::where("id", $request->fleetId)->first();
            $bus->update([
                "current_reading" => $request->currentReading,
                "reading_date" => date('Y-m-d'),
            ]);

            MaintenancePartLink::where(["bus_id" => $request->fleetId, "part_id" => $request->partId])->update([
                "maintenance_at" => $request->currentReading,
                "maintenance_date" => date("Y-m-d")
            ]);

            $maintenance = FleetMaintenance::create([
                "bus_id" => $request->fleetId,
                "part_id" => $request->partId,
                "amount" => $request->amount,
                "company_paid" => $request->companyPaid,
                "evidence" => $this->image($request->evidence) ?? null,
                "detail" => $request->detail,
                "maintenance_type" => $request->maintenanceType,
                'company_id' => Auth::user()->company_id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | added due maintenance of bus ($bus->bus_number)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
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
        if (!checkPermissionButtons("edit-maintenance")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            if ($request->hasFile("evidence")) {
                FleetMaintenance::where("id", $request->maintenanceId)->update([
                    "evidence" => $this->image($request->evidence) ?? null,
                ]);
            }

            $maintenance = FleetMaintenance::where("id", $request->maintenanceId)->update([
                "amount" => $request->amount,
                "company_paid" => $request->companyPaid,
                "detail" => $request->detail,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated due maintenance",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
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
        if (!checkPermissionButtons("update-meter-reading")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $fleet = Bus::find($request->fleetId);

            if ($fleet->current_reading > $request->currentReading) {
                return response()->json([
                    "errors" => [
                        "Reading Error" => ["New Reading should be greater than current reading"]
                    ]
                ], 422);
            }
            $bus = Bus::where("id", $request->fleetId)->first();
            $bus->update([
                "current_reading" => $request->currentReading,
                "reading_date" => date("Y-m-d")
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated meter reading of ($bus->bus_number)",
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

    public function maintenanceRecord(Request $request)
    {
        if (!checkForSubmenu("records")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $maintenances = FleetMaintenance::where(["company_id" => Auth::user()->company_id, "bus_id" => $request->bus_id])
            ->with("busName:id,bus_number,current_reading", "partName:id,name")
            ->orderBy('time', 'DESC')
            ->get();

        $data = [
            "mainData" => $maintenances,
            "busDrop" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "bus_number", "current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "name"]),
        ];
        return $data;
    }

    // Image Upload
    public function image($image)
    {

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
