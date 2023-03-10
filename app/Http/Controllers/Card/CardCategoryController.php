<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\LoyalityCard\CardCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CardCategoryController extends Controller
{
    public function index()
    {
        return CardCategory::with('addedBy:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        return CardCategory::create([
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
    }

    public function update(Request $request)
    {
        return CardCategory::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->update([
            'name' => $request->name,
            'discount_type' => $request->discount_type,
            'flat_discount' => $request->flat_discount ?? 0,
            'percentage_discount' => $request->percentage_discount ?? 0,
            'point_type' => $request->point_type,
            'point_flat' => $request->point_flat ?? 0,
            'point_distance' => $request->point_distance ?? 0,
            'updated_by' => Auth::user()->id,
        ]);
    }
}
