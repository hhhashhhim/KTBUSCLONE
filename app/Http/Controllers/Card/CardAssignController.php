<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CardAssignController extends Controller
{
    public function index(Request $request)
    {
        if(!checkForSubmenu("loyaltyCardAssign"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        return CardAssign::with('addedBy:id,name', 'updatedBy:id,name', 'customer', 'cardCategory:id,name')
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
            ->when($request->filled('card_category_id') && $request->card_category_id != 0, function ($query) use ($request) {
                $query->where('card_category_id', $request->card_category_id);
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
                $assignCard = CardAssign::where(['id' => $request->id, 'company_id' => Auth::user()->company_id])->first();
                if (!$assignCard) {
                    DB::rollBack();
                    return response()->json(["errors" => ["Error" => ['Assigned loyalty card not found.']]], 404);
                }

                $cleanCnic = plainContactAndCnic($request->cnic);
                $cleanPhone = plainContactAndCnic($request->phone);

                $duplicateCard = CardAssign::where('company_id', Auth::user()->company_id)
                    ->where('cnic', $cleanCnic)
                    ->where('id', '!=', $assignCard->id)
                    ->first();

                if ($duplicateCard) {
                    DB::rollBack();
                    return response()->json(["errors" => ["Error" => ["Loyalty Card Already Against Given CNIC Number "]]], 422);
                }

                $customer = Customer::where([
                    'id' => $assignCard->customer_id,
                    'company_id' => Auth::user()->company_id,
                ])->first();

                if (!$customer) {
                    $customer = Customer::where('company_id', Auth::user()->company_id)
                        ->where('cnic', $cleanCnic)
                        ->first();
                }

                if ($customer && (string) plainContactAndCnic($customer->cnic) !== (string) $cleanCnic) {
                    $duplicateCustomer = Customer::where('company_id', Auth::user()->company_id)
                        ->where('cnic', $cleanCnic)
                        ->where('id', '!=', $customer->id)
                        ->first();

                    if ($duplicateCustomer) {
                        DB::rollBack();
                        return response()->json(["errors" => ["Error" => ["Another customer already exists against given CNIC Number "]]], 422);
                    }
                }

                if ($customer) {
                    $customer->update([
                        'name' => $request->name,
                        'cnic' => $cleanCnic,
                        'contact' => $cleanPhone,
                        'updated_by' => Auth::user()->id,
                    ]);
                } else {
                    $customer = Customer::create([
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                        'name' => $request->name,
                        'cnic' => $cleanCnic,
                        'contact' => $cleanPhone,
                    ]);
                }

                $assignCard->update([
                    'rfId' => $request->rfId,
                    'cnic' => $cleanCnic,
                    'phone' => $cleanPhone,
                    'name' => $request->name,
                    'card_category_id' => $request->card_category_id,
                    'customer_id' => $customer->id,
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
                return $assignCard->fresh(['addedBy:id,name', 'updatedBy:id,name', 'customer', 'cardCategory:id,name']);
            
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

    public function discountHistory($customerId)
    {
        if(!checkForSubmenu("loyaltyCardAssign"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        return response()->json(['history' => $this->getCardHistory($customerId)]);
    }

    public function cardHistory($customerId)
    {
        return $this->discountHistory($customerId);
    }

    public function cardHistoryPdf(Request $request)
    {
        $request->validate([
            'customer_id' => ['required'],
        ]);

        return view('reports.cardHistoryReport', [
            'customer' => Customer::where('company_id', Auth::user()->company_id)
                ->where('id', $request->customer_id)
                ->first(),
            'history' => $this->getCardHistory($request->customer_id),
        ]);
    }

    private function getCardHistory($customerId)
    {
        return Ticket::with([
            'customer:id,name,cnic,contact',
            'route:id,name',
            'departure_city:id,name',
            'destination_city:id,name',
            'terminal:id,name',
            'addedBy:id,name',
        ])
            ->where('company_id', Auth::user()->company_id)
            ->whereIn('customer_id', $this->matchingCustomerIds($customerId))
            ->where('discount_type', 'card')
            ->orderByDesc('id')
            ->get()
            ->map(function ($ticket) {
                $discount = (float) ($ticket->discount ?? 0);
                $terminalDiscount = (float) ($ticket->terminal_discount ?? 0);
                $scheduleDiscount = (float) ($ticket->schedule_discount ?? 0);

                return [
                    'id' => $ticket->id,
                    'customer_id' => $ticket->customer_id,
                    'customer_name' => $ticket->customer?->name,
                    'customer_cnic' => $ticket->customer?->cnic,
                    'customer_contact' => $ticket->customer?->contact,
                    'booking_no' => $ticket->booking_no,
                    'invoice_id' => $ticket->invoice_id,
                    'transaction_id' => $ticket->transaction_id,
                    'schedule_date' => $ticket->schedule_date,
                    'schedule_time' => $ticket->schedule_time,
                    'seat_no' => $ticket->seat_no,
                    'seat_fare' => $ticket->seat_fare,
                    'discount' => $discount,
                    'terminal_discount' => $terminalDiscount,
                    'schedule_discount' => $scheduleDiscount,
                    'total_discount' => $discount + $terminalDiscount + $scheduleDiscount,
                    'route_name' => $ticket->route?->name,
                    'departure_city_name' => $ticket->departure_city?->name,
                    'destination_city_name' => $ticket->destination_city?->name,
                    'terminal_name' => $ticket->terminal?->name ?? $ticket->terminal_name,
                    'booked_time' => $ticket->booked_time,
                    'added_by_name' => $ticket->addedBy?->name,
                ];
            });
    }

    private function matchingCustomerIds($customerId)
    {
        $customer = Customer::where('company_id', Auth::user()->company_id)
            ->where('id', $customerId)
            ->first();

        if (!$customer) {
            return collect([$customerId]);
        }

        $cnic = $customer->cnic;
        $contact = $customer->contact;

        return Customer::where('company_id', Auth::user()->company_id)
            ->where(function ($query) use ($customerId, $cnic, $contact) {
                $query->where('id', $customerId);

                if (!empty($cnic) && $cnic != 0) {
                    $query->orWhere('cnic', $cnic);
                }

                if (!empty($contact) && $contact != 0) {
                    $query->orWhere('contact', $contact);
                }
            })
            ->pluck('id');
    }
}
