<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCard\CardCategory;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardCategoryController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("loyaltyCardCategory"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return CardCategory::with('addedBy:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-card-category"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', 'alpha', Rule::unique('card_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.alpha' => 'Name Must Be Alphabets',
                    'name.unique' => 'Name Must Be Unique',
                ];
                $this->validate($request, $rules, $customMessages);

                $category =  CardCategory::create([
                    'name' => $request->name,
                    'discount_type' => $request->discountType,
                    'flat_discount' => $request->discountFlat ?? 0,
                    'percentage_discount' => $request->discountPercentage ?? 0,
                    'point_type' => $request->pointsType,
                    'point_flat' => $request->pointsFlat ?? 0,
                    'point_distance' => $request->pointsDistance ?? 0,
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
        if(!checkPermissionButtons("edit-card-category"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $category = CardCategory::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->update([
                    'name' => $request->name,
                    'discount_type' => $request->discount_type,
                    'flat_discount' => $request->discount_type == "flat" ? $request->flat_discount : 0,
                    'percentage_discount' => $request->discount_type == "flat" ? 0 : $request->percentage_discount,
                    'point_type' => $request->point_type,
                    'point_flat' => $request->point_type == "flatPoints" ? $request->point_flat : 0,
                    'point_distance' => $request->point_type == "flatPoints" ? 0 : $request->point_distance,
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
