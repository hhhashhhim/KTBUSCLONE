<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;;

use App\Models\Company;
use App\Models\Inventory\GoodReceiveNote;
use App\Models\Inventory\MaterialRequest;
use App\Models\Inventory\MaterialRequestDetail;
use App\Models\Inventory\Product;
use App\Models\Inventory\PurchaseRequisitionNote;
use App\Models\Inventory\StoreIssuanceNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCPDF;

class MaterialRequestController extends Controller
{

    public function index()
    {
        $mrs = MaterialRequest::with(['details.product', 'details.bus', 'requestedByUser', 'storeIssuance.details'])->latest()->get();
        $products = Product::latest()->get();
        $buses = Bus::latest()->get();
        return response()->json([
            'success'  => true,
            'message'  => 'Material Requests fetched successfully.',
            'data'     => $mrs,
            'products' => $products,
            'buses' => $buses,
        ], 200);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_id'               => 'nullable',
            'details'              => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.qty'        => 'required|numeric|min:1',
            'details.*.reason'     => 'nullable|string',
            'direct_store'         => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Case 1: Direct Store Selected
            if ($request->direct_store) {
                // 1️⃣ Create Good Receive Note (GRN)
                $grn = GoodReceiveNote::create([
                    'po_id'       => null,
                    'supplier_id' => null,
                    'received_by' => auth()->id(),
                    'added_by'    => auth()->id(),
                    'company_id'  => auth()->user()->company_id,
                ]);

                foreach ($request->details as $detail) {
                    $grn->details()->create([
                        'product_id'        => $detail['product_id'],
                        'qty'               => $detail['qty'],
                        'rate'              => 0,
                        'total'             => 0,
                        'tax'               => 0,
                        'delivery_charges'  => 0,
                        'discount'          => 0,
                        'net_amount'        => 0,
                        'company_id'        => auth()->user()->company_id,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                // 2️⃣ Create Purchase Requisition Note (PRN)
                $prn = PurchaseRequisitionNote::create([
                    'mr_id'      => null,
                    'status'     => 2,
                    'added_by'   => auth()->id(),
                    'company_id' => auth()->user()->company_id,
                ]);

                foreach ($request->details as $detail) {
                    $prn->details()->create([
                        'product_id' => $detail['product_id'],
                        'qty'        => $detail['qty'],
                        'company_id' => auth()->user()->company_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    // ✅ Update product stock
                    $product = Product::find($detail['product_id']);
                    if ($product) {
                        $product->qty = $product->qty + $detail['qty'];
                        $product->avg_price = $detail['avg_price'];
                        $product->save();
                    }
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Good Receive Note & Purchase Requisition Note created successfully (Direct Store).',
                ]);
            }

            // ✅ Case 2: Regular MR Flow
            $mr = MaterialRequest::create([
                'requested_by' => auth()->id(),
                'status'       => 1,
                'company_id'   => auth()->user()->company_id,
                'added_by'     => auth()->id(),
            ]);

            foreach ($request->details as $detail) {
                $mr->details()->create([
                    'product_id'       => $detail['product_id'],
                    'bus_id'           => $request->bus_id,
                    'qty'              => $detail['qty'],
                    'store_Issued_qty' => 0,
                    'reason'           => $detail['reason'] ?? null,
                    'company_id'       => auth()->user()->company_id,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Material Request created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'          =>     'required',
            'qty'         =>     'required',
            'reason'      =>     'nullable',
            'bus_id'      =>     'nullable',
        ]);
        $mr     = MaterialRequestDetail::findOrFail($validated['id']);
        $mr->update([
            'qty'    => $validated['qty'],
            'reason' => $validated['reason'],
            'bus_id' => $validated['bus_id'] ?? $mr->bus_id,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Material Request Detail updated successfully.',
            'data'    => $mr,
        ], 200); // <== make sure to set status 200
    }
    public function mr_destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:material_requests,id',
        ]);
        $materialRequest = MaterialRequest::findOrFail($request->id);
        $materialRequest->delete();
        return response()->json([
            'success' => true,
            'message' => 'Material Request deleted successfully.',
        ]);
    }
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:material_request_details,id',
        ]);
        $detail = MaterialRequestDetail::findOrFail($request->id);
        $detail->delete();
        return response()->json([
            'success' => true,
            'message' => 'Material Request Detail deleted successfully.',
        ]);
    }

    public function mrPDF(Request $request)
    {
        // Load the first MR with details and relations
        $mr = MaterialRequest::with(['details.product', 'storeIssuance.details', 'requestedByUser'])->where('id', $request->mr_id)->first();
        $details     = $mr->details;
        $company     = Company::where('id', $mr->company_id)->first();
        $requestedBy = User::where('id', $mr->requested_by)->first();
        // Start TCPDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('Kainat Travel Material Request');
        $pdf->SetAuthor('Kainat Travel  Material Request');
        $pdf->SetTitle('Purchase Requisition Note');

        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false); // Optional, if you’re not using a header
        $pdf->setPrintFooter(true);  // ✅ This is necessary

        $pdf->setPrintFooter(true);
        $pdf->AddPage();
        // Add Logo - top left
        $logoPath = public_path('assets/img/kt-logo.jpg');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 25); // x=10mm, y=10mm, width=30mm
        }
        // Title

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Material Request', 0, 1, 'C');
        // Project Name
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 8, 'Project : ' . ($company->name ?? 'Kainat Travels'), 0, 1);

        // Line
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(3);

        // MR Info
        $pdf->SetFont('helvetica', '', 10);
        $mrDate = date('d-M-Y', strtotime($mr->created_at));
        $requestedBy = $requestedBy->name ?? 'N/A';

        $tbl = <<<EOD
            <table cellpadding="4" border="1">
                <tr>
                    <td width="20%"><b>MR #</b></td>
                    <td width="30%">MR-{$mr->id}</td>
                    <td width="20%"><b>Date </b></td>
                    <td width="30%">{$mrDate}</td>
                </tr>
                <tr> 
                    <td><b>Requested By</b></td>
                    <td colspan="3">{$requestedBy}</td>
                </tr>
            </table>
            EOD;

        $pdf->writeHTML($tbl, true, false, false, false, '');
        // Request Details
        $pdf->Ln(1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Request Details', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $table = <<<EOD
        <table border="1" cellpadding="4">
            <thead>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <th>Sr no.</th> 
                    <th>Bus Number.</th> 
                    <th>Product Name</th> 
                    <th>Requested QTY</th> 
                    <th>Issued QTY</th>  
                </tr>
            </thead>
            <tbody>
        EOD;

        foreach ($details as $i => $item) {
            $productName  = $item->product->name ?? 'N/A';
            $busNumber    = $item->bus->bus_number ?? 'N/A';
            $askedQty     = $item->qty ?? 0;
            $issuedQty    = $item->store_Issued_qty ?? 0;
            $reason = $item->reason ?? 'For mess';
            $srNo = $i + 1;
            $table .= <<<EOD
                <tr>
                    <td align="center">{$srNo}</td> 
                    <td>{$busNumber}</td>
                    <td>{$productName}</td>
                    <td align="center">{$askedQty}</td>
                    <td align="center">{$issuedQty}</td> 
                </tr>
                 <tr style="margin-bottom: 15px"> 
                    <th><b>Reason</b></th> 
                    <td colspan="4">{$reason}</td> 
                </tr>
        EOD;
        }

        $table .= <<<EOD
            </tbody>
        </table>
        EOD;
        $pdf->writeHTML($table, true, false, false, false, '');
        // Output PDF

        $pdf->SetPDFVersion('1.4'); // Enable transparency support
        $pdf->StartTransform();
        $pdf->SetAlpha(0.15); // Increase opacity to 30% (less transparent)
        $pdf->Rotate(45, 105, 148);
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->SetTextColor(0, 0, 0); // Black color
        $pdf->Text(20, 150, 'Kainat Travels');
        $pdf->StopTransform();
        $pdf->SetAlpha(1); // Reset transparency


        $pdf->Output('PRN_' . $mr->id . '.pdf', 'I');
    }

    public function bus()
    {
        $buses = Bus::all();
        return response()->json($buses);
    }
   public function productPDF(Request $request)
{
   $product = Product::with([
    'category',
    'unit',
    'issuanceDetails.storeIssuanceNote',
    'issuanceDetails.bus',
    'materialRequestDetails'
])->find($request->product_id);


    $company = Company::first(); // Or relevant company info

  $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false); // Optional, if you’re not using a header
        $pdf->setPrintFooter(true);  // ✅ This is necessary

        $pdf->setPrintFooter(true);
    $pdf->AddPage();

    // Logo
    $logoPath = public_path('assets/img/kt-logo.jpg');
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 12, 30);
    }

    // Title & Company Info
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Product Issuance Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
     $pdf->Cell(0, 8, 'Company : ' . ($company->name ?? 'Kainat Travels'), 0, 1);

    $pdf->Ln(5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    // Product Info Table
    $categoryName = $product->category ? $product->category->name : '-';
    $unitName     = $product->unit ? $product->unit->name : '-';

    $tbl = <<<EOD
<style>
table {
    border-collapse: collapse; width: 100%;
}
th {
   background-color: #f7f7f7; border: 1px solid #000; padding: 5px; font-weight: bold; text-align: center;
}
td {
    text-align: center;
    padding: 6px;
    border: 1px solid #000;
}
</style>
<table>
    <tr>
        <th>Product Name</th>
        <th>Category</th>
        <th>Unit</th>
        <th>Available Quantity</th>
        <th>Price</th>
    </tr>
    <tr>
        <td>{$product->name}</td>
        <td>{$categoryName}</td>
        <td>{$unitName}</td>
        <td>{$product->qty}</td>
        <td>{$product->avg_price}</td>
    </tr>
</table>
EOD;

    $pdf->writeHTML($tbl, true, false, false, false, '');

    // Issuance History Table
  // Issuance History Table
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Issuance History', 0, 1);
$pdf->SetFont('helvetica', '', 11);

$tbl2 = '<style>
table.history { border-collapse: collapse; width: 100%; }
table.history th { background-color: #f7f7f7; border: 1px solid #000; padding: 5px; font-weight: bold; text-align: center; }
table.history td { border: 1px solid #000; padding: 5px; text-align: center; }
</style>
<table class="history">
    <tr>
        <th>#</th>
        <th>Requested By</th>
        <th>Reason</th>
        <th>Bus</th>
        <th>Issuance Quantity</th>
        <th>Date</th>
    </tr>';

if($product->issuanceDetails->count() > 0) {
    foreach ($product->issuanceDetails->sortByDesc('created_at') as $i => $row) {

        // Find the MR detail corresponding to this issuance
        $mrDetail = $product->materialRequestDetails
            ->where('bus_id', $row->bus_id)
            ->where('product_id', $row->product_id) // optional but safer
            ->where('store_Issued_qty', $row->qty) // optional if qty matches
            ->sortByDesc('created_at')
            ->first();

        $reason = $mrDetail->reason ?? '-';

        $tbl2 .= '<tr>
            <td>' . ($i + 1) . '</td>
            <td>' . ($row->storeIssuanceNote->requested_by ?? '-') . '</td>
            <td>' . $reason . '</td>
            <td>' . ($row->bus->bus_number ?? '-') . '</td>
            <td>' . $row->qty . '</td>
            <td>' . date('d-M-Y', strtotime($row->created_at)) . '</td>
        </tr>';
    }
} else {
    $tbl2 .= '<tr><td colspan="6" style="text-align:center;">No issuance history found</td></tr>';
}


$tbl2 .= '</table>';

$pdf->writeHTML($tbl2, true, false, false, false, '');


    // Output
    $pdf->SetPDFVersion('1.4'); // Enable transparency support
       $pdf->StartTransform();
       $pdf->SetAlpha(0.15); // Increase opacity to 30% (less transparent)
       $pdf->Rotate(45, 105, 148);
       $pdf->SetFont('helvetica', 'B', 50);
       $pdf->SetTextColor(0, 0, 0); // Black color
       $pdf->Text(20, 150, 'Kainat Travels');
       $pdf->StopTransform();
       $pdf->SetAlpha(1); // Reset transparency

    $pdf->Output('Product_' . $product->id . '.pdf', 'I');
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
