<?php

namespace App\Services\Mobile;

/** Mirrors ERP BookingController::selected and the unchanged seatFareIsWrong helper. */
class MobileFareCalculator
{
    public static function calculate(
        float $baseFare,
        $scheduleDiscount = null,
        $terminalDiscount = null,
        $surcharge = null
    ): array {
        // Preserve ERP integer casts and intermediate rupee rounding exactly.
        $baseFare = (int) $baseFare;
        $adjusted = $baseFare;
        if ($scheduleDiscount) {
            $adjusted = $scheduleDiscount->type === 'percentage'
                ? round($baseFare - $baseFare * ($scheduleDiscount->percentage / 100))
                : $baseFare - (int) $scheduleDiscount->flat;
        }
        if ($terminalDiscount) {
            $adjusted -= ($baseFare / 100) * (float) $terminalDiscount->discount;
        }
        if ($surcharge) {
            // ERP gives an active surcharge precedence over both discounts,
            // including an assigned surcharge whose configured amount is zero.
            $adjusted = $surcharge->type === 'percentage'
                ? round($baseFare + $baseFare * ($surcharge->percentage / 100))
                : $baseFare + $surcharge->flat;
        }
        $amount = $adjusted != $baseFare ? (float) customRound($adjusted) : (float) $baseFare;
        // Report only effective adjustments. The final rounding line also absorbs
        // any sub-paisa precision so the monetary response reconciles exactly.
        $discount = $surcharge ? 0.0 : round($baseFare - $adjusted, 2);
        $surchargeAmount = $surcharge ? round($adjusted - $baseFare, 2) : 0.0;

        return [
            'original_amount' => (float) $baseFare,
            'discount' => $discount,
            'surcharge' => $surchargeAmount,
            'rounding_adjustment' => round($amount - ($baseFare - $discount + $surchargeAmount), 2),
            'amount' => $amount,
        ];
    }
}
