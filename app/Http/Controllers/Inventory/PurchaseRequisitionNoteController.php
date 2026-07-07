<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Company;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\PurchaseRequisitionNote;
use App\Models\Inventory\PurchaseRequisitionNoteDetail;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TCPDF;

class PurchaseRequisitionNoteController extends Controller
{
    //
    public function index()
    {
        $mrs = MaterialRequest::with([
            'details.product',
            'requestedByUser'
        ])->where('status', 1)->latest()->get();
    
        $prns = PurchaseRequisitionNote::with([
            'mr.requestedByUser',
            'details.product'
        ])->latest()->get();
        
    
        return response()->json([
            'success' => true,
            'message' => 'MRs and PRNs fetched successfully.',
            'mrs'     => $mrs,
            'prns'    => $prns
        ]);
    }
    
    public function store(Request $request)
    { 
        $validated = $request->validate([
            'mr_id' => 'required',
            'items' => 'required',
            'items.*.product_id' => 'required',
            'items.*.qty'        => 'required',
        ]);
    
        DB::beginTransaction();
    
        try {
            // Create PRN header
            $prn = PurchaseRequisitionNote::create([
                'mr_id'     => $validated['mr_id'],
                'status'    => 1,
                'added_by'  => auth()->id(),
                'company_id'=> Auth::user()->company_id,

            ]); 
            $mr = MaterialRequest::findOrFail($validated['mr_id']);
            $mr->status = 3;
            $mr->save();

            // Create PRN item details
            foreach ($validated['items'] as $item) {
                PurchaseRequisitionNoteDetail::create([
                    'prn_id'     => $prn->id,
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'], 
                    'company_id' => Auth::user()->company_id,
                ]);
            }
            DB::commit();
            ActivityLogger::log('Inventory Purchase Requisition', 'create', 'Purchase requisition note created', $prn->id, [], [
                'mr_id' => $validated['mr_id'],
                'items_count' => count($validated['items']),
            ], $request);
            return response()->json([
                'success' => true,
                'message' => 'PRN created successfully.',
                'prn_id' => $prn->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Log actual error for debugging
            Log::error('PRN Store Error: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PRN.',
                'error' => $e->getMessage()  // Optional for debugging
            ], 500);
        }
    }
    public function fetchPrnProducts(Request $request)
    {
        $request->validate([
            'prn_id' => 'required|integer',
        ]);
    
        // Fetch all PRN detail rows that belong to the given PRN ID, including the product relation
        $prnDetails = PurchaseRequisitionNoteDetail::with('product')
            ->where('prn_id', $request->prn_id)
            ->get();
    
        if ($prnDetails->isEmpty()) {
            return response()->json(['message' => 'No products found for this PRN.'], 404);
        }
    
        // Map and return the product data with quantity
        $products = $prnDetails->map(function ($detail) {
            return [
                'id'  => $detail->product?->id,
                'name' => $detail->product?->name,
                'qty'  => $detail->qty,
            ];
        });
    
        return response()->json([
            'products' => $products
        ]);
    }

    public function prnPDF(Request $request)
    { 
        $prnId = $request->input('prn_id');

        $prn = PurchaseRequisitionNote::with([
            'mr.requestedByUser',
            'details.product'
        ])->findOrFail($prnId);
    
        $mr = $prn->mr;
        $details = $prn->details;
        $company = Company::find($prn->company_id);
        $requestedBy = $mr->requestedByUser->name ?? 'N/A';
        $mrDate = date('d-M-Y', strtotime($mr->created_at));
    
        // PDF setup
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
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Purchase Requisition Note', 0, 1, 'C');
    
        // Project
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, 'Project : ' . ($company->name ?? 'Kainat Travels'), 0, 1);
    
        // Line
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(3);
    
        // MR Info Table
        $tbl = <<<EOD
        <table cellpadding="4" border="1">
            <tr>
            <td><b>PRN#</b></td>
            <td>PRN-{$prn->id}</td>
            <td ><b>MR #</b></td>
            <td>MR-{$mr->id}</td>
                 <td width="20%"><b>Date</b></td>
                <td>{$mrDate}</td>
            </tr>
            <tr>
                <td><b>Requested By</b></td>
                <td colspan="5">{$requestedBy}</td>
            </tr>
        </table>
        EOD;
    
        $pdf->writeHTML($tbl, true, false, false, false, '');
    
        // Request Details
        $pdf->Ln(1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PRN Details', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
    
        $table = <<<EOD
                <table border="1" cellpadding="4">
                    <thead>
                        <tr align="center" style="font-weight: bold; background-color: #f0f0f0;">
                            <th>Sr no.</th>
                            <th>Product Name</th>
                            <th>PRN QTY</th>
                            <th>Available Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                EOD;

                foreach ($details as $i => $item) {
                    $srNo = $i + 1;
                    $productName = $item->product->name ?? 'N/A';
                    $prnQty = $item->qty ?? 0;
                    $availableQty = $item->product->qty ?? 0;

                    $table .= <<<EOD
                    <tr>
                        <td align="center">{$srNo}</td>
                        <td align="center">{$productName}</td>
                        <td align="center">{$prnQty}</td>
                        <td align="center">{$availableQty}</td>
                    </tr>
                    EOD;
                }
        $table .= <<<EOD
            </tbody>
        </table>
        EOD;
    
        $pdf->writeHTML($table, true, false, false, false, '');
    
        // Watermark
        $pdf->SetAlpha(0.15);
        $pdf->StartTransform();
        $pdf->Rotate(45, 105, 148);
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Text(20, 150, 'Kainat Travels');
        $pdf->StopTransform();
        $pdf->SetAlpha(1);
    
        return $pdf->Output('PRN_' . $prn->id . '.pdf', 'I');
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
