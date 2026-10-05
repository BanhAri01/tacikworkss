<?php

namespace App\Actions\Checkout;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/** Priced cart. All amounts in cents. */
final class Quote
{
    /**
     * @param  Collection<int, array{seller_id: int, profile: \App\Models\SellerProfile, lines: Collection, items_total: int, buyer_fee: int, total: int}>  $groups
     * @param  Collection<int, array>  $unavailable  lines that can't be bought (removed product, seller not connected, ...)
     */
    public function __construct(
        public Collection $groups,
        public Collection $unavailable,
        public ?Coupon $coupon,
        public ?string $couponError,
    ) {}

    public function subtotal(): int
    {
        return $this->groups->sum(fn ($g) => $g['lines']->sum('list_price'));
    }

    public function discount(): int
    {
        return $this->groups->sum(fn ($g) => $g['lines']->sum('discount'));
    }

    public function buyerFee(): int
    {
        return $this->groups->sum('buyer_fee');
    }

    public function total(): int
    {
        return $this->groups->sum('total');
    }

    public function isEmpty(): bool
    {
        return $this->groups->isEmpty();
    }

    /** @return Collection<int, array> */
    public function lines(): Collection
    {
        return $this->groups->flatMap(fn ($g) => $g['lines']);
    }
}
