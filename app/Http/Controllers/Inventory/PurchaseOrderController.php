<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Company;
use App\Models\Inventory\BidDetail;
use App\Models\Inventory\BidSummary;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\PurchaseOrderDetail;
use App\Models\Inventory\Supplier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TCPDF;

class PurchaseOrderController extends Controller
{
    //
    public function index()
    {
         // Only fetch POs with status == 1
          $pos = PurchaseOrder::with([
            'supplier',
            'mr.requestedByUser', 
            'prn',
          ])  
          ->latest()
          ->get();        
        $bids = BidSummary::with([
            'supplier',
            'prn.details.product',
            'prn.mr.requestedByUser',
        ])
        ->where('status', 1)
        ->latest()
        ->get()
        ->unique('prn_id')
        ->values(); // ensures reindexing     

        $suppliers = Supplier::all();
        $products  = Product::all();
    
        return response()->json([
            'success'   => true,
            'message'   => 'Data fetched successfully.',
            'bids'      => $bids,
            'suppliers' => $suppliers,
            'products'  => $products,
            'pos'       => $pos,
        ]);
    } 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bid_detail_ids'   => 'required|array|min:1',
            'bid_detail_ids.*' => 'exists:bid_details,id',
        ]);
    
        DB::beginTransaction();
        try {
            $details = BidDetail::with(['product', 'bid.supplier', 'bid.prn', 'bid.mr'])
                ->whereIn('id', $validated['bid_detail_ids'])
                ->get();
    
            if ($details->isEmpty()) {
                return response()->json(['message' => 'No valid bid details found.'], 404);
            }
    
            // Group by bid ID (create PO per bid)
            $groupedByBid = $details->groupBy(fn($d) => $d->bid->id);
            $createdPOs = [];
    
            foreach ($groupedByBid as $bidId => $bidDetails) {
                $firstDetail = $bidDetails->first();
                $bid = $firstDetail->bid;
                $mr  = $bid->mr;
                $prn = $bid->prn;
                $supplierId = $bid->supplier_id;
    
                if (!$bid || !$mr || !$prn) {
                    throw new \Exception("Missing bid, MR or PRN for bid ID $bidId");
                }
    
                // Charges from bid summary
                $deliveryCharges = $bid->delivery_charges;
                $totalTax        = $bid->tax;
                $taxAmount       = $bid->tax_amount;
                $totalDiscount   = $bid->discount;
    
                $subTotalSum = $bidDetails->sum(fn($d) => $d->qty * $d->rate);
                $poDetails = [];
                $poTotal = 0;
    
                foreach ($bidDetails as $detail) {
                    $qty        = $detail->qty;
                    $rate       = $detail->rate;
                    $subTotal   = $qty * $rate;
                    $proportion = $subTotal / ($subTotalSum ?: 1);
                    $delivery   = $deliveryCharges * $proportion;
                    $tax_amt    = $taxAmount * $proportion;
                    $discount   = $totalDiscount * $proportion;
                    $netAmount  = $subTotal + $tax_amt + $delivery - $discount;
                    $poTotal   += $netAmount;
                    $poDetails[] = [
                        'product_id' => $detail->product_id,
                        'qty'        => $qty,
                        'rate'       => $rate,
                        'sub_total'  => $subTotal,
                        'tax'        => $totalTax,
                        'tax_amount' => $tax_amt,
                        'delivery'   => $delivery,
                        'discount'   => $discount,
                        'net_amount' => $netAmount,
                        'company_id' => auth()->user()->company_id,
                        'po_id'      => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
    
                $po = PurchaseOrder::create([
                    'bid_id'      => $bid->id,
                    'mr_id'       => $mr->id,
                    'prn_id'      => $prn->id,
                    'supplier_id' => $supplierId,
                    'total'       => $poTotal,
                    'remaining'   => $poTotal,
                    'status'      => 1,
                    'added_by'    => auth()->id(),
                    'company_id'  => auth()->user()->company_id,
                ]);
    
                foreach ($poDetails as &$d) {
                    $d['po_id'] = $po->id;
                }
    
                PurchaseOrderDetail::insert($poDetails);
    
                $mr->update(['status' => 5]);
                $bid->update(['status' => 2]);
    
                $createdPOs[] = $po->id;
            }
    
            DB::commit();
            ActivityLogger::log('Inventory Purchase Order', 'create', 'Purchase orders created from bid details', implode(',', $createdPOs), [], [
                'po_ids' => $createdPOs,
                'bid_detail_ids' => $validated['bid_detail_ids'],
            ], $request);
    
            return response()->json([
                'message' => 'Purchase Orders created successfully.',
                'po_ids'  => $createdPOs,
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PO creation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create purchase orders.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function show(Request $request)
    {
        $po = PurchaseOrder::with(['supplier', 'poDetails.product', 'goodReceiveNotes.details.product'])->where('id', $request->po_id)->get();
        return response()->json([
            'success' => true,
            'pos' => $po
        ]);
    }
    public function getSingle(Request $request)
    {
        $po = PurchaseOrder::with('poDetails.product')
            ->where('id', $request->po_id)
            ->first(); 
    
        if (!$po) {
            return response()->json([
                'success' => false,
                'message' => 'PO not found.',
            ], 404);
        }
    
        // Format the product list for Vue
        $products = $po->poDetails->map(function ($item) {
            return [
                'product_id'            => $item->product_id,
                'product_name'          => $item->product->name ?? 'N/A',
                'qty'                   => $item->qty, // Receivable Quantity
                'already_received_qty'  => $item->store_received ?? 0, // Already Received
            ];
        });
    
        return response()->json([
            'success' => true,
            'po' => [
                'id'       => $po->id,
                'products' => $products
            ]
        ]);
    }
    public function poPDF(Request $request)
    { 
        $poId = $request->input('po_id');
        $po = PurchaseOrder::with(['supplier', 'poDetails.product', 'goodReceiveNotes.details.product'])->findOrFail($poId);
    
        // Setup TCPDF
        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
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
        $pdf->Cell(0, 10, 'Purchase Order Details', 0, 1, 'C');
    
        // Meta info
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Ln(15);
        $pdf->MultiCell(0, 6, "PO #: {$po->id}    |    BID #: {$po->bid_id}    |    PRN #: {$po->prn_id}    |    MR #: {$po->mr_id}", 0, 'L');
        $pdf->MultiCell(0, 6, "Date: " . date('d-M-Y', strtotime($po->created_at)), 0, 'L');
        $pdf->Ln(3);
    
        // Supplier Info
        $supplier = $po->supplier;
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Supplier Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $supplierTable = <<<EOD
            <table cellpadding="4" border="1" width="100%">
                <tr>
                    <td width="33%"><b>Name:</b> {$supplier->name}</td>
                    <td width="33%"><b>Contact:</b> {$supplier->contact}</td>
                    <td width="34%"><b>CNIC:</b> {$supplier->cnic}</td>
                </tr>
                <tr>
                    <td colspan="3"><b>Address:</b> {$supplier->address}</td>
                </tr>
            </table>
        EOD;
        $pdf->writeHTML($supplierTable, true, false, false, false, '');
    
        // PO Details Table with 50% width (about 100 mm on A4)
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PO Line Items', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
    
        // Calculate sums for Discount, Delivery, Tax
        $totalDiscount = 0;
        $totalDelivery = 0;
        $totalTax = 0;
        $subTotal = 0;
    
        $poTable = '<table border="1" cellpadding="4" width="100%" style="width:100%;">';
        $poTable .= '
            <thead>
                <tr style="background-color:#f0f0f0; font-weight: bold;" align="center">
                    <th>#</th>
                    <th >Product</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Subtotal</th>
                </tr>
            </thead><tbody>';
    
        foreach ($po->poDetails as $i => $item) {
            $totalDiscount += $item->discount;
            $totalDelivery += $item->delivery;
            $totalTax += $item->tax_amount;
            $subTotal += $item->sub_total;
    
            $poTable .= "<tr align='center'>
                <td>" . ($i + 1) . "</td>
                <td style='text-align:left;'>{$item->product->name}</td>
                <td>{$item->qty}</td>
                <td>" . number_format($item->rate, 2) . "</td>
                <td>" . number_format($item->sub_total, 2) . "</td>
            </tr>";
        }
    
        $poTable .= '</tbody></table>';
        $pdf->writeHTML($poTable, true, false, false, false, '');
    
        // Display sums separately in a new table below with 50% width
        $pdf->Ln(-5);
        $pdf->SetFont('helvetica', 'B', 10);
 

        $summaryTable = '
        <table cellpadding="4" cellspacing="0" border="0" width="101%">
            <tr>
                <td width="50%"></td>
                <td width="50%">
                    <table border="1" cellpadding="4" width="100%">
                        <tr style="background-color:#d9edf7;">
                            <td align="right"><b>Sub Total:</b></td>
                            <td align="right">Rs. ' . number_format($subTotal, 2) . '</td>
                        </tr>
                        <tr style="background-color:#d9edf7;">
                            <td align="right"><b>Tax Amount:</b></td>
                            <td align="right">Rs. ' . number_format($totalTax, 2) . '</td>
                        </tr>
                        <tr style="background-color:#d9edf7;">
                            <td align="right"><b>Delivery Charges:</b></td>
                            <td align="right">Rs. ' . number_format($totalDelivery, 2) . '</td>
                        </tr>
                        <tr style="background-color:#d9edf7;">
                            <td align="right"><b>Discount:</b></td>
                            <td align="right">Rs. ' . number_format($totalDiscount, 2) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';
        $pdf->writeHTML($summaryTable, true, false, false, false, '');
        // PO Grand Total full width below sums (if you want it)
        $pdf->Ln(-5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PO Grand Total: Rs. ' . number_format($po->total, 2), 0, 1, 'R');
    
        // Watermark
        $pdf->SetAlpha(0.1);
        $pdf->StartTransform();
        $pdf->Rotate(45, 105, 148);
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->Text(20, 150, 'Kainat Travels');
        $pdf->StopTransform();
        $pdf->SetAlpha(1);
    
        return $pdf->Output("PO_{$po->id}.pdf", 'I');
    }
    

    }
    require_once(public_path().'/assets/tcpdf/tcpdf.php');
    class MYPDF extends TCPDF
    {
        public function Header()
        {
        }
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
