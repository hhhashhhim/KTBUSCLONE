<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;

use App\Models\Inventory\GoodReceiveNote;
use App\Models\Inventory\GoodReceiveNoteDetail;
use App\Models\Inventory\Product;
use App\Models\Inventory\PurchaseOrderDetail;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\StoreIssuanceNote;
use App\Models\Inventory\StoreIssuanceNoteDetail;
use App\Models\Inventory\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCPDF;

class ReportsController extends Controller
{
    private const REPORTS = [
        'control-register', 'goods-received', 'goods-issued', 'pending-grn',
        'daily-issuance', 'item-issue-summary', 'item-ledger',
        'item-wise-purchase', 'party-wise-purchase', 'balance-stock',
    ];

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

    public function reportData()
    {
        $companyId = Auth::user()->company_id;

        return response()->json([
            'products' => Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'buses' => Bus::where('company_id', $companyId)->orderBy('bus_number')->get(['id', 'bus_number']),
        ]);
    }

    public function runReport(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:' . implode(',', self::REPORTS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'product_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'bus_id' => ['nullable', 'integer'],
            'grn' => ['nullable', 'integer'],
            'po' => ['nullable', 'integer'],
            'mr' => ['nullable', 'integer'],
            'sin' => ['nullable', 'integer'],
        ]);

        return response()->json($this->buildReport($validated));
    }

    public function reportPdf(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:' . implode(',', self::REPORTS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'product_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'bus_id' => ['nullable', 'integer'],
            'grn' => ['nullable', 'integer'],
            'po' => ['nullable', 'integer'],
            'mr' => ['nullable', 'integer'],
            'sin' => ['nullable', 'integer'],
        ]);
        $report = $this->buildReport($validated);
        $company = Auth::user()->company;
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($company->name ?? 'Kainat Travels');
        $pdf->SetTitle($report['title']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 9, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 7, strtoupper($company->name ?? 'Kainat Travels'), 0, 1, 'C');
        if (!empty($company->location)) {
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 5, $company->location, 0, 1, 'C');
        }
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, $report['title'], 0, 1, 'C');
        $period = (!empty($validated['from']) || !empty($validated['to']))
            ? 'Period: ' . ($validated['from'] ?? 'Beginning') . ' to ' . ($validated['to'] ?? 'Today')
            : 'Generated: ' . date('d-M-Y h:i A');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, $period, 0, 1, 'C');
        $pdf->Ln(3);

        $columnCount = max(1, count($report['columns']));
        $width = round(100 / $columnCount, 2);
        $html = '<table border="1" cellpadding="4"><thead><tr style="background-color:#263b70;color:#ffffff;font-weight:bold;">';
        foreach ($report['columns'] as $column) {
            $html .= '<th width="' . $width . '%">' . $this->escape($column['label']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        if (empty($report['rows'])) {
            $html .= '<tr><td colspan="' . $columnCount . '" align="center">No records found</td></tr>';
        } else {
            foreach ($report['rows'] as $row) {
                $html .= '<tr>';
                foreach ($report['columns'] as $column) {
                    $align = ($column['align'] ?? 'left') === 'right' ? 'right' : (($column['align'] ?? '') === 'center' ? 'center' : 'left');
                    $html .= '<td align="' . $align . '" width="' . $width . '%">' . $this->escape($row[$column['key']] ?? '-') . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Printed by: ' . (Auth::user()->name ?? 'System') . ' | ' . date('d-M-Y h:i A'), 0, 1, 'R');
        $pdf->Output(str_replace(' ', '_', $report['title']) . '_' . date('Ymd_His') . '.pdf', 'I');
    }

    private function buildReport(array $filters): array
    {
        $type = $filters['type'];
        $builders = [
            'control-register' => 'controlRegisterReport',
            'goods-received' => 'goodsReceivedReport',
            'goods-issued' => 'goodsIssuedReport',
            'pending-grn' => 'pendingGrnReport',
            'daily-issuance' => 'dailyIssuanceReport',
            'item-issue-summary' => 'itemIssueSummaryReport',
            'item-ledger' => 'itemLedgerReport',
            'item-wise-purchase' => 'itemWisePurchaseReport',
            'party-wise-purchase' => 'partyWisePurchaseReport',
            'balance-stock' => 'balanceStockReport',
        ];

        return $this->{$builders[$type]}($filters);
    }

    private function controlRegisterReport(array $f): array
    {
        $companyId = Auth::user()->company_id;
        $from = Carbon::parse($f['from'] ?? now()->toDateString())->startOfDay();
        $to = Carbon::parse($f['to'] ?? now()->toDateString())->endOfDay();
        $products = Product::with('unit')->where('company_id', $companyId)
            ->when($f['product_id'] ?? null, fn ($q, $id) => $q->where('id', $id))->orderBy('name')->get();
        $rows = $products->map(function ($product, $index) use ($companyId, $from, $to) {
            $receivedBefore = GoodReceiveNoteDetail::where('company_id', $companyId)->where('product_id', $product->id)->where('created_at', '<', $from)->sum('qty');
            $issuedBefore = StoreIssuanceNoteDetail::where('company_id', $companyId)->where('product_id', $product->id)->where('created_at', '<', $from)->sum('qty');
            $received = GoodReceiveNoteDetail::where('company_id', $companyId)->where('product_id', $product->id)->whereBetween('created_at', [$from, $to])->sum('qty');
            $issued = StoreIssuanceNoteDetail::where('company_id', $companyId)->where('product_id', $product->id)->whereBetween('created_at', [$from, $to])->sum('qty');
            return ['sr' => $index + 1, 'item' => $product->name, 'unit' => optional($product->unit)->name ?: '-', 'opening' => $receivedBefore - $issuedBefore, 'received' => $received, 'issued' => $issued, 'balance' => ($receivedBefore - $issuedBefore) + $received - $issued];
        })->values()->all();
        return $this->report('Inventory Control Register', [['sr','Sr.','center'],['item','Item'],['unit','Unit'],['opening','Opening','right'],['received','Received','right'],['issued','Issued','right'],['balance','Balance','right']], $rows);
    }

    private function goodsReceivedReport(array $f): array
    {
        $query = GoodReceiveNoteDetail::with(['product', 'goodReceiveNote.supplier'])->where('company_id', Auth::user()->company_id);
        $this->dateFilter($query, $f);
        $query->when($f['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id));
        $query->when($f['grn'] ?? null, fn ($q, $id) => $q->where('good_receive_note_id', $id));
        $query->when($f['po'] ?? null, fn ($q, $id) => $q->whereHas('goodReceiveNote', fn ($n) => $n->where('po_id', $id)));
        $query->when($f['supplier_id'] ?? null, fn ($q, $id) => $q->whereHas('goodReceiveNote', fn ($n) => $n->where('supplier_id', $id)));
        $rows = $query->latest()->get()->map(fn ($d, $i) => ['sr'=>$i+1,'date'=>$d->created_at->format('d-M-Y'),'grn'=>'GRN-'.$d->good_receive_note_id,'po'=>'PO-'.(optional($d->goodReceiveNote)->po_id ?? '-'),'supplier'=>optional(optional($d->goodReceiveNote)->supplier)->name ?: '-','item'=>optional($d->product)->name ?: '-','qty'=>$d->qty,'rate'=>$this->money($d->rate),'total'=>$this->money($d->net_amount ?? $d->total)])->all();
        return $this->report('Goods Received Register', [['sr','Sr.','center'],['date','Date'],['grn','GRN #'],['po','PO #'],['supplier','Supplier'],['item','Item'],['qty','Qty','right'],['rate','Rate','right'],['total','Total','right']], $rows);
    }

    private function goodsIssuedReport(array $f): array
    {
        $query = StoreIssuanceNoteDetail::with(['product', 'bus', 'storeIssuanceNote'])->where('company_id', Auth::user()->company_id);
        $this->dateFilter($query, $f);
        $query->when($f['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))->when($f['bus_id'] ?? null, fn ($q, $id) => $q->where('bus_id', $id));
        $query->when($f['sin'] ?? null, fn ($q, $id) => $q->where('store_issuance_note_id', $id));
        $query->when($f['mr'] ?? null, fn ($q, $id) => $q->whereHas('storeIssuanceNote', fn ($n) => $n->where('mr_id', $id)));
        $rows = $query->latest()->get()->map(fn ($d, $i) => ['sr'=>$i+1,'date'=>$d->created_at->format('d-M-Y'),'mr'=>'MR-'.(optional($d->storeIssuanceNote)->mr_id ?? '-'),'sin'=>'SIN-'.$d->store_issuance_note_id,'bus'=>optional($d->bus)->bus_number ?: '-','item'=>optional($d->product)->name ?: '-','qty'=>$d->qty,'rate'=>$this->money($d->rate),'total'=>$this->money($d->total)])->all();
        return $this->report('Goods Issued Register', [['sr','Sr.','center'],['date','Date'],['mr','MR #'],['sin','SIN #'],['bus','Bus'],['item','Item'],['qty','Qty','right'],['rate','Rate','right'],['total','Total','right']], $rows);
    }

    private function pendingGrnReport(array $f): array
    {
        $companyId = Auth::user()->company_id;
        $query = PurchaseOrderDetail::with(['product'])->where('company_id', $companyId);
        $this->dateFilter($query, $f);
        $query->when($f['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))->when($f['po'] ?? null, fn ($q, $id) => $q->where('po_id', $id));
        $query->when($f['supplier_id'] ?? null, fn ($q, $id) => $q->whereHas('purchaseOrder', fn ($p) => $p->where('supplier_id', $id)));
        $supplierIds = PurchaseOrder::where('company_id', $companyId)->pluck('supplier_id', 'id');
        $suppliers = Supplier::where('company_id', $companyId)->pluck('name', 'id');
        $rows = $query->get()->map(function ($d) use ($companyId, $supplierIds, $suppliers) {
            $received = GoodReceiveNoteDetail::where('company_id', $companyId)->where('product_id', $d->product_id)->whereHas('goodReceiveNote', fn ($g) => $g->where('po_id', $d->po_id))->sum('qty');
            $pending = max(0, (float)$d->qty - (float)$received);
            if ($pending <= 0) return null;
            return ['po'=>'PO-'.$d->po_id,'supplier'=>$suppliers[$supplierIds[$d->po_id] ?? 0] ?? '-','item'=>optional($d->product)->name ?: '-','ordered'=>$d->qty,'received'=>$received,'pending'=>$pending];
        })->filter()->values()->map(fn ($r, $i) => array_merge(['sr'=>$i+1], $r))->all();
        return $this->report('Pending GRN Report', [['sr','Sr.','center'],['po','PO #'],['supplier','Supplier'],['item','Item'],['ordered','Ordered','right'],['received','Received','right'],['pending','Pending','right']], $rows);
    }

    private function dailyIssuanceReport(array $f): array { $report = $this->goodsIssuedReport($f); $report['title'] = 'Daily Issuance Detail'; return $report; }

    private function itemIssueSummaryReport(array $f): array
    {
        $query = StoreIssuanceNoteDetail::with(['product', 'bus'])->where('company_id', Auth::user()->company_id);
        $this->dateFilter($query, $f);
        $query->when($f['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))->when($f['bus_id'] ?? null, fn ($q, $id) => $q->where('bus_id', $id));
        $rows = $query->get()->groupBy(fn ($d) => $d->product_id . ':' . ($d->bus_id ?? 0))->values()->map(function ($items, $i) { $qty=$items->sum('qty'); $value=$items->sum('total'); $first=$items->first(); return ['sr'=>$i+1,'item'=>optional($first->product)->name ?: '-','bus'=>optional($first->bus)->bus_number ?: '-','qty'=>$qty,'avg_rate'=>$this->money($qty ? $value/$qty : 0),'value'=>$this->money($value)]; })->all();
        return $this->report('Item Issue Summary', [['sr','Sr.','center'],['item','Item'],['bus','Bus'],['qty','Total Qty','right'],['avg_rate','Avg. Rate','right'],['value','Total Value','right']], $rows);
    }

    private function itemLedgerReport(array $f): array
    {
        if (empty($f['product_id'])) return $this->report('Item Ledger', [['message','Information']], [['message'=>'Select a product to view its ledger.']]);
        $companyId=Auth::user()->company_id; $product=Product::where('company_id',$companyId)->findOrFail($f['product_id']); $from=Carbon::parse($f['from'] ?? '1900-01-01')->startOfDay(); $to=Carbon::parse($f['to'] ?? now()->toDateString())->endOfDay();
        $openingIn=GoodReceiveNoteDetail::where('company_id',$companyId)->where('product_id',$product->id)->where('created_at','<',$from)->sum('qty'); $openingOut=StoreIssuanceNoteDetail::where('company_id',$companyId)->where('product_id',$product->id)->where('created_at','<',$from)->sum('qty'); $balance=$openingIn-$openingOut;
        $moves=collect(); GoodReceiveNoteDetail::where('company_id',$companyId)->where('product_id',$product->id)->whereBetween('created_at',[$from,$to])->get()->each(fn($d)=>$moves->push(['date'=>$d->created_at,'type'=>'REC','doc'=>'GRN-'.$d->good_receive_note_id,'in'=>(float)$d->qty,'out'=>0,'rate'=>(float)$d->rate])); StoreIssuanceNoteDetail::where('company_id',$companyId)->where('product_id',$product->id)->whereBetween('created_at',[$from,$to])->get()->each(fn($d)=>$moves->push(['date'=>$d->created_at,'type'=>'ISS','doc'=>'SIN-'.$d->store_issuance_note_id,'in'=>0,'out'=>(float)$d->qty,'rate'=>(float)$d->rate]));
        $rows=[['date'=>$from->format('d-M-Y'),'type'=>'OPEN','doc'=>'Opening Balance','in'=>'-','out'=>'-','rate'=>'-','balance'=>$balance]]; foreach($moves->sortBy('date') as $m){$balance += $m['in']-$m['out']; $rows[]=['date'=>$m['date']->format('d-M-Y'),'type'=>$m['type'],'doc'=>$m['doc'],'in'=>$m['in'] ?: '-','out'=>$m['out'] ?: '-','rate'=>$this->money($m['rate']),'balance'=>$balance];}
        return $this->report('Item Ledger - '.$product->name, [['date','Date'],['type','Type','center'],['doc','Document'],['in','Qty In','right'],['out','Qty Out','right'],['rate','Rate','right'],['balance','Balance','right']], $rows);
    }

    private function itemWisePurchaseReport(array $f): array { $report=$this->goodsReceivedReport($f); $report['title']='Item-wise Purchase History'; return $report; }
    private function partyWisePurchaseReport(array $f): array { $report=$this->goodsReceivedReport($f); $report['title']='Party-wise Purchase History'; return $report; }

    private function balanceStockReport(array $f): array
    {
        $rows=Product::with(['unit','category'])->where('company_id',Auth::user()->company_id)->when($f['product_id'] ?? null,fn($q,$id)=>$q->where('id',$id))->orderBy('name')->get()->map(fn($p,$i)=>['sr'=>$i+1,'item'=>$p->name,'category'=>optional($p->category)->name ?: '-','unit'=>optional($p->unit)->name ?: '-','qty'=>$p->qty,'avg_rate'=>$this->money($p->avg_price),'value'=>$this->money((float)$p->qty*(float)$p->avg_price)])->all();
        return $this->report('Balance Stock Report', [['sr','Sr.','center'],['item','Item'],['category','Category'],['unit','Unit'],['qty','Balance Qty','right'],['avg_rate','Avg. Rate','right'],['value','Stock Value','right']], $rows);
    }

    private function dateFilter($query, array $f): void { if (!empty($f['from'])) $query->whereDate('created_at','>=',$f['from']); if (!empty($f['to'])) $query->whereDate('created_at','<=',$f['to']); }
    private function report(string $title, array $columns, array $rows): array { return ['title'=>$title,'columns'=>array_map(fn($c)=>['key'=>$c[0],'label'=>$c[1],'align'=>$c[2] ?? 'left'],$columns),'rows'=>$rows,'count'=>count($rows)]; }
    private function money($value): string { return number_format((float)$value,2); }
    private function escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

    public function bus()
    {
        $buses = Bus::all();
        return response()->json($buses);
    }
}
