<?php

namespace App\Actions\Checkout;

use App\Enums\CouponType;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Fees;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Prices a cart: list price − seller coupon = price; buyer pays a processing fee per seller.
 * Bundles expand into one line per product, the bundle price split by each item's separate price.
 */
class QuoteCart
{
    public function handle(?Cart $cart): Quote
    {
        if (! $cart) {
            return new Quote(collect(), collect(), null, null);
        }

        $productRelations = ['cover', 'licensePrices', 'seller.sellerProfile'];

        $cart->loadMissing([
            'coupon',
            'items.license',
            'items.product' => fn ($q) => $q->withTrashed()->with($productRelations),
            'items.bundle' => fn ($q) => $q->withTrashed()->with(['license', 'seller.sellerProfile', 'products' => fn ($p) => $p->with($productRelations)]),
        ]);

        $available = collect();
        $unavailable = collect();

        foreach ($cart->items as $item) {
            [$lines, $reason] = $item->bundle_id ? $this->bundleLines($item) : $this->productLine($item);

            $reason
                ? $unavailable->push(['cart_item_id' => $item->id, 'product' => $item->product, 'bundle' => $item->bundle, 'reason' => $reason])
                : $available->push(...$lines);
        }

        // An item bought inside a bundle shouldn't also be bought on its own.
        $inBundles = $available->whereNotNull('bundle')->pluck('product.id')->all();
        [$duplicates, $available] = $available->partition(fn ($l) => ! $l['bundle'] && in_array($l['product']->id, $inBundles, true));
        foreach ($duplicates as $line) {
            $unavailable->push(['reason' => __('Already included in a bundle in your cart')] + $line);
        }

        [$coupon, $couponError] = $this->coupon($cart, $available);

        $groups = $available->groupBy('seller_id')->map(function ($lines, $sellerId) use ($coupon) {
            $lines = $lines->values();

            if ($coupon && $coupon->seller_id === (int) $sellerId) {
                $lines = $this->applyCoupon($coupon, $lines);
            }

            $itemsTotal = $lines->sum('price');
            $buyerFee = Fees::processingFee($itemsTotal);

            // Spread the processing fee over lines so each order item records its share.
            $shares = Money::allocate($buyerFee, $lines->pluck('price')->all());
            $lines = $lines->map(fn ($line, $i) => ['buyer_fee' => $shares[$i]] + $line);

            return [
                'seller_id' => (int) $sellerId,
                'profile' => $lines->first()['product']->seller->sellerProfile,
                'lines' => $lines,
                'items_total' => $itemsTotal,
                'buyer_fee' => $buyerFee,
                'total' => $itemsTotal + $buyerFee,
            ];
        })->values();

        return new Quote($groups, $unavailable, $coupon, $couponError);
    }

    private function productLine(CartItem $item): array
    {
        $product = $item->product;
        $price = $product?->priceFor((int) $item->license_id);

        $reason = match (true) {
            ! $product || $product->trashed() || ! $product->isPublished() => __('No longer available'),
            $product->is_free || $price === null => __('License no longer offered'),
            ! $product->seller->sellerProfile->canReceivePayments() => __('Seller can\'t accept payments right now'),
            auth()->id() === $product->seller_id => __('Your own item'),
            default => null,
        };

        return [[$this->line($item, $product, $item->license, (int) $price, null)], $reason];
    }

    private function bundleLines(CartItem $item): array
    {
        $bundle = $item->bundle;

        $reason = match (true) {
            ! $bundle || ! $bundle->isAvailable() => __('Bundle no longer available'),
            ! $bundle->seller->sellerProfile->canReceivePayments() => __('Seller can\'t accept payments right now'),
            auth()->id() === $bundle->seller_id => __('Your own bundle'),
            default => null,
        };

        if ($reason) {
            return [[], $reason];
        }

        $products = $bundle->products->values();
        $shares = Money::allocate($bundle->price_cents, $products->map(fn ($p) => $p->priceFor($bundle->license_id) ?? $p->min_price_cents)->all());

        return [$products->map(fn ($p, $i) => $this->line($item, $p, $bundle->license, $shares[$i], $bundle))->all(), null];
    }

    private function line(CartItem $item, $product, $license, int $price, ?Bundle $bundle): array
    {
        return [
            'cart_item_id' => $item->id,
            'product' => $product,
            'license' => $license,
            'bundle' => $bundle,
            'seller_id' => $product?->seller_id,
            'list_price' => $price,
            'discount' => 0,
            'price' => $price,
            'buyer_fee' => 0,
            'reason' => null,
        ];
    }

    private function coupon(Cart $cart, Collection $available): array
    {
        $coupon = $cart->coupon;

        if (! $coupon) {
            return [null, null];
        }

        if (! $coupon->isUsable()) {
            return [null, __('This coupon has expired or reached its limit.')];
        }

        if (! $available->contains('seller_id', $coupon->seller_id)) {
            return [null, __('This coupon doesn\'t apply to items in your cart.')];
        }

        return [$coupon, null];
    }

    private function applyCoupon($coupon, Collection $lines): Collection
    {
        if ($coupon->type === CouponType::Percent) {
            $percent = min(100, $coupon->value);

            return $lines->map(function ($line) use ($percent) {
                $discount = intdiv($line['list_price'] * $percent + 50, 100);

                return ['discount' => $discount, 'price' => $line['list_price'] - $discount] + $line;
            });
        }

        $off = min($coupon->value, $lines->sum('list_price'));
        $shares = Money::allocate($off, $lines->pluck('list_price')->all());

        return $lines->map(fn ($line, $i) => ['discount' => $shares[$i], 'price' => $line['list_price'] - $shares[$i]] + $line);
    }
}
