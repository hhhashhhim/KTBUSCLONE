<?php
namespace App\Services\Mobile;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Poll committed ERP data so counter cancellations and gateway callbacks use the same inbox. */
class MobileBookingNotifications
{
    public function run(): void
    {
        $company = (int) config('mobile.company_id');
        if (!Schema::hasTable('invoices') || !Schema::hasColumn('invoices', 'passenger_account_id') || !Schema::hasTable('tickets')) return;
        DB::table('mobile_notification_runtime')->insertOrIgnore(['company_id' => $company, 'started_at' => now()]);
        $runtime = DB::table('mobile_notification_runtime')->where('company_id', $company)->first();
        $invoices = DB::table('invoices')->where('company_id', $company)->whereNotNull('passenger_account_id')
            ->where('id', '>', $runtime->invoice_cursor)->orderBy('id')->limit(500)->get();
        foreach ($invoices as $invoice) $this->sync($invoice, $runtime->started_at);
        DB::table('mobile_notification_runtime')->where('company_id', $company)->update([
            'invoice_cursor' => $invoices->count() < 500 ? 0 : $invoices->last()->id]);
        // New bookings and their payment/cancellation changes must not wait behind old invoices.
        DB::table('invoices')->where('company_id', $company)->whereNotNull('passenger_account_id')
            ->where('created_at', '>=', now()->subDay())->orderBy('id')->chunkById(200, function ($rows) use ($runtime) {
                foreach ($rows as $row) $this->sync($row, $runtime->started_at);
            });
        // Departure reminders must not wait for the rolling history scan.
        $now = Carbon::now('Asia/Karachi');
        $upcoming = DB::table('tickets')->where('company_id', $company)->whereNull('deleted_at')->where('type', 'booked')
            ->whereBetween('schedule_date', [$now->toDateString(), $now->copy()->addDay()->toDateString()])->distinct()->pluck('invoice_id');
        DB::table('invoices')->where('company_id', $company)->whereNotNull('passenger_account_id')->whereIn('id', $upcoming)
            ->orderBy('id')->chunkById(200, function ($rows) use ($runtime) { foreach ($rows as $row) $this->sync($row, $runtime->started_at); });
    }
    public function sync($invoice, string $startedAt): void
    {
        $company = (int) $invoice->company_id;
        if (!DB::table('passenger_accounts')->where('company_id', $company)->where('id', $invoice->passenger_account_id)->exists()) return;
        $tickets = DB::table('tickets')->where('company_id', $company)->where('invoice_id', $invoice->id)->orderBy('id')->get();
        if ($tickets->isEmpty()) return;
        $active = $tickets->filter(function ($t) { return !$t->deleted_at && !in_array($t->type, ['canceled', 'cancelled', 'reschedule']); });
        $first = $active->first() ?: $tickets->first();
        $payment = Schema::hasTable('mobile_payments') ? DB::table('mobile_payments')->where('company_id', $company)
            ->where('passenger_account_id', $invoice->passenger_account_id)->where('invoice_id', $invoice->id)->first() : null;
        // Sandbox transactions never generate passenger notifications.
        if ($payment && ($payment->environment ?? 'live') === 'sandbox') return;
        $confirmed = $active->isNotEmpty() && $active->every(function ($t) { return $t->type === 'booked'; });
        $state = ['status' => $active->isEmpty() ? 'cancelled' : ($confirmed ? 'confirmed' : 'pending'),
            'paid' => $payment ? (bool) $payment->paid_at : $confirmed,
            'seats' => $active->map(function ($t) { return (string) $t->seat_no; })->sort()->values()->all(),
            'departure' => $first->schedule_date . ' ' . $first->schedule_time,
            'refund_recorded' => (float) $tickets->sum(function ($t) { return (float) ($t->refund_amount ?? 0); })];
        DB::transaction(function () use ($invoice, $company, $state, $startedAt) {
            DB::table('mobile_notification_booking_states')->insertOrIgnore(['invoice_id' => $invoice->id, 'company_id' => $company,
                'snapshot' => '{}', 'sequence' => 0, 'updated_at' => now()]);
            $row = DB::table('mobile_notification_booking_states')->where('invoice_id', $invoice->id)->where('company_id', $company)->lockForUpdate()->first();
            $before = json_decode($row->snapshot, true) ?: null;
            $events = [];
            $reference = 'KT-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT);
            if (!$before && $state['status'] !== 'cancelled' && Carbon::parse($invoice->created_at)->gte(Carbon::parse($startedAt))) {
                $events[] = $state['status'] === 'confirmed'
                    ? ['booking_confirmed', 'Booking confirmed', 'Your seats for ' . $reference . ' are confirmed. Open My Trips for your ticket.']
                    : ['booking_created', 'Booking received', 'Your booking ' . $reference . ' is available in My Trips. Open it to check payment and seat status.'];
            }
            if ($before) {
                if (!$before['paid'] && $state['paid']) $events[] = ['payment_received', 'Payment recorded', 'Payment has been recorded for ' . $reference . '. Open your booking for the latest ticket status.'];
                if ($before['status'] !== $state['status']) {
                    if ($state['status'] === 'confirmed') $events[] = ['booking_confirmed', 'Booking confirmed', 'Your seats for ' . $reference . ' are confirmed.'];
                    elseif ($state['status'] === 'cancelled') $events[] = ['booking_cancelled', 'Booking cancelled', 'The seats in ' . $reference . ' have been cancelled. Contact support for any refund enquiry.'];
                    else $events[] = ['booking_updated', 'Booking status updated', 'The status of ' . $reference . ' has changed. Open My Trips for details.'];
                }
                if ($state['status'] !== 'cancelled' && $before['seats'] !== $state['seats']) $events[] = ['seats_updated', 'Seats updated', 'Your seats in ' . $reference . ' have changed. Check your booking before travel.'];
                if ($state['status'] !== 'cancelled' && $before['departure'] !== $state['departure']) $events[] = ['departure_changed', 'Departure updated', 'The departure recorded for ' . $reference . ' has changed to ' . $state['departure'] . ' PKT.'];
                if ((float) $before['refund_recorded'] !== $state['refund_recorded']) $events[] = ['refund_updated', 'Refund record updated', 'The refund record for ' . $reference . ' has changed. Contact support to confirm the payment of any refund.'];
            }
            $sequence = (int) $row->sequence + ($before != $state ? 1 : 0);
            foreach ($events as [$kind, $title, $body]) MobileNotificationInbox::record($company, $invoice->passenger_account_id,
                'booking:' . $invoice->id . ':' . $sequence . ':' . $kind, $kind, $title, $body, $invoice->id);
            try { $departure = Carbon::createFromFormat('Y-m-d H:i:s', $state['departure'], 'Asia/Karachi'); } catch (\Throwable $error) { $departure = null; }
            $now = Carbon::now('Asia/Karachi');
            if ($departure && $state['status'] === 'confirmed' && $departure->gt($now) && $departure->lte($now->copy()->addMinutes(config('mobile_notifications.reminder_minutes', 120)))) {
                MobileNotificationInbox::record($company, $invoice->passenger_account_id,
                    'reminder:' . $invoice->id . ':' . $departure->timestamp, 'departure_reminder', 'Your departure is approaching',
                    $reference . ' departs at ' . $departure->format('d M, h:i A') . ' PKT. Check your ticket and arrive before departure.', $invoice->id);
            }
            DB::table('mobile_notification_booking_states')->where('invoice_id', $invoice->id)->update([
                'snapshot' => json_encode($state), 'sequence' => $sequence, 'updated_at' => now()]);
        });
    }
}
