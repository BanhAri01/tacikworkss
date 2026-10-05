<?php

namespace App\Support;

use App\Models\Setting;

class Fees
{
    /**
     * Processing fee the buyer pays on top of a seller's items so the seller nets the item price
     * after PayPal's percentage + fixed fee: gross = ceil((items + fixed) / (1 − rate)).
     */
    public static function processingFee(int $itemsCents): int
    {
        if ($itemsCents <= 0) {
            return 0;
        }

        $rateBps = Setting::int('processing_fee_bps');
        $fixed = Setting::int('processing_fee_fixed_cents');

        $numerator = ($itemsCents + $fixed) * 10000;
        $denominator = 10000 - $rateBps;
        $gross = intdiv($numerator + $denominator - 1, $denominator);

        return $gross - $itemsCents;
    }
}
