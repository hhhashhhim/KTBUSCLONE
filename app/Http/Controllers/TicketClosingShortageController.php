<?php

namespace App\Http\Controllers;

use App\Models\Expense\TicketMergeExpense;
use App\Models\TicketClosingShortage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketClosingShortageController extends Controller
{
    public function store(Request $request)
{

    $request->validate([
        'ticket_closing_id' => 'required|integer',
        'type' => 'nullable|in:start,return',
        'rows' => 'required|array',
        'rows.*.terminal_id' => 'required|integer',
        'rows.*.passenger_count' => 'required|integer',
        'rows.*.kt_commission' => 'required|numeric',
        'rows.*.other_commission' => 'required|numeric',
        'rows.*.total_receivable' => 'required|numeric',
        'rows.*.total_received_cash' => 'required|numeric',
        'rows.*.total_received_bank' => 'required|numeric',
        'rows.*.shortage' => 'required|numeric',
        'rows.*.received' => 'required|numeric',
        'rows.*.bank_id' => 'nullable|integer',
    ]);

    foreach ($request->rows as $row) {
        TicketClosingShortage::updateOrCreate(
            [
                'ticket_closing_id' => $request->ticket_closing_id,
                'terminal_id' => $row['terminal_id'],
                'type' => $request->type,
            ],
            [
                'passenger_count' => $row['passenger_count'],
                'kt_commission' => $row['kt_commission'],
                'elt' => $row['elt'],
                'cancellation_amount' => $row['cancellation_amount'],
                'other_commission' => $row['other_commission'],
                'total_receivable' => $row['total_receivable'],
                'total_received_cash' => $row['total_received_cash'],
                'bank_id' => $row['bank_id'],
                'total_received_bank' => $row['total_received_bank'],
                'shortage' => $row['shortage'],
                'received' => $row['received'],
                "company_id" => Auth::user()->company_id
            ]
        );
    }
    

    return response()->json([
        'status' => true,
        'message' => 'Ticket closing shortages saved successfully',
    ]);
}

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ticket_closing_shortages,id',
            'total_received_cash' => 'nullable|numeric|min:0',
            'total_received_bank' => 'nullable|numeric|min:0',
            'bank_id' => 'nullable|exists:banks,id',
        ]);

        $shortageRow = TicketClosingShortage::findOrFail($request->id);

        $cash = (float) ($request->total_received_cash ?? 0);
        $bank = (float) ($request->total_received_bank ?? 0);

        $received = $cash + $bank;
        $receivable = (float) $shortageRow->total_receivable - $shortageRow->other_commission;

        // ❌ Validation: received cannot exceed receivable
        if ($received > $receivable) {
            return response()->json([
                'message' => 'Cash + Bank cannot exceed Total Receivable'
            ], 422);
        }

        $shortage = max(0, $receivable - $received);

        $shortageRow->update([
            'total_received_cash' => $cash,
            'bank_id' => $request->bank_id,
            'total_received_bank' => $bank,
            'received' => $received,
            'shortage' => $shortage,
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $shortageRow->fresh(['terminal', 'bank'])
        ]);
    }


}
