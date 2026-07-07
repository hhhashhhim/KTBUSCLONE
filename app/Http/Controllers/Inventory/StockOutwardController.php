<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Account\AccountHead;
use App\Models\Account\AccountTransaction;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\MaterialRequestDetail;
use App\Models\Inventory\Product;
use App\Models\Inventory\StoreIssuanceNote;
use App\Models\Inventory\StoreIssuanceNoteDetail;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCPDF;

class StockOutwardController extends Controller
{
    //
    public function index()
    {
        // Build a map of total issued quantity for each MR-product pair
        $issuedQtyMap = StoreIssuanceNoteDetail::with('storeIssuanceNote')
            ->get()
            ->groupBy(function ($detail) {
                return $detail->storeIssuanceNote->mr_id . '-' . $detail->product_id . '-' . $detail->bus_id;;
            })
            ->map(function ($group) {
                return $group->sum('qty');
            });

        // Fetch MRs where at least one detail has qty != store_issued_qty
        $mrs = MaterialRequest::with(['details.product', 'details.bus', 'requestedByUser'])
            ->whereHas('details', function ($query) {
                $query->whereColumn('qty', '!=', 'store_issued_qty');
            })
            ->latest()
            ->get();

        // Inject issued_qty into each MR detail
        foreach ($mrs as $mr) {
            foreach ($mr->details as $detail) {
                $key = $mr->id . '-' . $detail->product_id . '-' . $detail->bus_id;
                $detail->issued_qty = $issuedQtyMap[$key] ?? 0;
            }
        }

        // Fetch all Store Issuance Notes with related product details
        $outwards = StoreIssuanceNote::with(['details.product', 'details.bus'])->latest()->get();

        return response()->json([
            'success'  => true,
            'message'  => 'Material Requests and Store Issuance Notes fetched successfully.',
            'mrs'      => $mrs,
            'outwards' => $outwards
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'mr_id'        => 'required|exists:material_requests,id',
            'requested_by' => 'required|string',
            'details'      => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.bus_id' => 'required|exists:buses,id',
            'details.*.qty'   => 'required|numeric|min:1',
            'details.*.rate'  => 'required|numeric',
            'details.*.total' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();
            // Create new issuance
            $storeIssuance = StoreIssuanceNote::create([
                'mr_id'        => $request->mr_id,
                'requested_by' => $request->requested_by,
                'status'       => 1, // Partial
                'added_by'     => auth()->id(),
                'company_id'   => Auth::user()->company_id,
            ]);
            $issuedNow = 0;

            $document = AccountTransaction::where(["company_id" => Auth::user()->company_id])
                ->where("type", "JV")
                ->orderBy("document_id", "DESC")
                ->first();
            $document_id = $document ? $document->document_id + 1 : 1;
            $storeHead = $this->accountHeadCreate(
                'STORE EXPENSE | EXPENSE LEDGER',
                5, // EXPENSES
                15, // OPERATING EXPENSES
                48, // OTHER EXPENSES
                60, // GENERAL EXPENSE
            );
            $total_net_amount = 0;
            $sub_total = 0;
            foreach ($request->details as $detail) {
                $product = Product::findOrFail($detail['product_id']);
                // Check stock
                if ($product->qty < $detail['qty']) {
                    throw new \Exception("Insufficient stock for product ID: {$product->id}");
                }
                // Reduce stock
                $product->decrement('qty', $detail['qty']);
                // Save issuance detail
                $sub_total = $detail['qty'] * $product->avg_price;
                StoreIssuanceNoteDetail::create([
                    'store_issuance_note_id' => $storeIssuance->id,
                    'product_id' => $detail['product_id'],
                    'qty'        => $detail['qty'],
                    'bus_id'     => $detail['bus_id'],
                    'rate'       => $product->avg_price,
                    'total'      => $sub_total,
                    'company_id' => Auth::user()->company_id,
                ]);
                // Update issued qty in MR details
                MaterialRequestDetail::where('mr_id', $request->mr_id)
                    ->where('product_id', $detail['product_id'])
                    ->where('bus_id', $detail['bus_id'])
                    ->increment('store_issued_qty', $detail['qty']);
                $issuedNow += $detail['qty'];
                if ($product->product_head_id) {
                    $productHead = AccountHead::findOrFail($product->product_head_id);
                } else {
                    // Create new account head
                    $productHead = $this->accountHeadCreate(
                        $product->name . '-|PRODUCT LEDGER',
                        1,  // ASSETS
                        6,  // CURRENT ASSETS
                        15, // STORE AND SPARES
                        18  // GENERAL PARTS
                    );
                    // Save the new account head ID to the product
                    $product->product_head_id = $productHead->id;
                    $product->save();
                }
                $total_net_amount += $sub_total;
                $this->updateSaleTransaction(
                    $productHead, // head
                    $storeHead->id, //other head id
                    $sub_total, //credit
                    0, //debit
                    $document_id, //document id
                    "Generated Issuance of " . $product->name . " Issued QTY is @" . $detail['qty'] . " Avg Rate of this Issuance is @" . $product->avg_price,
                    $storeIssuance->id //posting id
                );
            }
            $this->updateSaleTransaction(
                $storeHead, // head
                $productHead->id, //other head id
                0, //credit
                $total_net_amount, //debit
                $document_id, //document id
                "Generated Issuance of " . $product->name . " Issued QTY is @" . $detail['qty'] . " Avg Rate of this Issuance is @" . $product->avg_price,
                $storeIssuance->id //posting id
            );
            // Load MR and all its details
            $mr = MaterialRequest::with('details')->findOrFail($request->mr_id);
            // Calculate total requested and issued
            $requestedTotal = $mr->details->sum('qty');
            $issuedTotal    = $mr->details->sum('store_issued_qty');

            // Update status
            if ($issuedTotal >= $requestedTotal) {
                $mr->status = 2; // Completed
                $storeIssuance->status = 2; // Completed
            } else {
                $mr->status = 7; // Partial
                $storeIssuance->status = 1; // Partial
            }

            $mr->save();
            $storeIssuance->save();

            DB::commit();

            ActivityLogger::log('Inventory Stock Out', 'create', 'Store issuance note created', $storeIssuance->id, [], [
                'mr_id' => $request->mr_id,
                'total_net_amount' => $total_net_amount,
                'status' => $storeIssuance->status,
            ], $request);

            return response()->json([
                'success' => true,
                'message' => 'Store Issuance Note created successfully',
                'data'    => $storeIssuance->load('details')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create issuance note',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    function accountHeadCreate($name, $first, $second, $third, $fourth)
    {
        $existHead = AccountHead::where(["name" => $name, "parent_account_id" => $first, "account_id" => $second, "parent_group_id" => $third, "group_id" => $fourth])->first();
        if ($existHead) {
            return $existHead;
        }
        $code = AccountHead::latest('id')->where('group_id', $fourth)->limit(1)->value('code') + 1;
        $code = str_pad($code, 4, '0', STR_PAD_LEFT);
        $head = AccountHead::create([
            'name' => strtoupper($name),
            'code' => $code,
            'parent_account_id' => $first,
            'account_id' => $second,
            'parent_group_id' => $third,
            'group_id' => $fourth,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);

        return $head;
    }
    function updateSaleTransaction($head, $other_id, $credit, $debit, $document_id, $narration, $posting_id)
    {
        AccountTransaction::create([
            'terminal_id' => 1,
            'account_head_id' => $head->id,
            'other_account_head_id' => $other_id,
            'credit' => $credit,
            'debit' => $debit,
            'document_id' => $document_id,
            'type' => "JV",
            'narration' => strtoupper($narration),
            'posting_type' => 'SIN',
            'posting_id' => $posting_id,
            'approved' => 1,
            'approved_by' => 0,
            'parent_account_id' => $head->parent_account_id,
            'account_id' => $head->account_id,
            'parent_group_id' => $head->parent_group_id,
            'group_id' => $head->group_id,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);
    }

    public function sinPDF(Request $request)
    {
        // Load outward note with product details
        $outward = StoreIssuanceNote::with(['details.product'])->findOrFail($request->outward_id);

        // Get the related MR with user and products
        $mr = MaterialRequest::with(['details.product', 'requestedByUser'])
            ->find($outward->mr_id);

        // Use TCPDF directly
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();

        // Logo
        $logoPath = public_path('assets/img/kt-logo.jpg');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 25);
        }

        // Title
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Stock Outward Details', 0, 1, 'C');
        $pdf->Ln(10);

        // Meta Info
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, "Invoice #: {$outward->id}", 0, 'L');
        $pdf->MultiCell(0, 6, "Date: " . date('d-M-Y h:i A', strtotime($outward->created_at)), 0, 'L');
        $pdf->MultiCell(0, 6, "Requested By: " . ($mr?->requestedByUser?->name ?? '-'), 0, 'L');

        // Table Title
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, "Product Details", 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        // HTML Table
        $outwardTable = <<<EOD
            <table border="1" cellpadding="4">
                <thead>
                    <tr style="background-color:#f9f9f9;">
                        <th>#</th>
                        <th>Bus Number</th>
                        <th>Product</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
        EOD;

        foreach ($outward->details as $i => $detail) {
            $productName = $detail->product->name ?? 'N/A';
            $busNumber = $detail->bus->bus_number ?? 'N/A';
            $qty = $detail->qty ?? 0;
            $outwardTable .= "<tr align='center'>
                <td>" . ($i + 1) . "</td>
                <td>{$busNumber}</td>
                <td>{$productName}</td>
                <td>{$qty}</td>
            </tr>";
        }

        $outwardTable .= <<<EOD
                </tbody>
            </table>
        EOD;

        $pdf->writeHTML($outwardTable, true, false, false, false, '');

        // Watermark
        $pdf->SetAlpha(0.1);
        $pdf->StartTransform();
        $pdf->Rotate(45, 105, 148);
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->Text(20, 150, 'Kainat Travels');
        $pdf->StopTransform();
        $pdf->SetAlpha(1);

        // Output the PDF
        return $pdf->Output("Outward_Invoice_{$outward->id}.pdf", 'I');
    }
}
require_once(public_path() . '/assets/tcpdf/tcpdf.php');
class MYPDF extends TCPDF
{
    public function Header() {}
    public function Footer()
    {
        $this->SetY(-12); // Distance from bottom
        $this->SetFont('helvetica', 'UB', 10);
        $printDate = date('d-m-Y h:i A');
        $printedBy = auth()->check() ? auth()->user()->name : 'System';
        $footerText = "Printed by: $printedBy | Printed on: $printDate | Developed by SARZONE";
        $this->Cell(0, 10, $footerText, 0, false, 'C');
    }
}
