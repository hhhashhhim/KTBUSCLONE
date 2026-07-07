<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Inventory\BidSummary;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\MaterialRequestDetail;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductUnit;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\PurchaseRequisitionNote;
use App\Models\Inventory\StoreIssuanceNoteDetail;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['unit', 'category'])
            ->withCount(['materialRequestDetails', 'prnDetails'])
            ->latest()->get()
            ->map(function ($product) {
                $product->is_deletable =
                    $product->material_request_details_count == 0 &&
                    $product->prn_details_count == 0;
                return $product;
            });

        $categories = ProductCategory::all();
        $units = ProductUnit::all();

        return response()->json([
            'products'   => $products,
            'categories' => $categories,
            'units'      => $units,
        ]);
    }

    // 👉 Create a new product
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'unit_id'     => 'required|integer|exists:product_units,id',
            'category_id' => 'required|integer|exists:product_categories,id',
            'qty'         => 'required',
            'avg_price'   => 'required',
        ]);


        $product = Product::create([
            'name'        => $request->name,
            'category_id' => $request->category_id,
            'unit_id'     => $request->unit_id,
            'qty'         => 0,
            'avg_price'   => 0,
            'added_by' => Auth::user()->id,
            'company_id'  => Auth::user()->company_id,

        ]);

        ActivityLogger::log('Inventory Product', 'create', 'Product created', $product->id, [], $product->toArray(), $request);

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $product->load(['unit', 'category']),
        ]);
    }

    // 👉 Update an existing product
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'required|exists:products,id',
            'name'        => 'required|string|max:255',
            'unit_id'     => 'required|exists:product_units,id',
            'category_id' => 'required|exists:product_categories,id',
        ]);

        $product = Product::findOrFail($validated['id']);
        $oldValues = $product->only(['name', 'unit_id', 'category_id']);

        // Update only necessary fields
        $product->name        = $validated['name'];
        $product->unit_id     = $validated['unit_id'];
        $product->category_id = $validated['category_id'];
        $product->save();

        ActivityLogger::log('Inventory Product', 'update', 'Product updated', $product->id, $oldValues, $product->only(['name', 'unit_id', 'category_id']), $request);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('unit', 'category')
        ]);
    }


    // 👉 Delete a product
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($validated['id']);
        $oldValues = $product->toArray();
        $product->delete();

        ActivityLogger::log('Inventory Product', 'delete', 'Product deleted', $product->id, $oldValues, [], $request);

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function allRequests(Request $request)
    {

        $mrs  = MaterialRequest::where('status', 1)->count();
        $pos  = PurchaseOrder::where('status', 1)->count();
        $prns = PurchaseRequisitionNote::where('status', 1)->count();
        $bids = BidSummary::where('status', 1)->count();
        return response()->json([
            'pos'    => $pos,
            'mrs'    => $mrs,
            'prns'   => $prns,
            'bids'   => $bids,
        ], 200);
    }

 public function issuanceHistory($id)
{
    // Fetch issuance details
    $details = StoreIssuanceNoteDetail::with([
        'storeIssuanceNote',
        'storeIssuanceNote.materialRequestDetail',
        'bus',
    ])
    ->where('product_id', $id)
    ->orderBy('id', 'DESC')
    ->get();


    return response()->json($details);
}

    
}
