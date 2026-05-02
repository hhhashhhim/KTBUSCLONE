<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckJazzcashPendingPayment extends Command
{
    protected $signature = 'jazzcash:check-pending-payment';
    protected $description = 'Check JazzCash pending payment and process DB actions if confirmed';

    public function handle(): int
    {
        Log::info('start');
        try {
            $pendingSeatTypes = ['advance booking', 'reserved', 'pending booking'];


            DB::beginTransaction();

            Log::info('initiate');


            $tickets = Ticket::where([
                "terminal_id" => 14,
                "online_terminal" => 1
            ])
                ->whereIn("type", $pendingSeatTypes)
                ->whereNotNull("transaction_id")
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy('transaction_id')
                ->map(function ($items) {
                    return $items->first();
                })
                ->values();

            foreach ($tickets as $key => $ticket) {
                Log::info('JazzCash pending payment check started', [
                    'transaction_id' => $ticket->transaction_id,
                    'invoice_id' => $ticket->invoice_id,
                    'current_type' => $ticket->type,
                ]);

                $jazzcashRespons = confirmJazzcashPendingPayment($ticket->transaction_id);
                $jcStatus = $jazzcashRespons->status;
                $response = $jazzcashRespons->response;
                $responseCode = is_object($response) ? ($response->pp_PaymentResponseCode ?? null) : null;
                $responseStatus = $jcStatus ? 'success' : 'non-success';

                Log::info('JazzCash pending payment response received', [
                    'transaction_id' => $ticket->transaction_id,
                    'status' => $responseStatus,
                    'response_code' => $responseCode,
                    'response' => $response,
                ]);

                if ($jcStatus) {
                    // this is for fare validation how much amount received from payment gateway
                    $checkTotal = Ticket::where("invoice_id", $ticket->invoice_id)
                        ->where('company_id', $ticket->company_id)
                        ->whereIn("type", $pendingSeatTypes)
                        ->selectRaw('(SUM(seat_fare) - SUM(discount)) as amount')
                        ->first()->amount;
                    $jcAmount = ($response->pp_Amount / 100);
                    if ($jcAmount != $checkTotal) {
                        Log::info('Amount invalid against ' . $ticket->transaction_id . 'jc-update-amount' . $response->pp_Amount . 'jc-amount' . $jcAmount . 'seat-amount' . $checkTotal);
                        Log::info(json_encode($response));
                        Log::info('Seat type update skipped after JazzCash confirmation', [
                            'transaction_id' => $ticket->transaction_id,
                            'invoice_id' => $ticket->invoice_id,
                            'reason' => 'amount_mismatch',
                        ]);
                        continue;
                    }

                    $updatedRows = Ticket::where("invoice_id", $ticket->invoice_id)
                        ->where('company_id', $ticket->company_id)
                        ->whereIn('type', $pendingSeatTypes)
                        ->update([
                        'type' => 'booked',
                        'updated_by' => 0,
                        'booked_time' => date("Y-m-d H:i:s"),
                    ]);
                    Log::info('ticket update');
                    Log::info(json_encode($response));
                    Log::info('Seat type changed to booked after JazzCash confirmation', [
                        'transaction_id' => $ticket->transaction_id,
                        'invoice_id' => $ticket->invoice_id,
                        'updated_rows' => $updatedRows,
                    ]);
                    ticketConfirmedMessage($ticket->invoice_id);
                    //////////////////////////////////////////////
                    ActivityLog::create([
                        "activity_by" => 0,
                        "message" => "Server Automation" . " | update ticket (advance to confirm) | time : " . $ticket->schedule_date . " " . $ticket->schedule_time . " | invoice id :" . $ticket->invoice_id . " / " . (isset($jcAmount) ? json_encode($jcAmount) : "*"),
                        "requested_host" => 0,
                        "company_id" => $ticket->company_id
                    ]);
                } else {
                    Log::info('JazzCash pending payment not confirmed against ' . $ticket->transaction_id);
                    Log::info(json_encode($response));
                    Log::info('Seat type update skipped', [
                        'transaction_id' => $ticket->transaction_id,
                        'invoice_id' => $ticket->invoice_id,
                        'reason' => 'jazzcash_non_success_response',
                    ]);
                }
            }



            DB::commit();
            Log::info('commit successfully');


            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('JazzCash pending payment check failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return self::FAILURE;
        }
        Log::info('end');
    }
}
