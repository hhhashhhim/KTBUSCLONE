<?php

namespace App\Http\Controllers\Discount;

use App\Http\Controllers\Controller;
use App\Models\Discount\Discount;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("discounts"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Discount::with('addedBy')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }

    public function storeDiscount(Request $request)
    {
        if(!checkPermissionButtons("add-discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('discounts', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Discount Name is Required!',
                    'name.unique' => 'Discount Name not be Repeated!',
                ];
                $this->validate($request, $rules, $customMessages);
                $discount = Discount::create([
                    'name' => $request->name,
                    'type' => $request->type,
                    'percentage' => $request->type == "percentage" ?  $request->percentage : null,
                    'flat' => $request->type == "flat" ? $request->flat : null,
                    'company_id' => Auth::user()->company_id,
                    'is_active' => $request->active,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | stored discount (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return Discount::with('addedBy')->find($discount->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function updateDiscount(Request $request)
    {
        if(!checkPermissionButtons("edit-discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => 'required',
                ];

                $customMessages = [
                    'name.required' => 'Discount Name is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $discount = Discount::where('id', $request->id)->update([
                    'name' => $request->name,
                    'type' => $request->type,
                    'percentage' => $request->type == "percentage" ? $request->percentage: null,
                    'flat' => $request->type == "flat" ? $request->flat: null,
                    'is_active' => $request->is_active,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated discount (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $discount;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    // public function deleteDiscount(Request $request)
    // {
    //     return Discount::find($request->id)->delete();
    // }
}
