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

        $maintenancesHistory = FleetMaintenance::where('bus_id', $request->id)->with('partName')->orderBy('id', 'DESC')->get();

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

        // ✅ Update bus reading
        Bus::where("id", $request->fleetId)->update([
            "current_reading" => $request->currentReading
        ]);

        foreach ($request->fleetPart as $key => $partId) {
            $checkExist = MaintenancePartLink::where([
                "bus_id"     => $request->fleetId,
                "part_id"    => $partId,
                "company_id" => Auth::user()->company_id
            ])->first();

            if (!$checkExist) {
                $useDays = isset($request->useDaysPerRow[$key]) ? $request->useDaysPerRow[$key] : false;

                $createData = [
                    "bus_id"     => $request->fleetId,
                    "part_id"    => $partId,
                    "added_by"   => Auth::user()->id,
                    "company_id" => Auth::user()->company_id,
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
            "company_id"     => Auth::user()->company_id
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

        $rules = [
            'fleetId'             => 'required',
            'currentReading'      => 'required',
            'fleetPart'           => 'required|array',
            'useDaysPerRow'       => 'required|array',
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

        foreach ($request->fleetPart as $key => $partId) {
            $useDays = isset($request->useDaysPerRow[$key]) ? $request->useDaysPerRow[$key] : false;

            $createData = [
                "bus_id"     => $request->fleetId,
                "part_id"    => $partId,
                "added_by"   => Auth::user()->id,
                "company_id" => Auth::user()->company_id,
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

        ActivityLog::create([
            "activity_by"    => Auth::user()->id,
            "message"        => Auth::user()->name . " | updated maintenance part at reading (" . $request->currentReading . ")",
            "requested_host" => $request->ip(),
            "company_id"     => Auth::user()->company_id
        ]);

        DB::commit();

        return response()->json(["message" => "Maintenance parts updated successfully."], 200);
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

        // Main table data
        $due = Bus::with('maintenancePartLink')
            ->get()
            ->map(function ($bus) use ($today) {
                $dueParts = $bus->maintenancePartLink->filter(function ($part) use ($bus, $today) {
                    // Reading-based check
                    $readingDue = false;
                    if (!empty($part->maintenance_after) && !empty($part->maintenance_at)) {
                        $readingDue = $bus->current_reading >= ($part->maintenance_after + $part->maintenance_at);
                    }

                    // Days+Date check
                    $daysDateDue = false;
                    $plusDate = null;
                    if (!empty($part->maintenance_days) && !empty($part->maintenance_days_date)) {
                        $plusDate = \Carbon\Carbon::parse($part->maintenance_days_date)
                            ->addDays($part->maintenance_days)
                            ->format('Y-m-d');
                        $daysDateDue = $today->greaterThanOrEqualTo($plusDate);
                    }

                    $part->maintenance_days_plus_date = $plusDate;

                    return $readingDue || $daysDateDue;
                })->values();

                return [
                    'bus_id'              => $bus->id,
                    'bus_number'          => $bus->bus_number,
                    'current_reading'     => $bus->current_reading,
                    'due_parts'           => $dueParts->count(), // for table display
                    'total_parts'         => $bus->maintenancePartLink->count(),
                    'due_parts_list'      => $dueParts->map(function ($p) {
                        return [
                            'part_id'                     => $p->id,
                            'part_name'                   => $p->part_name ?? '',
                            'maintenance_days'            => $p->maintenance_days,
                            'maintenance_days_date'       => $p->maintenance_days_date,
                            'maintenance_days_plus_date'  => $p->maintenance_days_plus_date
                        ];
                    })->values()
                ];
            })
            ->sortByDesc('due_parts')
            ->values();

        // Bus chart counts
        $buses = Bus::with('maintenancePartLink')->get();
        $dueBusCount = 0;
        $updateBusCount = 0;
        foreach ($buses as $bus) {
            $hasDuePart = $bus->maintenancePartLink->contains(function ($part) use ($bus, $today) {
                $readingDue = false;
                if (!empty($part->maintenance_after) && !empty($part->maintenance_at)) {
                    $readingDue = $bus->current_reading >= ($part->maintenance_after + $part->maintenance_at);
                }
                $daysDateDue = false;
                if (!empty($part->maintenance_days) && !empty($part->maintenance_days_date)) {
                    $plusDate = \Carbon\Carbon::parse($part->maintenance_days_date)
                        ->addDays($part->maintenance_days);
                    $daysDateDue = $today->greaterThanOrEqualTo($plusDate);
                }
                return $readingDue || $daysDateDue;
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

        // Part chart counts
        $partLinks = MaintenancePartLink::with('bus')->get();
        $duePartCount = 0;
        $updatePartCount = 0;
        foreach ($partLinks as $single) {
            $readingDue = false;
            if (!empty($single->maintenance_after) && !empty($single->maintenance_at)) {
                $readingDue = $single->bus->current_reading >= ($single->maintenance_after + $single->maintenance_at);
            }
            $daysDateDue = false;
            if (!empty($single->maintenance_days) && !empty($single->maintenance_days_date)) {
                $plusDate = \Carbon\Carbon::parse($single->maintenance_days_date)
                    ->addDays($single->maintenance_days);
                $daysDateDue = $today->greaterThanOrEqualTo($plusDate);
            }
            if ($readingDue || $daysDateDue) {
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
            "mainData" => $due,
            "busDrop"  => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "bus_number", "current_reading"]),
            "partDrop" => MaintenancePart::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id", "name"]),
            "busChart" => $busChart,
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
