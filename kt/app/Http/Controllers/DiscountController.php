<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
       return Discount::orderBy('id')->where('is_active', 1)->select('name','id', 'percentage')->get();
    }

    public function storeDiscount(Request $request)
    {
        $rules = [
            'name' => 'required',
            'percentage' => 'required|numeric|min:0|max:100',
        ];

        $customMessages = [
            'name.required' => 'Discount Name is Required!',
            'percentage.required' => 'Discount percentage is Required!',
            'percentage.min' => 'Discount percentage never be less then 0',
            'percentage.max' => 'Discount percentage never be greater then 100',
        ];
        $this->validate($request, $rules, $customMessages);
        return Discount::create(['name' => $request->name, 'percentage' => $request->percentage, 'is_active' => $request->active]);
    }
}
