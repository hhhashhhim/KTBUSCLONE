<?php

namespace App\Http\Controllers\DiscountType;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DiscountType\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DiscountTypeController extends Controller
{
   public function index()
    {
        if(!checkForSubmenu("discountCardtype"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return DiscountType::with('addedBy:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-card-discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', 'alpha', Rule::unique('discount_types', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.alpha' => 'Name Must Be Alphabets',
                    'name.unique' => 'Name Must Be Unique',
                ];
                $this->validate($request, $rules, $customMessages);

                $category =  DiscountType::create([
                    'name' => $request->name,
                    'discount_type' => $request->discountType,
                    'flat_discount' => $request->discountFlat ?? 0,
                    'percentage_discount' => $request->discountPercentage ?? 0,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added card category $request->name",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $category;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-card-discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $category = DiscountType::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->update([
                    'name' => $request->name,
                    'discount_type' => $request->discount_type,
                    'flat_discount' => $request->discount_type == "flat" ? $request->flat_discount : 0,
                    'percentage_discount' => $request->discount_type == "flat" ? 0 : $request->percentage_discount,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated card category $request->name",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $category;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
}
