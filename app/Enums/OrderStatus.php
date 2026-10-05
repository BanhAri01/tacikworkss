<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';     // created, waiting for buyer approval / capture
    case Paid = 'paid';
    case Cancelled = 'cancelled'; // buyer cancelled at PayPal
    case Failed = 'failed';
    case Refunded = 'refunded';
}
