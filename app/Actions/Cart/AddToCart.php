<?php

namespace App\Actions\Cart;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class AddToCart
{
    public function __construct(private CurrentCart $carts) {}

    public function handle(Product $product, int $licenseId): void
    {
        $product->loadMissing('licensePrices', 'seller.sellerProfile');

        $error = match (true) {
            ! $product->isPublished() => __('This item is not available.'),
            $product->is_free => __('Free items don\'t need the cart.'),
            $product->priceFor($licenseId) === null => __('Choose a license.'),
            auth()->id() === $product->seller_id => __('You can\'t buy your own item.'),
            ! $product->seller->sellerProfile->canReceivePayments() => __('This seller can\'t accept payments yet.'),
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['license' => $error]);
        }

        // One line per product: choosing another license replaces the previous choice.
        $this->carts->get(create: true)->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['license_id' => $licenseId],
        );
    }
}
