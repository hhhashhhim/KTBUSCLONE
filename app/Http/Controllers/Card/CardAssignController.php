<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $data = CardAssign::where(['cnic' => plainContactAndCnic($request->customerCNIC), 'company_id' => Auth::user()->company_id])->first();
        if (!$data) {
            $customer = Customer::where('cnic', plainContactAndCnic($request->customerCNIC))->first();
            if (!$customer) {
                return response()->json(["errors" => ["Error" => ["To Assign The Loyalty Card, Customer Already Added To your Record "]]], 422);
            }
            return CardAssign::create([
                'rfId' =>$request->rfId,
                'cnic' => plainContactAndCnic($request->customerCNIC) ?? plainContactAndCnic($customer->cnic),
                'phone' => plainContactAndCnic($request->contact) ?? plainContactAndCnic($customer->contact),
                'name' => $request->customerName ?? $customer->name,
                'card_category_id' => $request->cardCategory,
                'customer_id' => $customer->id,
                'company_id' => Auth::user()->company_id,
                'expiry_date' => $request->expiryDate,
                'starting_points' => $request->startingPoints,
                'added_by' => Auth::user()->id,
            ]);
        } else {
            return response()->json(["errors" => ["Error" => ["Loyalty Card Already Against Given CNIC Number "]]], 422);
        }
    }

    public function update(Request $request)
    {
        return CardAssign::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->update([
            'card_category_id' => $request->card_category_id,
            'expiry_date' => $request->expiry_date,
            'starting_points' => $request->starting_points,
            'updated_by' => Auth::user()->id,
        ]);
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
