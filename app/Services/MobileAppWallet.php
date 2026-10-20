<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Uses the same customer-linked loyalty balance as the passenger mobile API. */
class MobileAppWallet
{
    public const MAX_POINTS = 2147483647;

    public function state($account, bool $lock = false): array
    {
        $state = ['available' => false, 'enabled' => false, 'active' => false,
            'points_available' => null, 'card_id' => null, 'expires_at' => null, 'revision' => null];
        foreach ([
            'mobile_app_configs' => ['company_id', 'features'],
            'card_assigns' => ['company_id', 'customer_id', 'starting_points', 'expiry_date', 'deleted_at', 'updated_by', 'updated_at'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table) || !Schema::hasColumns($table, $columns)) {
                return $state + ['message' => 'Wallet storage is unavailable. Install the existing mobile and loyalty-card schema.'];
            }
        }
        $state['available'] = true;
        $config = DB::table('mobile_app_configs')->where('company_id', $account->company_id);
        if ($lock) { $config->lockForUpdate(); }
        $row = $config->first();
        $features = $row ? json_decode($row->features ?? '{}', true) : [];
        // Match App Settings and the verified mobile/config default (wallet=true).
        // An explicit shared company override always takes precedence.
        $enabled = (bool) config('mobile.features.wallet', true);
        if (is_array($features) && array_key_exists('wallet', $features)) { $enabled = (bool) $features['wallet']; }
        if (!$enabled) {
            return $state + ['message' => 'The wallet feature is disabled. Enable it in App Settings to adjust points.'];
        }
        $state['enabled'] = true;
        if (!$account->customer_id || !DB::table('customers')->where('company_id', $account->company_id)->where('id', $account->customer_id)->exists()) {
            return $state + ['message' => 'This passenger has no customer record linked to a loyalty card.'];
        }
        // Match MobileLoyaltyService: customer/company, non-deleted card, expiry today or later.
        $query = DB::table('card_assigns')->where('company_id', $account->company_id)
            ->where('customer_id', $account->customer_id)->whereNull('deleted_at')->whereDate('expiry_date', '>=', today());
        if ($lock) { $query->lockForUpdate(); }
        $cards = $query->orderBy('id')->limit(2)->get();
        if ($cards->isEmpty()) {
            return $state + ['message' => 'No active loyalty card is linked. Assign or renew the customer’s card under Loyalty Card → Assign Card.'];
        }
        if ($cards->count() !== 1) {
            return $state + ['message' => 'Multiple active loyalty cards are linked. Resolve the duplicate cards under Loyalty Card before adjusting points.'];
        }
        $card = $cards->first();
        $state['active'] = true;
        $state['points_available'] = max(0, (int) $card->starting_points);
        $state['card_id'] = (int) $card->id;
        $state['expires_at'] = $card->expiry_date;
        $state['revision'] = app(MobileAppAdminData::class)->revision($card);
        return $state + ['message' => null];
    }
}
