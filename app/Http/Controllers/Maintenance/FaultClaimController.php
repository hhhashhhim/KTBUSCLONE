<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityToCity;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use App\Models\Bus\Bus;
use App\Models\FaultClaimPart;
use App\Models\Hrm\Employee\Employee;
use App\Models\Inventory\Supplier;
use App\Models\Maintenance\DockRequest;
use App\Models\Maintenance\FaultClaim;
use App\Models\Maintenance\InspectionResult;
use App\Models\Maintenance\InspectionResultPart;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaultClaimController extends Controller
{
 public function index(Request $request)
{
    $companyId = Auth::user()->company_id;

    // 🚍 Buses
    $buses = Bus::where('company_id', $companyId)
        ->orderBy('id')
        ->get();

    // 👨‍ Drivers
    $drivers = Employee::where([
        'employee_type' => 1,
        'company_id'    => $companyId,
        'hide'          => 0
    ])->get(["id", "user_id", "name", "cnic"]);

    // ⚙️ Faults with filters
   $faults = FaultClaim::with([
    'bus',
    'driver',
    'dock_requests',
    'claimParts.part:id,name', // ✅ parts added in fault claim
    'inspectionResult.parts.part:id,name' // ✅ parts added in inspection
])
->where('company_id', $companyId)
->when($request->from_date && $request->to_date, function ($q) use ($request) {
    $q->whereBetween('created_at', [
        $request->from_date . " 00:00:00",
        $request->to_date   . " 23:59:59"
    ]);
})
->when($request->status, fn($q) => $q->where('status', $request->status))
->when($request->bus_id, fn($q) => $q->where('bus_id', $request->bus_id))
->get();


    // 🔧 Load all bus-part links
    $links = MaintenancePartLink::with('maintenancePart')
        ->whereIn('bus_id', $buses->pluck('id'))
        ->get();

    // ✅ Same calculation function as fleetDueDetail (applied to link)
    $calculateStatus = function ($link, $bus) {
        if (!$link->maintenancePart) return null;

        $part = $link->maintenancePart;

        // Merge link values into link (not only part)
        $link->name                  = $part->name;
        $link->maintenance_after     = $link->maintenance_after;
        $link->maintenance_at        = $link->maintenance_at;
        $link->maintenance_days      = $link->maintenance_days;
        $link->maintenance_days_date = $link->maintenance_days_date;

        // Defaults
        $link->percentage            = 100;
        $link->due                   = false;
        $link->next_maintenance_date = null;

        if ($link->maintenance_days && $link->maintenance_days_date) {
            // 📅 Date-based
            $startDate = Carbon::parse($link->maintenance_days_date);
            $endDate   = $startDate->copy()->addDays($link->maintenance_days);
            $now       = Carbon::now();

            if ($now->greaterThanOrEqualTo($endDate)) {
                $link->percentage = 0;
                $link->due = true;
            } else {
                $totalDays   = max($startDate->diffInDays($endDate), 1);
                $daysPassed  = $startDate->diffInDays($now);
                $usedPercent = ($daysPassed / $totalDays) * 100;
                $link->percentage = min(max(100 - intval($usedPercent), 0), 100);
            }

            $link->next_maintenance_date = $endDate->toDateString();

        } else {
            // 🚗 Km-based
            $alertReading = $link->maintenance_after + $link->maintenance_at;
            if ($bus->current_reading >= $alertReading) {
                $link->percentage = 0;
                $link->due = true;
            } else {
                $distanceTravelled = $bus->current_reading - $link->maintenance_at;
                $totalDistance     = max($alertReading - $link->maintenance_at, 1);
                $usedPercent       = ($distanceTravelled / $totalDistance) * 100;
                $link->percentage  = min(max(100 - intval($usedPercent), 0), 100);
            }

            $link->next_maintenance_date = null;
        }

        // 🔴 Force due if <= 20%
        if ($link->percentage <= 20) {
            $link->due = true;
        }

        // Extra fields for table
        $link->current_reading = $bus->current_reading;
        $link->alert_reading   = $link->maintenance_after + $link->maintenance_at;

        return $link;
    };

    // Attach parts with health to buses
    $buses = $buses->map(function ($bus) use ($links, $calculateStatus) {
        $busParts = $links->where('bus_id', $bus->id)->map(function ($link) use ($bus, $calculateStatus) {
            return $calculateStatus($link, $bus);
        })->filter()->values();

        return array_merge($bus->toArray(), ['parts' => $busParts]);
    });

    return [
        "faults"  => $faults,
        "drivers" => $drivers,
        "buses"   => $buses,
        "links"   => $links,
    ];
}



public function store(Request $request)
{
    DB::beginTransaction();
    try {
        // ✅ Create Fault Claim
        $fault = FaultClaim::create([
            'bus_id'     => $request->bus_id,
            'driver_id'  => $request->driver_id,
            'description'=> $request->description,
            'status'     => 'pending',
            'added_by'   => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ]);

        // ✅ Create Dock Request
        $dock = DockRequest::create([
            'fault_claim_id' => $fault->id,
            'bus_id'         => $request->bus_id,
            'dock_time'      => $request->dock_time,
            'periority'      => $request->periority,
            'description'    => $request->description,
            'status'         => 'pending',
            'request_type'   => $request->request_type ?? 'regular',
            'added_by'       => Auth::id(),
            'company_id'     => Auth::user()->company_id,
        ]);

        // ✅ Save Selected Parts into fault_claim_parts
        if ($request->has('parts') && is_array($request->parts)) {
            foreach ($request->parts as $partId) {
                FaultClaimPart::create([
                    'part_id'        => $partId,
                    'fault_claim_id' => $fault->id,
                    'dock_request_id'=> $dock->id, // ✅ new column
                    'bus_id'         => $request->bus_id,
                    'status'         => 'pending', // ✅ default
                    'added_by'       => Auth::id(),
                    'company_id'     => Auth::user()->company_id,
                ]);
            }
        }

        DB::commit();
        return response()->json([
            'message' => 'Fault & Dock Request Created Successfully',
            'data'    => null
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error occurred during creation',
            'error'   => $e->getMessage()
        ], 422);
    }
}




    public function submitResult(Request $request)
    {
        DB::beginTransaction();

        try {
            $fault = FaultClaim::where("id", $request->claim_id)->first();

            // CASE: Dock Required — only create DockRequest
            if ($request->status === 'dock_required') {
                DockRequest::create([
                    'fault_claim_id' => $fault->id,
                    'bus_id' => $fault->bus_id,
                    'dock_time' => $request->dock_time,
                    'periority' => $request->periority,
                    'description' => $request->dock_description,
                    'status' => 'pending',
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);

                $fault->update([
                    "status" => "pending"
                ]);
            }
            // CASE: Resolved or No Fault — only create InspectionResult
            else {
                $inspection = InspectionResult::create([
                    'fault_claim_id' => $fault->id,
                    'bus_id' => $fault->bus_id,
                    'driver_id' => $fault->driver_id,
                    'status' => str_replace('_', ' ', $request->status),
                    'repair_type' => $request->repair_type ? str_replace('_', ' ', $request->repair_type) : null,
                    'machanic_name' => $request->mechanic_name,
                    'current_reading' => $request->reading,
                    'maintenance_date' => $request->maintenance_date,
                    'vendor_id' => $request->vendor_id,
                    'amount' => $request->bill_amount,
                    'comments' => $request->comments,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);

                // Add parts only if provided
                if (!empty($request->parts) && is_array($request->parts)) {
                    foreach ($request->parts as $partId) {
                        InspectionResultPart::create([
                            'inspection_result_id' => $inspection->id,
                            'fault_claim_id' => $fault->id,
                            'bus_id' => $fault->bus_id,
                            'part_id' => $partId,
                            'added_by' => Auth::user()->id,
                            'company_id' => Auth::user()->company_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $fault->update([
                    "status" => "resolved"
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Result Submitted Successfully',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error occurred during submission',
                'error' => $e->getMessage(),
            ], 422);
        }
    }


    public function show(Request $request)
    {
        $fault = FaultClaim::with(['bus', 'driver', 'dock_requests.approved'])
            ->where('id', $request->id)
            ->first();

        if (!$fault) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $inspection = InspectionResult::with([
            'bus:id,bus_number',
            'driver:id,name',
            'vendor:id,name',
            'parts.part' => function ($q) {
                $q->select('id', 'name');
            },
            'dockRequest'
        ])->where("fault_claim_id", $request->id)->first();

        return [
            "fault" => $fault,
            "inspection" => $inspection
        ];
    }

   
    public function helperData(Request $request)
    {
        $parts = MaintenancePart::with('addedBy', 'company')->where('company_id', Auth::user()->company_id)->get();
        $vendors = Supplier::all();
        return [
            "parts" => $parts,
            "vendors" => $vendors,
        ];
    }

    public function requests(Request $request)
    {
        $query = FaultClaim::with([
            'bus:id,bus_number',
            'driver:id,name',
            'dock_requests'
        ]);

        // ✅ Filter by date
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // ✅ Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Filter by bus
        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }

        $faults = $query->orderByDesc('id')->get();

        // return also buses for filter dropdown
        $buses = Bus::select('id', 'bus_number')->get();

        return response()->json([
            'faults' => $faults,
            'buses'  => $buses
        ]);
    }

public function pendingDockCount()
{
    $count = DockRequest::where('status', 'pending')->count();

    return response()->json([
        'pending_count' => $count
    ]);
}


    public function approveDockRequest(Request $request)
    {

        DB::beginTransaction();

        try {
            $dock = DockRequest::find($request->id);

            if (!$dock) {
                return response()->json(['message' => 'Dock request not found'], 404);
            }

            // Update DockRequest with status, approver, dock time, and comment
            $dock->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
                'dock_start_time' => $request->dock_time,
                'comments' => $request->comment,
            ]);

            // Update related FaultClaim status
            if ($dock->fault_claim_id) {
                FaultClaim::where('id', $dock->fault_claim_id)
                    ->update(['status' => 'dock time']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Dock request approved successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     public function showRequest(Request $request)
    {
        $fault = FaultClaim::with(['bus', 'driver', 'dock_requests.approved'])
            ->where('id', $request->id)
            ->first();

        if (!$fault) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $inspection = InspectionResult::with([
            'bus:id,bus_number',
            'driver:id,name',
            'vendor:id,name',
            'parts.part' => function ($q) {
                $q->select('id', 'name');
            },
            'dockRequest'
        ])->where("fault_claim_id", $request->id)->first();

        return [
            "fault" => $fault,
            "inspection" => $inspection
        ];
    }

}
