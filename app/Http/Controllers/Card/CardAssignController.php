<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardAssignController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("loyaltyCardAssign"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return CardAssign::with('addedBy:id,name', 'updatedBy:id,name', 'customer', 'cardCategory:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function cardCategories()
    {
        if(!checkForSubmenu("loyaltyCardAssign"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return CardCategory::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-assign-card"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $data = CardAssign::where(['cnic' => plainContactAndCnic($request->customerCNIC), 'company_id' => Auth::user()->company_id])->first();
                if (!$data) {
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
                    $assignCard =  CardAssign::create([
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
                    ActivityLog::create([
                        "activity_by" => Auth::user()->id,
                        "message" => Auth::user()->name." | assigned card (".CardCategory::find($request->cardCategory)->name.") to customer ".$request->customerName ?? $customer->name,
                        "requested_host" => $request->ip(),
                        "company_id" => Auth::user()->company_id
                    ]);
                    DB::commit();
                    return $assignCard;
                } else {
                    return response()->json(["errors" => ["Error" => ["Loyalty Card Already Against Given CNIC Number "]]], 422);
                }
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-assign-card"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $assignCard =  CardAssign::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->first();
                $assignCard->update([
                    'card_category_id' => $request->card_category_id,
                    'expiry_date' => $request->expiry_date,
                    'starting_points' => $request->starting_points,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated card assignation of customer (".$assignCard->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $assignCard;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function getCnic(Request $request)
    {
        if(!checkForSubmenu("loyaltyCardAssign"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if ($request->status == 'addFormCNIC') {
            return Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request['cnicNumber']))->first();
        }
        if ($request->status == 'addFormContact') {
            return Customer::where('company_id', Auth::user()->company_id)->where('contact', plainContactAndCnic($request['phoneNumber']))->first();
        }
    }
}
