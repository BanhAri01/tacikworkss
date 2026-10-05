<?php

namespace App\Payments;

/** One seller's part of a captured order, as reported by the gateway. */
final readonly class CapturedUnit
{
    public function __construct(
        public int $sellerId,
        public ?string $captureId,
        public string $status,          // COMPLETED, PENDING, DECLINED, ...
        public int $grossCents,
        public int $gatewayFeeCents,
        public int $platformFeeCents,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }
}
