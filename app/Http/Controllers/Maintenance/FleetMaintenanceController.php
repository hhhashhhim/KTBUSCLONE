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
use Dotenv\Exception\ValidationException;
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

    // Helper function for percentage & due calculation
    $calculateStatus = function ($part) use ($currentReading) {
        if ($part->maintenance_days && $part->maintenance_days_date) {
            // Date-based calculation
            $startDate = Carbon::parse($part->maintenance_days_date);
            $endDate = $startDate->copy()->addDays($part->maintenance_days);
            $now = Carbon::now();

            if ($now->greaterThanOrEqualTo($endDate)) {
                $part->percentage = 0;
                $part->due = true;
            } else {
                $totalDays = max($startDate->diffInDays($endDate), 1);
                $daysPassed = $startDate->diffInDays($now);
                $usedPercent = ($daysPassed / $totalDays) * 100;
                $part->percentage = min(max(100 - intval($usedPercent), 0), 100);
                $part->due = false;
            }

            $part->next_maintenance_date = $endDate->toDateString();
        } else {
            // Reading-based calculation
            $alertReading = $part->maintenance_after + $part->maintenance_at;

            if ($currentReading >= $alertReading) {
                $part->percentage = 0;
                $part->due = true;
            } else {
                $distanceTravelled = $currentReading - $part->maintenance_at;
                $totalDistance = max($alertReading - $part->maintenance_at, 1);
                $usedPercent = ($distanceTravelled / $totalDistance) * 100;
                $part->percentage = min(max(100 - intval($usedPercent), 0), 100);
                $part->due = false;
            }

            $part->next_maintenance_date = null;
        }

        // ✅ Extra rule: if health percentage ≤ 20%, mark as due
        if ($part->percentage <= 20) {
            $part->due = true;
        }

        $part->current_reading = $currentReading;
        $part->alert_reading = $part->maintenance_after + $part->maintenance_at;

        return $part;
    };

    // Apply calculation to each part
    $bus->sortedPartLink = $bus->maintenancePartLink
        ->map($calculateStatus)
        ->sortByDesc('due')
        ->values();

    // Chart data
    $partLinks = MaintenancePartLink::with('bus')->where("bus_id", $request->id)->get();
    $duePartCount = 0;
    $updatePartCount = 0;

    foreach ($partLinks as $single) {
        $status = $calculateStatus($single);
        if ($status->due) {
            $duePartCount++;
        } else {
            $updatePartCount++;
        }
    }

    $partChart = (object)[
        'labels' => ['Due Part', 'Update Part'],
        'series' => [$duePartCount, $updatePartCount]
    ];

    $maintenancesHistory = FleetMaintenance::where('bus_id', $request->id)
        ->with('partName')
        ->orderBy('id', 'DESC')
        ->get();

    return [
        "due_bus" => $bus,
        "singleBusChart" => $partChart,
        "maintenancesHistory" => $maintenancesHistory
    ];
}


   public function fleetPartLink(Request $request)
{
    if (!checkPermissionButtons("link-maintenance")) {
        return response()->json(["Error" => ['You are not authorized to access this URL']], 403);
    }

    try { 
        DB::beginTransaction();

        $rules = [
            'fleetId'             => 'required',
            'currentReading'      => 'required',
            'fleetPart'           => 'required|array',
            'useDaysPerRow'       => 'nullable|array',
            'maintenanceAfter'    => 'nullable|array',
            'maintenanceAt'       => 'nullable|array',
            'maintenanceDays'     => 'nullable|array',
            'maintenanceDateDays' => 'nullable|array',
        ];
        $this->validate($request, $rules);

        $companyId = Auth::user()->company_id;

        // ✅ Update bus reading (restricted by company_id)
        Bus::where("id", $request->fleetId)
            ->where("company_id", $companyId)
            ->update([
                "current_reading" => $request->currentReading
            ]);

        foreach ($request->fleetPart as $key => $partId) {
            $checkExist = MaintenancePartLink::where([
                "bus_id"     => $request->fleetId,
                "part_id"    => $partId,
                "company_id" => $companyId
            ])->first();

            if (!$checkExist) {
                $useDays = isset($request->useDaysPerRow[$key]) ? $request->useDaysPerRow[$key] : false;

                $createData = [
                    "bus_id"     => $request->fleetId,
                    "part_id"    => $partId,
                    "added_by"   => Auth::user()->id,
                    "company_id" => $companyId,
                ];

                if ($useDays) {
                    $createData['maintenance_days'] = isset($request->maintenanceDays[$key]) && $request->maintenanceDays[$key] !== ""
                        ? (int) $request->maintenanceDays[$key] : null;

                    $createData['maintenance_days_date'] = isset($request->maintenanceDateDays[$key]) && $request->maintenanceDateDays[$key] !== ""
                        ? $request->maintenanceDateDays[$key] : null;
                } else {
                    $createData['maintenance_after'] = isset($request->maintenanceAfter[$key]) && $request->maintenanceAfter[$key] !== ""
                        ? (int) $request->maintenanceAfter[$key] : null;

                    $createData['maintenance_at'] = isset($request->maintenanceAt[$key]) && $request->maintenanceAt[$key] !== ""
                        ? (int) $request->maintenanceAt[$key] : null;
                }

                MaintenancePartLink::create($createData);
            }
        }

        // ✅ Log activity
        ActivityLog::create([
            "activity_by"    => Auth::user()->id,
            "message"        => Auth::user()->name . " | linked maintenance part at reading (" . $request->currentReading . ")",
            "requested_host" => $request->ip(),
            "company_id"     => $companyId
        ]);

        DB::commit();

        return response()->json(["message" => "Maintenance parts linked successfully."], 200);
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Database transaction error: ' . $e->getMessage() . ' Line: ' . $e->getLine());

        return response()->json([
            "errors" => [
                "Error" => [$e->getMessage(), "Line: " . $e->getLine()]
            ]
        ], 422);
    }
}




public function updateFleetPartLink(Request $request)
{
    if (!checkPermissionButtons("edit-link-maintenance")) {
        return response()->json(["Error" => ['You are not authorized to access this URL']], 403);
    }

    try {
        DB::beginTransaction();

        // ✅ Validation rules
        $rules = [
            'fleetId'             => 'required',
            'currentReading'      => 'required',
            'fleetPart'           => 'required|array|min:1',
            'useDaysPerRow'       => 'nullable|array',
            'maintenanceAfter'    => 'nullable|array',
            'maintenanceAt'       => 'nullable|array',
            'maintenanceDays'     => 'nullable|array',
            'maintenanceDateDays' => 'nullable|array',
        ];

        $this->validate($request, $rules);

        // ✅ Update bus reading
        Bus::where("id", $request->fleetId)->update([
            "current_reading" => $request->currentReading
        ]);

        // ✅ Delete old links
        MaintenancePartLink::where([
            "bus_id"     => $request->fleetId,
            "company_id" => Auth::user()->company_id
        ])->delete();

        // ✅ Re-insert part links
        foreach ($request->fleetPart as $key => $partId) {
            $useDays = isset($request->useDaysPerRow[$key]) 
                ? (bool) $request->useDaysPerRow[$key] 
                : false;

            $createData = [
                "bus_id"     => $request->fleetId,
                "part_id"    => $partId,
                "added_by"   => Auth::user()->id,
                "company_id" => Auth::user()->company_id,
            ];

            // ✅ Always handle both paths
            $createData['maintenance_after']      = $request->maintenanceAfter[$key] ?? null;
            $createData['maintenance_at']         = $request->maintenanceAt[$key] ?? null;
            $createData['maintenance_days']       = $request->maintenanceDays[$key] ?? null;
            $createData['maintenance_days_date']  = $request->maintenanceDateDays[$key] ?? null;

            MaintenancePartLink::create($createData);
        }

        // ✅ Log activity
        ActivityLog::create([
            "activity_by"    => Auth::user()->id,
            "message"        => Auth::user()->name . " | updated maintenance part at reading (" . $request->currentReading . ")",
            "requested_host" => $request->ip(),
            "company_id"     => Auth::user()->company_id
        ]);

        DB::commit();

        return response()->json(["message" => "Maintenance parts updated successfully."], 200);

    } catch (ValidationException $e) {
        return response()->json([
            "errors" => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Database transaction error: ' . $e->getMessage() . ' Line: ' . $e->getLine());

        return response()->json([
            "errors" => ["Error" => [$e->getMessage(), "Line: " . $e->getLine()]]
        ], 422);
    }
}

 public function dueMaintenance()
{
    if (!checkForSubmenu("dues")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $today = \Carbon\Carbon::today();
    $companyId = Auth::user()->company_id;

    // Main table data (only user’s company buses)
    $due = Bus::with('maintenancePartLink')
        ->where('company_id', $companyId)
        ->get()
        ->map(function ($bus) use ($today) {
            $dueParts = $bus->maintenancePartLink->filter(function ($part) use ($bus, $today) {
                $isDue = false;
                $plusDate = null;

                // ✅ Reading-based calculation
                if (!empty($part->maintenance_after) && !empty($part->maintenance_at)) {
                    $alertReading = $part->maintenance_after + $part->maintenance_at;
                    if ($bus->current_reading >= $alertReading) {
                        $isDue = true;
                        $part->percentage = 0;
                    } else {
                        $distanceTravelled = $bus->current_reading - $part->maintenance_at;
                        $totalDistance = max($alertReading - $part->maintenance_at, 1);
                        $usedPercent = ($distanceTravelled / $totalDistance) * 100;
                        $part->percentage = max(0, min(100, 100 - intval($usedPercent)));

                        if ($part->percentage <= 20) {
                            $isDue = true;
                        }
                    }
                }

                // ✅ Days-based calculation
                if (!empty($part->maintenance_days) && !empty($part->maintenance_days_date)) {
                    $startDate = \Carbon\Carbon::parse($part->maintenance_days_date);
                    $endDate = $startDate->copy()->addDays($part->maintenance_days);
                    $plusDate = $endDate->format('Y-m-d');

                    if ($today->greaterThanOrEqualTo($endDate)) {
                        $isDue = true;
                        $part->percentage = 0;
                    } else {
                        $totalDays = max($startDate->diffInDays($endDate), 1);
                        $daysPassed = $startDate->diffInDays($today);
                        $usedPercent = ($daysPassed / $totalDays) * 100;
                        $part->percentage = max(0, min(100, 100 - intval($usedPercent)));

                        if ($part->percentage <= 20) {
                            $isDue = true;
                        }
                    }
                }

                $part->maintenance_days_plus_date = $plusDate;
                return $isDue;
            })->values();

            $totalParts = $bus->maintenancePartLink->count();
            $dueCount   = $dueParts->count();
            $healthPercentage = $totalParts > 0
                ? round((($totalParts - $dueCount) / $totalParts) * 100)
                : 0;

            return [
                'bus_id'            => $bus->id,
                'bus_number'        => $bus->bus_number,
                'current_reading'   => $bus->current_reading,
                'due_parts'         => $dueCount,
                'total_parts'       => $totalParts,
                'health_percentage' => $healthPercentage,
                'due_parts_list'    => $dueParts->map(function ($p) {
                    return [
                        'part_id'                     => $p->id,
                        'part_name'                   => $p->part_name ?? '',
                        'maintenance_days'            => $p->maintenance_days,
                        'maintenance_days_date'       => $p->maintenance_days_date,
                        'maintenance_days_plus_date'  => $p->maintenance_days_plus_date,
                        'health_percentage'           => $p->percentage ?? 0
                    ];
                })->values()
            ];
        })
        ->sortByDesc('due_parts')
        ->values();

    // 🚍 Bus chart counts (filter by company_id too)
    $buses = Bus::with('maintenancePartLink')
        ->where('company_id', $companyId)
        ->get();

    $dueBusCount = 0;
    $updateBusCount = 0;
    foreach ($buses as $bus) {
        $hasDuePart = $bus->maintenancePartLink->contains(function ($part) use ($bus, $today) {
            $isDue = false;

            if (!empty($part->maintenance_after) && !empty($part->maintenance_at)) {
                $alertReading = $part->maintenance_after + $part->maintenance_at;
                if ($bus->current_reading >= $alertReading) {
                    return true;
                }
                $distanceTravelled = $bus->current_reading - $part->maintenance_at;
                $totalDistance = max($alertReading - $part->maintenance_at, 1);
                $usedPercent = ($distanceTravelled / $totalDistance) * 100;
                $percentage = max(0, min(100, 100 - intval($usedPercent)));
                if ($percentage <= 20) return true;
            }

            if (!empty($part->maintenance_days) && !empty($part->maintenance_days_date)) {
                $startDate = \Carbon\Carbon::parse($part->maintenance_days_date);
                $endDate = $startDate->copy()->addDays($part->maintenance_days);
                if ($today->greaterThanOrEqualTo($endDate)) {
                    return true;
                }
                $totalDays = max($startDate->diffInDays($endDate), 1);
                $daysPassed = $startDate->diffInDays($today);
                $usedPercent = ($daysPassed / $totalDays) * 100;
                $percentage = max(0, min(100, 100 - intval($usedPercent)));
                if ($percentage <= 20) return true;
            }

            return $isDue;
        });

        if ($hasDuePart) {
            $dueBusCount++;
        } else {
            $updateBusCount++;
        }
    }

    $busChart = (object)[
        'labels' => ['Due Bus', 'Updated Bus'],
        'series' => [$dueBusCount, $updateBusCount]
    ];

    // ⚙️ Part chart counts (also filter by company_id)
    $partLinks = MaintenancePartLink::with('bus')
        ->whereHas('bus', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->get();

    $duePartCount = 0;
    $updatePartCount = 0;

    foreach ($partLinks as $single) {
        $isDue = false;

        if (!empty($single->maintenance_after) && !empty($single->maintenance_at)) {
            $alertReading = $single->maintenance_after + $single->maintenance_at;
            if ($single->bus->current_reading >= $alertReading) {
                $isDue = true;
            } else {
                $distanceTravelled = $single->bus->current_reading - $single->maintenance_at;
                $totalDistance = max($alertReading - $single->maintenance_at, 1);
                $usedPercent = ($distanceTravelled / $totalDistance) * 100;
                $percentage = max(0, min(100, 100 - intval($usedPercent)));
                if ($percentage <= 20) $isDue = true;
            }
        }

        if (!empty($single->maintenance_days) && !empty($single->maintenance_days_date)) {
            $startDate = \Carbon\Carbon::parse($single->maintenance_days_date);
            $endDate = $startDate->copy()->addDays($single->maintenance_days);
            if ($today->greaterThanOrEqualTo($endDate)) {
                $isDue = true;
            } else {
                $totalDays = max($startDate->diffInDays($endDate), 1);
                $daysPassed = $startDate->diffInDays($today);
                $usedPercent = ($daysPassed / $totalDays) * 100;
                $percentage = max(0, min(100, 100 - intval($usedPercent)));
                if ($percentage <= 20) $isDue = true;
            }
        }

        if ($isDue) {
            $duePartCount++;
        } else {
            $updatePartCount++;
        }
    }

    $partChart = (object)[
        'labels' => ['Due Part', 'Updated Part'],
        'series' => [$duePartCount, $updatePartCount]
    ];

    return [
        "mainData"  => $due,
        "busDrop"   => Bus::orderBy('id')->where('company_id', $companyId)->get(["id", "bus_number", "current_reading"]),
        "partDrop"  => MaintenancePart::orderBy('id')->where('company_id', $companyId)->get(["id", "name"]),
        "busChart"  => $busChart,
        "partChart" => $partChart,
    ];
}

public function dueMaintenanceAdd(Request $request)
{
    if (!checkPermissionButtons("add-maintenance")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    try {
        DB::beginTransaction();

        // Update bus reading
        $bus = Bus::where("id", $request->fleetId)->first();
        $bus->update([
            "current_reading" => $request->currentReading,
            "reading_date"    => date('Y-m-d'),
        ]);

        // Get maintenance link first
        $link = MaintenancePartLink::where([
            "bus_id"  => $request->fleetId,
            "part_id" => $request->partId
        ])->first();

        if ($link) {
            // Always update maintenance_date
            $link->update([
                "maintenance_date" => date("Y-m-d"),
            ]);

            // If maintenance_days exists, refresh only days date
            if (!is_null($link->maintenance_days)) {
                $link->update([
                    "maintenance_days_date" => date("Y-m-d"),
                ]);
            } else {
                // Otherwise also update reading
                $link->update([
                    "maintenance_at" => $request->currentReading,
                ]);
            }
        }

        // Create maintenance record
        $maintenance = FleetMaintenance::create([
            "bus_id"           => $request->fleetId,
            "part_id"          => $request->partId,
            "amount"           => $request->amount,
            "company_paid"     => $request->companyPaid,
            "evidence"         => $this->image($request->evidence) ?? null,
            "detail"           => $request->detail,
            "maintenance_type" => $request->maintenanceType,
            "company_id"       => Auth::user()->company_id,
        ]);

        ActivityLog::create([
            "activity_by"    => Auth::user()->id,
            "message"        => Auth::user()->name . " | added due maintenance of bus ($bus->bus_number)",
            "requested_host" => $request->ip(),
            "company_id"     => Auth::user()->company_id
        ]);

        DB::commit();
        return $maintenance;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Database transaction error: ' . $e->getMessage());
        return response()->json([
            "errors" => ["Error" => [$e->getMessage()]]
        ], 422);
    }
}




public function dueMaintenanceUpdate(Request $request)
{
    if (!checkPermissionButtons("edit-maintenance")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    try {
        DB::beginTransaction();

        // Update evidence if new file uploaded
        if ($request->hasFile("evidence")) {
            FleetMaintenance::where("id", $request->maintenanceId)->update([
                "evidence" => $this->image($request->evidence) ?? null,
            ]);
        }

        // Update fleet maintenance record
        FleetMaintenance::where("id", $request->maintenanceId)->update([
            "amount"       => $request->amount,
            "company_paid" => $request->companyPaid,
            "detail"       => $request->detail,
        ]);

        // Update maintenance link
        if ($request->fleetId && $request->partId) {
            $link = MaintenancePartLink::where([
                "bus_id"  => $request->fleetId,
                "part_id" => $request->partId
            ])->first();

            if ($link) {
                // Always update maintenance_date
                $link->update([
                    "maintenance_date" => date("Y-m-d"),
                ]);

                // If maintenance_days exists, refresh only days date
                if (!is_null($link->maintenance_days)) {
                    $link->update([
                        "maintenance_days_date" => date("Y-m-d"),
                    ]);
                } else {
                    // Otherwise update reading too
                    $link->update([
                        "maintenance_at" => $request->currentReading,
                    ]);
                }
            }
        }

        ActivityLog::create([
            "activity_by"    => Auth::user()->id,
            "message"        => Auth::user()->name . " | updated due maintenance",
            "requested_host" => $request->ip(),
            "company_id"     => Auth::user()->company_id
        ]);

        DB::commit();
        return response()->json(["success" => true]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Database transaction error: ' . $e->getMessage());
        return response()->json([
            "errors" => ["Error" => [$e->getMessage()]]
        ], 422);
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
