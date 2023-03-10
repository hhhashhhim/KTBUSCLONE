<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardAssignController extends Controller
{
    public function index()
    {
        return CardAssign::with('addedBy:id,name', 'updatedBy:id,name', 'customer', 'cardCategory:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function cardCategories()
    {
        return CardCategory::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        $customer = Customer::where('cnic', plainContactAndCnic($request->customerCNIC))->first();
        if (!$customer) {
            $customer = Customer::create([
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
                'name' => $request->customerName,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }
        return CardAssign::create([
            'cnic' => plainContactAndCnic($request->customerCNIC),
            'phone' => plainContactAndCnic($request->contact),
            'name' => $request->customerName,
            'card_category_id' => $request->cardCategory,
            'customer_id' => $customer->id,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function update(Request $request)
    {
        dd($request->all());

//        return CardAssign::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->update([
//            'name' => $request->name,
//            'discount_type' => $request->discount_type,
//            'flat_discount' => $request->flat_discount ?? 0,
//            'percentage_discount' => $request->percentage_discount ?? 0,
//            'point_type' => $request->point_type,
//            'point_flat' => $request->point_flat ?? 0,
//            'point_distance' => $request->point_distance ?? 0,
//            'updated_by' => Auth::user()->id,
//        ]);
    }

    public function getCnic(Request $request)
    {
        if ($request->status == 'addFormCNIC') {
            return Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request['cnicNumber']))->first();
        }
        if ($request->status == 'addFormContact') {
            return Customer::where('company_id', Auth::user()->company_id)->where('contact', plainContactAndCnic($request['phoneNumber']))->first();
        }
    }
}
