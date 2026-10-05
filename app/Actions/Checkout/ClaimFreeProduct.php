<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\TrafficSource;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Free items: logged-in users get a $0 order so downloads and licenses work the same way. */
class ClaimFreeProduct
{
    public function handle(User $user, Product $product): OrderItem
    {
        if (! $product->is_free || ! $product->isPublished()) {
            throw ValidationException::withMessages(['license' => __('This item is not available for free.')]);
        }

        $existing = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id)->where('status', OrderStatus::Paid))
            ->first();

        if ($existing) {
            return $existing;
        }

        // Free downloads come with the most basic license.
        $license = License::orderBy('sort')->firstOrFail();

        return DB::transaction(function () use ($user, $product, $license) {
            $order = Order::create([
                'buyer_id' => $user->id,
                'subtotal_cents' => 0,
                'discount_cents' => 0,
                'buyer_fee_cents' => 0,
                'total_cents' => 0,
                'status' => OrderStatus::Paid,
                'gateway' => 'free',
                'paid_at' => now(),
            ]);

            $product->increment('sales_count');

            return $order->items()->create([
                'product_id' => $product->id,
                'license_id' => $license->id,
                'seller_id' => $product->seller_id,
                'product_title' => $product->title,
                'license_name' => $license->name,
                'list_price_cents' => 0,
                'price_cents' => 0,
                'attribution' => TrafficSource::Marketplace,
                'purchase_code' => (string) Str::uuid(),
            ]);
        });
    }
}
