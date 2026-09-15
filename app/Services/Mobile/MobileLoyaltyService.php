<?php

namespace App\Services\Mobile;

use App\Models\LoyaltyCard\CardAssign;
use App\Models\PassengerAccount;
use RuntimeException;

class MobileLoyaltyService
{
    public function wallet(PassengerAccount $account): array
    {
        $card = $this->cardFor($account);
        if (!$card) {
            return [
                'active' => false,
                'points_available' => 0,
                'message' => 'No active Kainat loyalty card is linked to this account.',
            ];
        }

        $category = $card->cardCategory;
        return [
            'active' => true,
            'points_available' => max(0, (int) $card->starting_points),
            'expires_at' => $card->expiry_date ? (string) $card->expiry_date : null,
            'card' => [
                'id' => $card->id,
                'category' => optional($category)->name,
            ],
            'redemption' => [
                'discount_type' => optional($category)->discount_type,
                'flat_discount_per_point' => (float) (optional($category)->flat_discount ?? 0),
                'percentage_discount_per_point' => (float) (optional($category)->percentage_discount ?? 0),
            ],
        ];
    }

    public function deductionFor(PassengerAccount $account, int $points, float $amount): array
    {
        if ($points < 1) {
            return ['points' => 0, 'amount' => 0.0];
        }

        $card = $this->cardFor($account);
        if (!$card) {
            throw new RuntimeException('No active loyalty card is linked to this account.', 422);
        }
        if ($points > (int) $card->starting_points) {
            throw new RuntimeException('You do not have enough loyalty points.', 422);
        }

        return $this->deductionForCard($card, $points, $amount);
    }

    public function deductionForCard(CardAssign $card, int $points, float $amount): array
    {
        if ($points > (int) $card->starting_points) {
            throw new RuntimeException('You do not have enough loyalty points.', 422);
        }

        $category = $card->cardCategory;
        $amount = max(0, $amount);
        $deduction = optional($category)->discount_type === 'percentage'
            ? $amount * min(100, ((float) optional($category)->percentage_discount * $points) / 100)
            : (float) optional($category)->flat_discount * $points;

        return [
            'points' => $points,
            'amount' => round(min($amount, $deduction), 2),
        ];
    }

    public function lockedCardFor(PassengerAccount $account): ?CardAssign
    {
        return CardAssign::query()
            ->with('cardCategory')
            ->where('company_id', $account->company_id)
            ->where('customer_id', $account->customer_id)
            ->whereDate('expiry_date', '>=', today())
            ->lockForUpdate()
            ->first();
    }

    private function cardFor(PassengerAccount $account): ?CardAssign
    {
        return CardAssign::query()
            ->with('cardCategory')
            ->where('company_id', $account->company_id)
            ->where('customer_id', $account->customer_id)
            ->whereDate('expiry_date', '>=', today())
            ->first();
    }
}
