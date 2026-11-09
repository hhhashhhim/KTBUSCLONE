<?php

namespace App\Services\Mobile;

use App\Models\ActivityLog;
use App\Models\FareTable;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\MobilePayment;
use App\Models\PassengerAccount;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MobileLoyaltyEarningService
{
    /** Called only inside verified settlement, with payment and tickets locked. */
    public function awardPayment(MobilePayment $payment, Collection $tickets): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Loyalty earning requires the payment settlement transaction.');
        }
        if (!Schema::hasTable('mobile_loyalty_awards')) {
            throw new RuntimeException('The mobile loyalty schema needs updating.', 503);
        }
        if (DB::table('mobile_loyalty_awards')->where('payment_id', $payment->id)->exists()) {
            return;
        }

        $account = PassengerAccount::where('company_id', $payment->company_id)
            ->find($payment->passenger_account_id);
        // Credit the authenticated booker's card, not a passenger CNIC entered
        // for one of the seats. This is the same owner used by the mobile wallet.
        $card = $account && $account->customer_id
            ? app(MobileLoyaltyService::class)->lockedCardFor($account) : null;
        $category = $card ? $card->cardCategory : null;
        if ($category && (int) $category->company_id !== (int) $payment->company_id) {
            $category = null;
        }
        $amount = max(0, $payment->amount_minor / 100);
        $distance = 0;
        $rate = 0;
        $basis = 0;
        if ($category && $category->point_type === 'flatPoints') {
            $rate = (float) $category->point_flat;
            $basis = $amount;
        } elseif ($category && $category->point_type === 'distancePoints') {
            $first = $tickets->first();
            $distance = max(0, (float) FareTable::where('company_id', $payment->company_id)
                ->where('from_city_id', $first->departure_city_id)
                ->where('to_city_id', $first->destination_city_id)->orderBy('id')->value('distance_in_km'));
            $rate = (float) $category->point_distance;
            // ERP awards journey distance once per booking, not once per seat.
            $basis = $distance;
        }
        // ERP stores the balance as an integer. Make its whole-point rounding
        // explicit so SQLite tests and MySQL produce the same credit.
        $points = $rate > 0 ? (int) round($basis / $rate, 0, PHP_ROUND_HALF_UP) : 0;
        $tickets = $tickets->sortBy('id')->values();
        $allocated = 0;
        $weight = 0;
        foreach ($tickets as $index => $ticket) {
            $weight += $category && $category->point_type === 'flatPoints'
                ? max(0, (float) $ticket->seat_fare - (float) $ticket->discount) : 1;
            $denominator = $category && $category->point_type === 'flatPoints' ? $amount : $tickets->count();
            $cumulative = $index === $tickets->count() - 1 ? $points
                : ($denominator > 0 ? min($points, (int) round($points * $weight / $denominator)) : 0);
            DB::table('mobile_loyalty_awards')->insert([
                'company_id' => $payment->company_id, 'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id, 'ticket_id' => $ticket->id,
                'card_assign_id' => $category ? $card->id : null,
                'card_category_id' => $category ? $category->id : null,
                'point_type' => $category ? $category->point_type : null,
                'point_value' => max(0, $rate), 'booking_amount' => $amount, 'distance_in_km' => $distance,
                'earned_points' => $cumulative - $allocated, 'redeemed_points' => (int) $ticket->points_usage,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $allocated = $cumulative;
        }
        if ($points > 0) {
            $card->update(['starting_points' => (int) $card->starting_points + $points]);
            ActivityLog::create([
                'activity_by' => config('mobile.booking_user_id'), 'company_id' => $payment->company_id,
                'message' => "Mobile loyalty earned {$points} points | invoice {$payment->invoice_id} | card {$card->id}",
                'requested_host' => 'mobile-api',
            ]);
        }
    }

    /** Return true when this is an online mobile ticket, bypassing ERP recalculation. */
    public function reverseTicket(Ticket $ticket): bool
    {
        if (!Schema::hasTable('mobile_payments')) {
            return false;
        }
        return DB::transaction(function () use ($ticket) {
            $locked = Ticket::withTrashed()->where('company_id', $ticket->company_id)
                ->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $award = $this->awardForTicket($locked);
            $payment = MobilePayment::where('company_id', $ticket->company_id)
                ->when($award, function ($query) use ($award) { $query->whereKey($award->payment_id); },
                    function ($query) use ($locked) { $query->where('invoice_id', $locked->invoice_id); })
                ->first();
            if (!$payment) { return false; }
            if ($locked->trashed() || ($award && $award->reversed_at)) { return true; }

            $deltas = [];
            if ($award && $award->card_assign_id && $award->earned_points > 0) {
                $deltas[$award->card_assign_id] = -(int) $award->earned_points;
            }
            $redeemed = $award ? (int) $award->redeemed_points : (int) $locked->points_usage;
            if ($redeemed > 0) {
                if (!$payment->wallet_card_id) {
                    throw new RuntimeException('The loyalty point return needs head office review.', 409);
                }
                $id = $payment->wallet_card_id;
                $deltas[$id] = ($deltas[$id] ?? 0) + $redeemed;
            }
            ksort($deltas);
            foreach ($deltas as $id => $delta) {
                // Expiry/deletion after booking must not prevent a correction to
                // the original card or transfer it to a replacement card.
                $card = CardAssign::withTrashed()->where('company_id', $ticket->company_id)
                    ->whereKey($id)->lockForUpdate()->first();
                if (!$card) { throw new RuntimeException('The loyalty adjustment needs head office review.', 409); }
                $card->update(['starting_points' => (int) $card->starting_points + $delta]);
            }
            if ($award) {
                DB::table('mobile_loyalty_awards')->where('id', $award->id)
                    ->update(['reversed_at' => now(), 'updated_at' => now()]);
            }
            $locked->update(['points_usage' => 0]);
            return true;
        });
    }

    private function awardForTicket(Ticket $ticket)
    {
        if (!Schema::hasTable('mobile_loyalty_awards')) { return null; }
        $id = $ticket->id;
        $seen = [];
        while ($id && !isset($seen[$id])) {
            $seen[$id] = true;
            $award = DB::table('mobile_loyalty_awards')->where('company_id', $ticket->company_id)
                ->where('ticket_id', $id)->lockForUpdate()->first();
            if ($award) { return $award; }
            // ERP reschedules replace ticket IDs, retaining this authoritative
            // chain. Do not award again or recalculate using the replacement fare.
            if (!Schema::hasTable('ticket_reschedules')) { return null; }
            $id = DB::table('ticket_reschedules')->where('company_id', $ticket->company_id)
                ->where('new_ticket_id', $id)->whereNull('deleted_at')->value('old_ticket_id');
        }
        return null;
    }
}
