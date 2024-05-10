<?php

namespace App\Http\Controllers\FareClass;

use App\Http\Controllers\Controller;
use App\Models\FareClass;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FareClassController extends Controller
{
    protected function index()
    {
        if(!checkForSubmenu("fare-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return FareClass::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function storeFareClass(Request $request)
    {
        if(!checkPermissionButtons("add-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'FareClassName' => ['required', Rule::unique('fare_classes', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'FareClassColor' => 'required',
                ];

                $customMessages = [
                    'FareClassName.required' => 'Fare Class Name is Required!',
                    'name.unique' => 'Fare Class Name is already available!',
                    'FareClassColor.required' => 'Fare Class Color is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $fareClass = FareClass::create([
                    'name' => $request->FareClassName,
                    'color' => $request->FareClassColor,
                    'is_active' => $request->isActive,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                updateFareTable(Auth::user()->company_id);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added fare class (".$request->FareClassName.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $fareClass;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateFareClass(Request $request)
    {
        if(!checkPermissionButtons("edit-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => 'required',
                    'color' => 'required',
                ];

                $customMessages = [
                    'name.required' => 'FareClass Name is Required!',
                    'color.required' => 'FareClass Color is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $fareClass =  FareClass::where('id', $request->id)->update([
                    'name' => $request->name,
                    'color' => $request->color,
                    'is_active' => $request->is_active,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated fare class (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $fareClass;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    // public function deleteFareClass(Request $request)
    // {
    //     return FareClass::find($request->id)->delete();
    // }
}
