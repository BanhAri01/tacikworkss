<?php

namespace App\Enums;

enum LedgerType: string
{
    case Sale = 'sale';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
