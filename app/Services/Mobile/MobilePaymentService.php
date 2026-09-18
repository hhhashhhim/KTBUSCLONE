<?php

namespace App\Services\Mobile;

use App\Models\ActivityLog;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\TicketIsPartial;
use App\Models\Invoice;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\MobilePayment;
use App\Models\PassengerAccount;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class MobilePaymentService
{
    private $gateway;
    public function __construct(MobilePaymentGateway $gateway) { $this->gateway = $gateway; }

    public function present(MobilePayment $payment): array
    {
        return [
            'environment' => $payment->environment,
            'id' => $payment->public_id, 'method' => $payment->method, 'status' => $payment->status,
            'expires_at' => $payment->expires_at->toIso8601String(),
            'checkout_url' => !config('mobile_payments.preview_only') && $payment->status === 'pending' && !$payment->started_at && $payment->expires_at->isFuture() && $this->hasReservation($payment)
                ? URL::temporarySignedRoute('mobile.payments.checkout', $payment->expires_at, ['payment' => $payment->public_id]) : null,
        ];
    }

    public function owned(PassengerAccount $account, int $invoice): MobilePayment
    {
        $payment = MobilePayment::where('invoice_id', $invoice)->where('company_id', $account->company_id)
            ->where('passenger_account_id', $account->id)->first();
        if (!$payment) { throw new RuntimeException('The payment was not found.', 404); }
        return $payment;
    }

    public function checkout(MobilePayment $payment): array
    {
        $this->gateway->assertTransactionsAllowed();
        if (!config('mobile_payments.enabled')) { throw new RuntimeException('Online checkout is unavailable.', 503); }
        $lock = Cache::lock('mobile-payment-start:' . $payment->id, 30);
        if (!$lock->get()) { throw new RuntimeException('Checkout is already opening.', 409); }
        try {
            $payment->refresh();
            if ($payment->status !== 'pending' || $payment->started_at || !$payment->expires_at->isFuture()) {
                throw new RuntimeException('Checkout has already started or expired. Return to the app and check payment status.', 409);
            }
            if (!$this->hasReservation($payment)) { throw new RuntimeException('This reservation is no longer available.', 409); }
            $form = $this->gateway->checkout($payment);
            $payment->refresh();
            if ($payment->status !== 'pending' || !$payment->expires_at->isFuture()) {
                throw new RuntimeException('Checkout expired while connecting. Return to the app and check payment status.', 409);
            }
            $payment->update(['started_at' => now()]);
            return $form;
        } finally { $lock->release(); }
    }

    public function refresh(MobilePayment $payment): MobilePayment
    {
        $this->gateway->assertTransactionsAllowed();
        if ($payment->environment !== config('mobile_payments.environment')) {
            throw new RuntimeException('This payment belongs to a different payment environment.', 409);
        }
        if (in_array($payment->status, ['paid', 'review_required'], true)) { return $payment; }
        $lock = Cache::lock('mobile-payment-check:' . $payment->id, 30);
        if (!$lock->get()) { return $payment->fresh(); }
        try {
            $payment->refresh();
            if ($payment->checked_at && $payment->checked_at->gt(now()->subSeconds(15))) { return $payment; }
            $payment->update(['checked_at' => now()]);
            // Browser callbacks only trigger this authenticated server-to-server inquiry.
            $paid = $payment->started_at ? $this->gateway->paid($payment) : false;
            return $this->settle($payment, $paid);
        } finally { $lock->release(); }
    }

    private function hasReservation(MobilePayment $payment): bool
    {
        return Ticket::where('company_id', $payment->company_id)->where('invoice_id', $payment->invoice_id)
            ->where('type', 'advance booking')->count() === $payment->ticket_count;
    }

    private function settle(MobilePayment $payment, bool $paid): MobilePayment
    {
        $invoice = Invoice::where('company_id', $payment->company_id)->find($payment->invoice_id);
        if (!$invoice) { throw new RuntimeException('The booking was not found.', 404); }
        $lock = Cache::lock('stayLock:' . $invoice->schedule_id, 15);
        if (!$lock->get()) { throw new RuntimeException('The booking is being updated. Please check again.', 409); }
        try {
            return DB::transaction(function () use ($payment, $paid) {
                $payment = MobilePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if (in_array($payment->status, ['paid', 'review_required'], true)) { return $payment; }
                $tickets = Ticket::withTrashed()->where('company_id', $payment->company_id)
                    ->where('invoice_id', $payment->invoice_id)->lockForUpdate()->get();
                $active = $tickets->filter(function ($ticket) { return !$ticket->trashed() && $ticket->type === 'advance booking'; });
                $amount = (int) round($active->sum(function ($ticket) { return $ticket->seat_fare - $ticket->discount; }) * 100);
                if ($paid) {
                    // Never revive released seats or overwrite a staff cancellation/reschedule.
                    $confirm = $payment->status === 'pending' && $payment->expires_at->isFuture()
                        && $active->count() === $payment->ticket_count && $amount === $payment->amount_minor;
                    if ($confirm) {
                        Ticket::whereIn('id', $active->pluck('id'))->update([
                            'type' => 'booked', 'transaction_id' => $payment->transaction_reference,
                            'booked_time' => now(), 'updated_by' => config('mobile.booking_user_id'),
                        ]);
                        TicketIsPartial::whereIn('ticket_id', $active->pluck('id'))->update(['type' => 'booked']);
                    } else { $this->release($payment, $active); }
                    $payment->update(['status' => $confirm ? 'paid' : 'review_required', 'paid_at' => now()]);
                    $this->audit($payment, $confirm ? 'Payment verified; tickets confirmed' : 'Payment received; head office review required');
                } elseif ($payment->status === 'pending' && !$payment->expires_at->isFuture()) {
                    $this->release($payment, $active);
                    $payment->update(['status' => 'expired']);
                    $this->audit($payment, 'Payment reservation expired');
                }
                return $payment;
            });
        } finally { $lock->release(); }
    }

    private function release(MobilePayment $payment, $tickets): void
    {
        $points = (int) $tickets->sum('points_usage');
        if ($points > 0 && $payment->wallet_card_id) {
            $card = CardAssign::withTrashed()->whereKey($payment->wallet_card_id)
                ->where('company_id', $payment->company_id)->lockForUpdate()->first();
            if (!$card) { throw new RuntimeException('The loyalty point return needs head office review.', 409); }
            $card->increment('starting_points', $points);
        }
        foreach ($tickets as $ticket) {
            BookingCancel::create(['company_id' => $payment->company_id, 'ticket_id' => $ticket->id,
                'percentage' => 0, 'reason' => 'Mobile payment reservation expired', 'type' => $ticket->type,
                'added_by' => config('mobile.booking_user_id')]);
            $ticket->update(['type' => 'canceled', 'points_usage' => 0, 'updated_by' => config('mobile.booking_user_id')]);
            TicketIsPartial::where('ticket_id', $ticket->id)->delete();
            $ticket->delete();
        }
    }

    private function audit(MobilePayment $payment, string $message): void
    {
        ActivityLog::create(['activity_by' => config('mobile.booking_user_id'), 'company_id' => $payment->company_id,
            'requested_host' => request()->ip() ?: '0:0',
            'message' => $message . ' | invoice id: ' . $payment->invoice_id . ' | payment: ' . $payment->public_id]);
    }
}
