<?php

namespace App\Payments;

final readonly class MerchantStatus
{
    public function __construct(
        public string $merchantId,
        public bool $paymentsReceivable,
        public bool $emailConfirmed,
    ) {}
}
