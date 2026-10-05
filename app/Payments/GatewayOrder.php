<?php

namespace App\Payments;

final readonly class GatewayOrder
{
    /**
     * @param  array<int, CapturedUnit>  $units  keyed by seller id
     */
    public function __construct(
        public string $reference,
        public string $status,  // CREATED, APPROVED, COMPLETED, VOIDED, ...
        public array $units = [],
    ) {}

    /** Every seller unit has a completed capture. */
    public function isFullyCaptured(): bool
    {
        if ($this->units === []) {
            return false;
        }

        foreach ($this->units as $unit) {
            if (! $unit->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }
}
