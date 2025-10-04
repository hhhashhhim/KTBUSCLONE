<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Inventory\GoodReceiveNote;
use App\Models\Inventory\GoodReceiveNoteDetail;
use App\Models\Inventory\Product;
use App\Models\Inventory\PurchaseOrderDetail;
use App\Models\Inventory\StoreIssuanceNote;
use App\Models\Inventory\StoreIssuanceNoteDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    //
    public function filter_received(Request $request)
    {
        $query = GoodReceiveNote::with([
            'supplier',
            'purchaseOrder',
            // Filter details to load only the selected product
            'details' => function ($q) use ($request) {
                if ($request->filled('product_id')) {
                    $q->where('product_id', $request->product_id);
                }
            },
            // Still need product relation inside each detail
            'details.product'
        ]);
    
        // Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }
    
        // Filter by GRN number
        if ($request->filled('grn')) {
            $query->where('id', $request->grn);
        }
    
        // Filter by PO number
        if ($request->filled('po')) {
            $query->where('po_id', $request->po);
        }
    
        // Ensure only GRNs that actually have the selected product in their details
        if ($request->filled('product_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }
    
        $inwards = $query->latest()->get();
    
        return response()->json(['inward' => $inwards]);
    }

  public function filter_issued(Request $request)
{
    $query = StoreIssuanceNote::query();

    // ✅ Base relations, conditionally filter details
    $query->with(['details' => function ($q) use ($request) {
        $q->with(['product', 'bus']);

        if ($request->filled('product_id')) {
            $q->where('product_id', $request->product_id);
        }

        if ($request->filled('bus_id')) {
            $q->where('bus_id', $request->bus_id);
        }
    }]);

    // ✅ Date range filter
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('created_at', [
            $request->from_date . ' 00:00:00',
            $request->to_date . ' 23:59:59'
        ]);
    }

    // ✅ MR and SIN filters
    if ($request->filled('mr')) {
        $query->where('mr_id', $request->mr);
    }

    if ($request->filled('sin')) {
        $query->where('id', $request->sin);
    }

    // ✅ Parent-level filters (only include notes having such details)
    if ($request->filled('product_id')) {
        $query->whereHas('details', function ($q) use ($request) {
            $q->where('product_id', $request->product_id);
        });
    }

    if ($request->filled('bus_id')) {
        $query->whereHas('details', function ($q) use ($request) {
            $q->where('bus_id', $request->bus_id);
        });
    }

    // ✅ Final data
    $outwards = $query->latest()->get();

    return response()->json([
        'outward' => $outwards
    ]);
}

     
   public function product_control(Request $request)
    {
        $fromDate = $request->filled('from_date') 
            ? Carbon::parse($request->from_date)->startOfDay() 
            : Carbon::today()->startOfDay();

        $toDate = $request->filled('to_date') 
            ? Carbon::parse($request->to_date)->endOfDay() 
            : Carbon::today()->endOfDay();

        $products = Product::all();

        $data = $products->map(function ($product) use ($fromDate, $toDate) {
            // Opening purchases and issuances before fromDate
            $openingPurchases = GoodReceiveNoteDetail::where('product_id', $product->id)
                ->where('created_at', '<', $fromDate)
                ->get();

            $openingIssuances = StoreIssuanceNoteDetail::where('product_id', $product->id)
                ->where('created_at', '<', $fromDate)
                ->get();

            $opening_issuance_qty = $openingIssuances->sum('qty');

            // Safe accumulation of opening purchases
            $opening_purchase_qty = 0;
            $opening_purchase_value = 0;
            foreach ($openingPurchases as $record) {
                $qty = $record->qty;
                $netTotal = $record->net_amount ?? 0;
                $opening_purchase_qty += $qty;
                $opening_purchase_value += $netTotal;
            }

            // Avoid division by zero
            $avg_opening_rate = $opening_purchase_qty > 0
                ? $opening_purchase_value / $opening_purchase_qty
                : 0;

            $opening_qty = $opening_purchase_qty - $opening_issuance_qty;
            $opening_value = $opening_qty * $avg_opening_rate;

            // Purchases during selected date range
            $currentPurchases = GoodReceiveNoteDetail::where('product_id', $product->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            $purchase_qty = 0;
            $purchase_value = 0;
            foreach ($currentPurchases as $record) {
                $qty = $record->qty;
                $netTotal = $record->net_amount ?? 0;
                $purchase_qty += $qty;
                $purchase_value += $netTotal;
            } 
            $avg_purchase_price = $purchase_qty > 0 ? intval($purchase_value / $purchase_qty) : 0;
            // Issuances during selected date range
            $currentIssuances = StoreIssuanceNoteDetail::where('product_id', $product->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            $issuance_qty = $currentIssuances->sum('qty');
            $issuance_value = 0;
            foreach ($currentIssuances as $r) {
                $issuance_value += $r->qty * ($r->rate ?? 0);
            }

            // Final balances
            $balance_qty = $opening_qty + $purchase_qty - $issuance_qty;
            $balance_value = $opening_value + $purchase_value - $issuance_value;
            $avg_balance_price = $balance_qty > 0 ? $balance_value / $balance_qty : 0;

            return [
                'name' => $product->name,

                'opening_qty' => $opening_qty,
                'avg_opening_price' => round($avg_opening_rate, 0),
                'opening_value' => round($opening_value, 0),

                'purchase_qty' => $purchase_qty,
                'avg_purchase_price' => round($avg_purchase_price, 0),
                'purchase_value' => round($purchase_value, 0),

                'issuance_qty' => $issuance_qty,
                'avg_issuance_price' => $issuance_qty > 0 ? round($issuance_value / $issuance_qty, 0) : 0,
                'issuance_value' => round($issuance_value, 0),

                'balance_qty' => $balance_qty,
                'avg_balance_price' => round($avg_balance_price, 0),
                'balance_value' => round($balance_value, 0),
            ];
        });

        return response()->json(['data' => $data]);
    }

}
