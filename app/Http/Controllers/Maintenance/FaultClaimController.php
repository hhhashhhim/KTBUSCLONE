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
use App\Models\Hrm\Employee\Employee;
use App\Models\Inventory\Supplier;
use App\Models\Maintenance\DockRequest;
use App\Models\Maintenance\FaultClaim;
use App\Models\Maintenance\InspectionResult;
use App\Models\Maintenance\InspectionResultPart;
use App\Models\Maintenance\MaintenancePart;
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
        ])
            ->get(["id", "user_id", "name", "cnic"]);

        // ⚙️ Faults with filters
        $faults = FaultClaim::with('dock_requests', 'bus', 'driver')
            ->where('company_id', $companyId)

            // ✅ Date filter
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . " 00:00:00",
                    $request->to_date   . " 23:59:59"
                ]);
            })

            // ✅ Status filter
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })

            // ✅ Bus filter
            ->when($request->bus_id, function ($q) use ($request) {
                $q->where('bus_id', $request->bus_id);
            })


            ->get();

        return [
            "faults"  => $faults,
            "drivers" => $drivers,
            "buses"   => $buses,
        ];
    }


    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $fault = FaultClaim::create([
                'bus_id' => $request->bus_id,
                'driver_id' => $request->driver_id,
                'description' => $request->description,
                'status' => 'pending',
                'added_by' => Auth::user()->id,
                'company_id' => Auth::user()->company_id,
            ]);

            $dock = DockRequest::create([
                'fault_claim_id' => $fault->id,
                'bus_id' => $request->bus_id,
                'dock_time' => $request->dock_time,
                'periority' => $request->periority,
                'description' => $request->description,
                'status' => 'pending',
                'added_by' => Auth::user()->id,
                'company_id' => Auth::user()->company_id,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Fault & Dock Request Created Successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred during creation',
                'error' => $e->getMessage()
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
}
