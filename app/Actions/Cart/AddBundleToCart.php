<?php

namespace App\Actions\Cart;

use App\Models\Bundle;
use Illuminate\Validation\ValidationException;

class AddBundleToCart
{
    public function __construct(private CurrentCart $carts) {}

    public function handle(Bundle $bundle): void
    {
        $bundle->loadMissing('seller.sellerProfile');

        $error = match (true) {
            ! $bundle->isAvailable() => __('This bundle is not available.'),
            auth()->id() === $bundle->seller_id => __('You can\'t buy your own bundle.'),
            ! $bundle->seller->sellerProfile->canReceivePayments() => __('This seller can\'t accept payments yet.'),
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['bundle' => $error]);
        }

        $this->carts->get(create: true)->items()->firstOrCreate(['bundle_id' => $bundle->id]);
    }
}
