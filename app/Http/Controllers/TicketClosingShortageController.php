<?php

namespace App\Http\Controllers;

use App\Models\Expense\TicketMergeExpense;
use App\Models\TicketClosingShortage;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketClosingShortageController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'ticket_closing_id' => 'required|integer',
        'records' => 'required|array|min:1',
        'records.*.type' => 'required|in:start,return',
        'records.*.route' => 'nullable|integer',
        'records.*.bus_id' => 'nullable|integer',
        'records.*.rows' => 'required|array',
        'records.*.rows.*.terminal_id' => 'required|integer',
        'records.*.rows.*.passenger_count' => 'required|integer',
        'records.*.rows.*.kt_commission' => 'required|numeric',
        'records.*.rows.*.elt' => 'required|numeric',
        'records.*.rows.*.cancellation_amount' => 'required|numeric',
        'records.*.rows.*.other_commission' => 'required|numeric',
        'records.*.rows.*.total_receivable' => 'required|numeric',
        'records.*.rows.*.total_received_cash' => 'required|numeric',
        'records.*.rows.*.total_received_bank' => 'required|numeric',
        'records.*.rows.*.shortage' => 'required|numeric',
        'records.*.rows.*.received' => 'required|numeric',
        'records.*.rows.*.bank_id' => 'nullable|integer',
    ]);

    DB::transaction(function () use ($request) {
        foreach ($request->records as $record) {
            foreach ($record['rows'] as $row) {
                TicketClosingShortage::updateOrCreate(
                    [
                        'ticket_closing_id' => $request->ticket_closing_id,
                        'terminal_id' => $row['terminal_id'],
                        'type' => $record['type'],
                    ],
                    [
                        'bus_id'              => $record['bus_id'] ?? 0,
                        'route_id'            => $record['route'] ?? null,
                        'passenger_count'     => round($row['passenger_count']),
                        'kt_commission'       => round($row['kt_commission']),
                        'elt'                 => round($row['elt']),
                        'cancellation_amount' => round($row['cancellation_amount']),
                        'other_commission'    => round($row['other_commission']),
                        'total_receivable'    => round($row['total_receivable']),
                        'total_received_cash' => round($row['total_received_cash']),
                        'bank_id'             => $row['bank_id'] ?? null,
                        'total_received_bank' => round($row['total_received_bank']),
                        'shortage'            => round($row['shortage']),
                        'received'            => round($row['received']),
                        'company_id'          => Auth::user()->company_id,
                    ]
                );
            }
        }
    });

    ActivityLogger::log('Ticket Closing Shortage', 'create', 'Ticket closing shortage saved', $request->ticket_closing_id, [], [
        'ticket_closing_id' => $request->ticket_closing_id,
        'records_count' => count($request->records),
    ], $request);

    return response()->json([
        'message' => 'Ticket closing shortage saved successfully'
    ], 200);
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
        $oldValues = $shortageRow->only(['total_received_cash', 'bank_id', 'total_received_bank', 'received', 'shortage']);

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

        ActivityLogger::log('Ticket Closing Shortage', 'update', 'Ticket closing shortage updated', $shortageRow->id, $oldValues, $shortageRow->fresh()->only(['total_received_cash', 'bank_id', 'total_received_bank', 'received', 'shortage']), $request);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $shortageRow->fresh(['terminal', 'bank'])
        ]);
    }
}
