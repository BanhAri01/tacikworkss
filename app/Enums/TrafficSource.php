<?php

namespace App\Enums;

use App\Models\Setting;

/** Who brought the buyer; decides the platform fee rate on an order item. */
enum TrafficSource: string
{
    case Own = 'own';                 // seller's storefront or ?ref= link
    case Marketplace = 'marketplace';

    public function feeBps(): int
    {
        return match ($this) {
            self::Own => Setting::int('fee_own_bps'),
            self::Marketplace => Setting::int('fee_marketplace_bps'),
        };
    }
}
