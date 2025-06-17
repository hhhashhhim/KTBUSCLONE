<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\BidDetail;
use App\Models\Inventory\BidSummary;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\PurchaseRequisitionNote;
use App\Models\Inventory\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TCPDF;

class BidSummariesController extends Controller
{
    //
    public function index(){
        $prns = PurchaseRequisitionNote::with([
            'mr.requestedByUser',
            'details.product'
        ])->where('status', 1)->latest()->get();
    
        $bids = BidSummary::with([
            'supplier',
            'prn.details.product',
            'prn.mr.requestedByUser',
        ])
        ->latest()
        ->get()
        ->unique('prn_id')
        ->values(); // ensures reindexing        

        $suppliers = Supplier::all();
        $products  = Product::all();
    
        return response()->json([
            'success'   => true,
            'message'   => 'MRs, PRNs, Bids, Suppliers, and Products fetched successfully.',
            'prns'      => $prns,
            'bids'      => $bids,
            'suppliers' => $suppliers,
            'products'  => $products,
        ]);
    }
   public function store(Request $request){ 
    DB::beginTransaction();
        try {
            foreach ($request->suppliers ?? [] as $supplier) {
                $products = $supplier['products'] ?? [];
                // Calculate total (subtotal) from all products
                $productTotals = collect($products)->map(function ($product) {
                    $rate = $product['rate'] ?? 0;
                    $qty  = $product['quantity'] ?? 0;
                    return $rate * $qty;
                });
                $subTotal = $productTotals->sum();
                // Supplier-level totals
                $totalTax      = (float) ($supplier['tax'] ?? 0);
                $totalTaxAmount     = (float) ($supplier['tax_amount'] ?? 0);
                $totalDiscount = (float) ($supplier['discount'] ?? 0);
                $totalDelivery = (float) ($supplier['delivery_charges'] ?? 0);
                $grandTotal    = $subTotal + $totalTaxAmount + $totalDelivery - $totalDiscount;
                $advancePercent       = $supplier['advance_percent'] ?? 0;
                $afterDeliveryPercent = $supplier['after_delivery_percent'] ?? 0;
                $advanceAmount        = ($advancePercent / 100) * $grandTotal;
                $afterDeliveryAmount  = ($afterDeliveryPercent / 100) * $grandTotal;
                // Create Bid Summary
                $summary = BidSummary::create([
                    'prn_id'                  => $request->prn_id,
                    'mr_id'                   => $request->mr_id,
                    'supplier_id'             => $supplier['supplier_id'],
                    'total'                   => $subTotal,
                    'total_amount'            => $grandTotal,
                    'tax'                     => $totalTax,
                    'tax_amount'              => $totalTaxAmount,
                    'advance'                 => $advanceAmount,
                    'advance_amount'          => $advancePercent,
                    'after_delivery'          => $afterDeliveryPercent,
                    'after_delivery_amount'   => $afterDeliveryAmount,
                    'credit_days'             => $supplier['credit_days']??0,
                    'discount'                => $totalDiscount,
                    'delivery_charges'        => $totalDelivery,
                    'contact_person'          => $supplier['contact_person'],
                    'contact_person_contact'  => $supplier['contact_person_contact'],
                    'terms_condition'         => $supplier['terms_condition'],
                    'quotation_ref'           => $supplier['quotation_ref'],
                    'quotation_date'          => $supplier['quotation_date'] ?? now(),
                    'status'                  => 1,
                    'company_id'              => Auth::user()->company_id,
                    'added_by'                => auth()->id(),
                ]);
                // Now distribute discount, tax, and delivery proportionally per product
                foreach ($products as $product) {
                    $rate  = (float) ($product['rate'] ?? 0);
                    $qty   = (float) ($product['quantity'] ?? 0);
                    $total = $rate * $qty;
                    $ratio = $subTotal > 0 ? $total / $subTotal : 0;
                    $discountShare = $ratio * $totalDiscount;
                    $taxShare      = $ratio * $totalTax;
                    $taxAmount      = $ratio * $totalTaxAmount;
                    $deliveryShare = $ratio * $totalDelivery;
                    $netAmount     = $total + $taxShare + $deliveryShare - $discountShare;
                    BidDetail::create([
                        'bid_id'           => $summary->id,
                        'product_id'       => $product['product_id'],
                        'qty'              => $qty,
                        'rate'             => $rate,
                        'total'            => $total,
                        'discount'         => round($discountShare, 2),
                        'delivery_charges' => round($deliveryShare, 2),
                        'tax'              => round($taxShare, 2),
                        'tax_amount'       => round($taxAmount, 2),
                        'net_amount'       => round($netAmount, 2),
                         'company_id'      => Auth::user()->company_id,

                    ]);
                }
            }
            if ($request->prn_id) {
                $mr = MaterialRequest::findOrFail($request->mr_id);
                $mr->status = 4;
                $mr->save();
                PurchaseRequisitionNote::find($request->prn_id)?->update(['status' => 2]);
            }
            DB::commit();
            return response()->json(['message' => 'Bids submitted successfully.']);
         } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Submission failed.',
                    'details' => $e->getMessage(),
                ], 500);
          }
    }
    // Compare bids function
    public function compareBid(Request $request)
    {
        $prnId = $request->prn_id;

        $bids = BidSummary::with([
            'supplier',
            'prn.mr.requestedByUser',
            'details.product',
            'details.supplier'
        ])
        ->where('prn_id', $prnId)
        ->get();

        return response()->json([
            'success' => true,
            'bids'    => $bids
        ]);
    }
    // Show bids for PRN
    public function show(Request $request)
    {
        $prnId = $request->prn_id;
        $bids = BidSummary::with([
            'supplier',
            'prn.mr.requestedByUser',
            'details.product',
            'details.supplier'
        ])
        ->where('prn_id', $prnId)
        ->get();

        $grouped = [];
        foreach ($bids as $bid) {
            $grouped[] = $bid;
        }

        return response()->json([
            'success'     => true,
            'bids_by_prn' => $grouped
        ]);
    }
    // Update Bid
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'             => 'required',
            'supplier_id'    => 'required|exists:suppliers,id',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:255',
            'quotation_ref'   => 'nullable|string|max:255',
            'quotation_date'  => 'nullable|date',
            'credit_days'     => 'nullable|integer',
            'advance_percent' => 'nullable|numeric',
            'after_delivery_percent' => 'nullable|numeric',
            'terms_condition' => 'nullable|string',
            'discount'        => 'nullable|numeric',
            'tax'             => 'nullable|numeric',
            'delivery_charges' => 'nullable|numeric',
            'details'         => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.qty'   => 'required|numeric|min:0',
            'details.*.rate'  => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
             $summary = BidSummary::findOrFail($validated['id']); 
            $products = $validated['details'];
            // Calculate subtotal
            $subTotal = collect($products)->reduce(function ($carry, $product) {
                return $carry + ($product['rate'] * $product['qty']);
            }, 0);
            $totalTax      = (float) ($validated['tax'] ?? 0);
            $totalTaxAmount      = (float) ($validated['tax_amount'] ?? 0);
            $totalTaxAmount= (float) ($validated['tax_amount'] ?? 0);
            $totalDiscount = (float) ($validated['discount'] ?? 0);
            $totalDelivery = (float) ($validated['delivery_charges'] ?? 0);
            $grandTotal    = $subTotal + $totalTaxAmount + $totalDelivery - $totalDiscount;

            $advancePercent = $validated['advance_percent'] ?? 0;
            $afterDeliveryPercent = $validated['after_delivery_percent'] ?? 0;
            $advanceAmount = ($advancePercent / 100) * $grandTotal;
            $afterDeliveryAmount = ($afterDeliveryPercent / 100) * $grandTotal;

            // Update Bid Summary
            $summary->update([  
                'total' => $subTotal,
                'total_amount' => $grandTotal,
                'tax' => $totalTax,
                'tax_amount' => $totalTaxAmount,
                'advance' => $advancePercent,
                'advance_amount' => $advanceAmount,
                'after_delivery' => $afterDeliveryPercent,
                'after_delivery_amount' => $afterDeliveryAmount,
                'credit_days' => $validated['credit_days'],
                'discount' => $totalDiscount,
                'delivery_charges' => $totalDelivery,
                'contact_person' => $validated['contact_person'],
                'contact_person_contact' => $validated['contact_person_contact'],
                'terms_condition' => $validated['terms_condition'],
                'quotation_ref' => $validated['quotation_ref'],
                'quotation_date' => $validated['quotation_date'] ?? now(),
            ]);

            // Delete old BidDetails
            BidDetail::where('bid_id', $summary->id)->delete();

            // Insert new BidDetails
            foreach ($products as $product) {
                $rate = $product['rate'];
                $qty = $product['qty'];
                $total = $rate * $qty;
                $ratio = $subTotal > 0 ? $total / $subTotal : 0;

                $discountShare = $ratio * $totalDiscount;
                $taxShare = $ratio * $totalTax;
                $deliveryShare = $ratio * $totalDelivery;
                $netAmount = $total + $taxShare + $deliveryShare - $discountShare;

                BidDetail::create([
                    'bid_id' => $summary->id,
                    'product_id' => $product['product_id'],
                    'qty' => $qty,
                    'rate' => $rate,
                    'total' => $total,
                    'discount' => round($discountShare, 2),
                    'delivery_charges' => round($deliveryShare, 2),
                    'tax' => round($taxShare, 2),
                    'tax' => round($taxShare, 2),
                    'net_amount' => round($netAmount, 2),
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Bid updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Update failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bid_summaries,id',
        ]);
        DB::beginTransaction();
        try {
            $bid = BidSummary::findOrFail($request->id);
            $prnId = $bid->prn_id;

            // Also delete related bid details if necessary
            $bid->details()->delete();
            $bid->delete();
            DB::commit();
            return response()->json([
                'message' => 'Bid deleted successfully.',
                'prn_id' => $prnId,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete bid.', 'error' => $e->getMessage()], 500);
        }
    }
    public function bidPDF(Request $request)
    {
        $prnId = $request->prn_id;
        $bids = BidSummary::with([
            'supplier',
            'prn.mr.requestedByUser',
            'details.product',
            'details.supplier'
        ])
        ->where('prn_id', $prnId)
        ->get();
    
        $prn = PurchaseRequisitionNote::with(['mr.requestedByUser', 'details.product'])->findOrFail($prnId);
        $mr = $prn->mr;
        $details = $prn->details;
        $company = Company::find($prn->company_id);
        $requestedBy = $mr->requestedByUser->name ?? 'N/A';
        $mrDate = date('d-M-Y', strtotime($mr->created_at));
    
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();
    
        $logoPath = public_path('assets/img/kt-logo.jpg');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 25);
        }
    
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Bid Summary Report', 0, 1, 'C');
    
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, 'Project: ' . ($company->name ?? 'Kainat Travels'), 0, 1);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(3);
    
        // MR Info
        $tbl = <<<EOD
        <table cellpadding="4" border="1">
            <tr>
                <td><b>PRN#</b></td><td>PRN-{$prn->id}</td>
                <td><b>MR#</b></td><td>MR-{$mr->id}</td>
                <td><b>Date</b></td><td>{$mrDate}</td>
            </tr>
            <tr>
                <td><b>Requested By</b></td><td colspan="5">{$requestedBy}</td>
            </tr>
        </table>
        EOD;
        $pdf->writeHTML($tbl, true, false, false, false, '');
    
        // PRN Product Details Table
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PRN Product Details', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
    
        $table = <<<EOD
        <table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f2f2f2; font-weight:bold;" align="center">
                    <th>Sr No.</th>
                    <th>Product Name</th>
                    <th>PRN Qty</th>
                    <th>Available Stock</th>
                </tr>
            </thead>
            <tbody>
        EOD;
    
        foreach ($details as $i => $item) {
            $sr = $i + 1;
            $name = $item->product->name ?? 'N/A';
            $qty = $item->qty ?? 0;
            $available = $item->product->qty ?? 0;
    
            $table .= <<<EOD
                <tr align="center">
                    <td>{$sr}</td>
                    <td align="left">{$name}</td>
                    <td>{$qty}</td>
                    <td>{$available}</td>
                </tr>
            EOD;
        }
        $table .= '</tbody></table>';
        $pdf->writeHTML($table, true, false, false, false, '');
    
        // Loop through bids
        foreach ($bids as $index => $bid) {
            $pdf->Ln(5);
            $bgColor = $index % 2 == 0 ? '#eaf1fa' : '#f7f7f7';
            $supplier = $bid->supplier;
    
            // Supplier Info
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 8, "Bid from Supplier #".($index+1), 0, 1);
            $pdf->SetFont('helvetica', '', 10);
    
            $supplierTable = <<<EOD
            <table cellpadding="4" border="1" style="background-color: {$bgColor}">
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
    
            // Product Table
            $productTable = <<<EOD
            <table border="1" cellpadding="4">
                <thead>
                    <tr style="background-color:#dce6f1; font-weight:bold;" align="center">
                        <th>Sr No.</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
            EOD;
    
            foreach ($bid->details as $i => $d) {
                $sr = $i + 1;
                $product = $d->product->name ?? 'N/A';
                $qty = $d->qty;
                $rate = number_format($d->rate, 2);
                $amount = number_format($d->total, 2);
    
                $productTable .= <<<EOD
                    <tr align="center">
                        <td>{$sr}</td>
                        <td align="left">{$product}</td>
                        <td>{$qty}</td>
                        <td align="right">{$rate}</td>
                        <td align="right">{$amount}</td>
                    </tr>
                EOD;
            }
    
            // Totals
            $productTable .= <<<EOD
                <tr>
                    <td colspan="4" align="right"><b>Subtotal</b></td>
                    <td align="right"><b>{$bid->total}</b></td>
                </tr>
                <tr>
                    <td colspan="4" align="right"><b>Tax</b></td>
                    <td align="right"><b>{$bid->tax_amount}</b></td>
                </tr>
                <tr>
                    <td colspan="4" align="right"><b>Discount</b></td>
                    <td align="right"><b>{$bid->discount}</b></td>
                </tr>
                <tr style="background-color:#d0e9c6;">
                    <td colspan="4" align="right"><b>Total</b></td>
                    <td align="right"><b>{$bid->total_amount}</b></td>
                </tr>
                </tbody>
            </table>
            EOD;
            $pdf->writeHTML($productTable, true, false, false, false, '');
    
            // Meta Info
            $quotationDate = date('d-M-Y', strtotime($bid->quotation_date ?? ''));
            $metaInfo = <<<EOD
            <table border="1" cellpadding="4" style="background-color: #fcfcfc;">
                <tr>
                    <td><b>Quotation Date:</b> {$quotationDate}</td>
                    <td><b>Quotation Ref:</b> {$bid->quotation_ref}</td>
                    <td><b>Credit Days:</b> {$bid->credit_days}</td>
                </tr>
                <tr>
                    <td><b>Contact Person:</b> {$bid->contact_person}</td>
                    <td colspan="2"><b>Contact No:</b> {$bid->contact_person_contact}</td>
                </tr>
                <tr>
                    <td><b>Advance (%):</b> {$bid->advance}%</td>
                    <td><b>Advance Amount:</b> Rs. {$bid->advance_amount}</td>
                    <td><b>After Delivery (%):</b> {$bid->after_delivery}%</td>
                </tr>
                <tr>
                    <td colspan="2"><b>After Delivery Amount:</b> Rs. {$bid->after_delivery_amount}</td>
                    <td><b>Delivery Charges:</b> Rs. {$bid->delivery_charges}</td>
                </tr>
                <tr>
                    <td colspan="3"><b>Terms & Conditions:</b> {$bid->terms_condition}</td>
                </tr>
            </table>
            EOD;
            $pdf->writeHTML($metaInfo, true, false, false, false, '');
        }
    
        // Watermark
        $pdf->SetAlpha(0.07);
        $pdf->StartTransform();
        $pdf->Rotate(45, 105, 148);
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Text(20, 150, 'Kainat Travels');
        $pdf->StopTransform();
        $pdf->SetAlpha(1);
    
        return $pdf->Output('PRN_BidSummary_'.$prn->id.'.pdf', 'I');
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
            // Position at 15 mm from bottom
            $this->SetY(-20);
            $this->SetFont('helvetica', 'U', 10);
            $this->Cell(0, 10, 'Kainat Travels | Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'C');
        }

    }
