<?php

namespace App\Actions\Checkout;

use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewSale;
use App\Notifications\OrderPaid;
use App\Payments\GatewayOrder;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marks an order paid exactly once: records real fees, ledger entries, counters, emails.
 * Called from the PayPal return URL, the webhook, or directly for $0 orders.
 */
class CompleteOrder
{
    /** @return bool true if this call completed the order */
    public function handle(Order $order, ?GatewayOrder $remote): bool
    {
        if ($remote && ! $remote->isFullyCaptured()) {
            return false;
        }

        $completed = DB::transaction(function () use ($order, $remote) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status === OrderStatus::Paid) {
                return null;
            }

            $order->load('items');

            if ($remote && ! $this->matches($order, $remote)) {
                Log::critical('Captured amounts do not match order', ['order' => $order->uuid, 'gateway_ref' => $remote->reference]);
                $order->update(['status' => OrderStatus::Failed]);

                return null;
            }

            foreach ($order->items->groupBy('seller_id') as $sellerId => $items) {
                $unit = $remote?->units[$sellerId] ?? null;
                $gatewayFee = $unit?->gatewayFeeCents ?? 0;
                $shares = Money::allocate($gatewayFee, $items->map(fn ($i) => $i->price_cents + $i->buyer_fee_cents)->values()->all());

                foreach ($items->values() as $i => $item) {
                    $item->update([
                        'payment_fee_cents' => $shares[$i],
                        'seller_net_cents' => $item->price_cents + $item->buyer_fee_cents - $shares[$i] - $item->platform_fee_cents,
                        'gateway_capture_id' => $unit?->captureId,
                    ]);

                    LedgerEntry::firstOrCreate(
                        ['order_item_id' => $item->id, 'type' => LedgerType::Sale],
                        ['seller_id' => $item->seller_id, 'amount_cents' => $item->seller_net_cents, 'description' => $item->product_title.' ('.$item->license_name.')'],
                    );
                }
            }

            Product::whereIn('id', $order->items->pluck('product_id'))->increment('sales_count');

            if ($order->coupon_id) {
                $order->coupon()->increment('used_count');
            }

            if ($cart = Cart::where('user_id', $order->buyer_id)->first()) {
                $cart->items()->whereIn('product_id', $order->items->pluck('product_id'))->delete();
                if ($order->coupon_id && $cart->coupon_id === $order->coupon_id) {
                    $cart->update(['coupon_id' => null]);
                }
            }

            $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            return $order;
        });

        if (! $completed) {
            return false;
        }

        $completed->load('buyer', 'items.seller');
        $completed->buyer->notify(new OrderPaid($completed));
        foreach ($completed->items->groupBy('seller_id') as $items) {
            $items->first()->seller->notify(new NewSale($items));
        }

        return true;
    }

    /** Each seller unit captured exactly what we asked for (items + processing fee). */
    private function matches(Order $order, GatewayOrder $remote): bool
    {
        foreach ($order->items->groupBy('seller_id') as $sellerId => $items) {
            $expected = $items->sum('price_cents') + $items->sum('buyer_fee_cents');

            if ($expected === 0) {
                continue;
            }

            $unit = $remote->units[$sellerId] ?? null;

            if (! $unit || $unit->grossCents !== $expected) {
                return false;
            }
        }

        return true;
    }
}
