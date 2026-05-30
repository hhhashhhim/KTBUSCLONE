<?php

namespace App\Http\Controllers\DiscountType;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\DiscountType\DiscountAssign;
use App\Models\DiscountType\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountCardAssignController extends Controller
{
    public function index(Request $request)
    {
        if (!checkForSubmenu("discountCardAssign")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        return DiscountAssign::with('addedBy:id,name', 'updatedBy:id,name', 'customer', 'discountCardType:id,name')
            ->where('company_id', Auth::user()->company_id)
            ->when($request->filled('rfId'), function ($query) use ($request) {
                $query->where('rfId', 'like', '%' . $request->rfId . '%');
            })
            ->when($request->filled('cnic'), function ($query) use ($request) {
                $query->where('cnic', 'like', '%' . plainContactAndCnic($request->cnic) . '%');
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%');
            })
            ->when($request->filled('phone'), function ($query) use ($request) {
                $query->where('phone', 'like', '%' . plainContactAndCnic($request->phone) . '%');
            })
            ->when($request->filled('card_type_id') && $request->card_type_id != 0, function ($query) use ($request) {
                $query->where('card_type_id', $request->card_type_id);
            })
            ->when($request->filled('expiry_from'), function ($query) use ($request) {
                $query->whereDate('expiry_date', '>=', $request->expiry_from);
            })
            ->when($request->filled('expiry_to'), function ($query) use ($request) {
                $query->whereDate('expiry_date', '<=', $request->expiry_to);
            })
            ->get();
    }

    public function cardCategories()
    {
        if (!checkForSubmenu("discountCardAssign")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return DiscountType::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        if (!checkPermissionButtons("add-assign-discount")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        // try {
        DB::beginTransaction();
        $data = DiscountAssign::where(['cnic' => plainContactAndCnic($request->customerCNIC), 'company_id' => Auth::user()->company_id])->first();
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
            $assignCard =  DiscountAssign::create([
                'rfId' => $request->rfId,
                'cnic' => plainContactAndCnic($request->customerCNIC) ?? plainContactAndCnic($customer->cnic),
                'phone' => plainContactAndCnic($request->contact) ?? plainContactAndCnic($customer->contact),
                'name' => $request->customerName ?? $customer->name,
                'card_type_id' => $request->cardCategory,
                'customer_id' => $customer->id,
                'company_id' => Auth::user()->company_id,
                'expiry_date' => $request->expiryDate,
                'added_by' => Auth::user()->id,
            ]);
            $discountType = DiscountType::find($request->cardCategory);
            $discountName = $discountType ? $discountType->name : 'Unknown Card Type';

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | assigned card (" . $discountName . ") to customer " . ($request->customerName ?? $customer->name),
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return $assignCard;
        } else {
            return response()->json(["errors" => ["Error" => ["Loyalty Card Already Against Given CNIC Number "]]], 422);
        }

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('Database transaction error: ' . $e->getMessage());
        //     return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        // }
    }

    public function update(Request $request)
    {
        if (!checkPermissionButtons("edit-assign-discount")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $assignCard = DiscountAssign::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->first();
            if (!$assignCard) {
                DB::rollBack();
                return response()->json(["errors" => ["Error" => ['Assigned discount card not found.']]], 404);
            }

            $cleanCnic = plainContactAndCnic($request->cnic);
            $cleanPhone = plainContactAndCnic($request->phone);

            $duplicateCard = DiscountAssign::where('company_id', Auth::user()->company_id)
                ->where('cnic', $cleanCnic)
                ->where('id', '!=', $assignCard->id)
                ->first();

            if ($duplicateCard) {
                DB::rollBack();
                return response()->json(["errors" => ["Error" => ["Discount Card Already Against Given CNIC Number "]]], 422);
            }

            $customer = Customer::where([
                'id' => $assignCard->customer_id,
                'company_id' => Auth::user()->company_id,
            ])->first();

            if ($customer) {
                $duplicateCustomer = Customer::where('company_id', Auth::user()->company_id)
                    ->where('cnic', $cleanCnic)
                    ->where('id', '!=', $customer->id)
                    ->first();

                if ($duplicateCustomer) {
                    DB::rollBack();
                    return response()->json(["errors" => ["Error" => ["Another customer already exists against given CNIC Number "]]], 422);
                }

                $customer->update([
                    'name' => $request->name,
                    'cnic' => $cleanCnic,
                    'contact' => $cleanPhone,
                    'updated_by' => Auth::user()->id,
                ]);
            }

            $assignCard->update([
                'rfId' => $request->rfId,
                'cnic' => $cleanCnic,
                'phone' => $cleanPhone,
                'name' => $request->name,
                'card_type_id' => $request->card_type_id,
                'expiry_date' => $request->expiry_date,
                'updated_by' => Auth::user()->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name . " | updated card assignation of customer (" . $assignCard->name . ")",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return $assignCard->fresh(['addedBy:id,name', 'updatedBy:id,name', 'customer', 'discountCardType:id,name']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function getCnic(Request $request)
    {
        if (!checkForSubmenu("discountCardAssign")) {
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
