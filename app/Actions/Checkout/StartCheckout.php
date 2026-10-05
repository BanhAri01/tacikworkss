<?php

namespace App\Actions\Checkout;

use App\Actions\Cart\CurrentCart;
use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TrafficSource;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use App\Support\Attribution;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StartCheckout
{
    public function __construct(
        private CurrentCart $carts,
        private QuoteCart $quotes,
        private PaymentGateway $gateway,
        private CompleteOrder $complete,
    ) {}

    /**
     * Creates a pending order from the cart and returns the URL to send the buyer to.
     */
    public function handle(User $buyer): string
    {
        $cart = $this->carts->get();
        $quote = $this->quotes->handle($cart);

        $error = match (true) {
            $quote->isEmpty() => __('Your cart is empty.'),
            $quote->unavailable->isNotEmpty() => __('Remove unavailable items from your cart first.'),
            $quote->couponError !== null => $quote->couponError,
            $quote->groups->count() > config('marketplace.max_sellers_per_checkout') => __('Please check out items from at most :n sellers at a time.', ['n' => config('marketplace.max_sellers_per_checkout')]),
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['cart' => $error]);
        }

        $referrerId = Attribution::sellerId();
        $affiliateId = Attribution::affiliateId();
        $affiliateBps = Setting::int('affiliate_bps');

        $order = DB::transaction(function () use ($buyer, $quote, $referrerId, $affiliateId, $affiliateBps) {
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'coupon_id' => $quote->coupon?->id,
                'subtotal_cents' => $quote->subtotal(),
                'discount_cents' => $quote->discount(),
                'buyer_fee_cents' => $quote->buyerFee(),
                'total_cents' => $quote->total(),
                'status' => OrderStatus::Pending,
                'gateway' => $quote->total() > 0 ? $this->gateway->name() : 'free',
            ]);

            foreach ($quote->lines() as $line) {
                $source = $referrerId === $line['seller_id'] ? TrafficSource::Own : TrafficSource::Marketplace;
                $bps = $source->feeBps();
                $platformFee = Money::bps($line['price'], $bps);

                // Affiliates are paid by the platform from its own fee, only on marketplace traffic.
                $earnsAffiliate = $affiliateId && $source === TrafficSource::Marketplace
                    && ! in_array($affiliateId, [$buyer->id, $line['seller_id']], true);

                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'license_id' => $line['license']->id,
                    'bundle_id' => $line['bundle']?->id,
                    'affiliate_id' => $earnsAffiliate ? $affiliateId : null,
                    'affiliate_commission_cents' => $earnsAffiliate ? min(Money::bps($line['price'], $affiliateBps), $platformFee) : 0,
                    'seller_id' => $line['seller_id'],
                    'product_title' => $line['product']->title,
                    'license_name' => $line['license']->name,
                    'list_price_cents' => $line['list_price'],
                    'discount_cents' => $line['discount'],
                    'price_cents' => $line['price'],
                    'buyer_fee_cents' => $line['buyer_fee'],
                    'platform_fee_cents' => $platformFee,
                    // Provisional until the capture reports the real PayPal fee.
                    'seller_net_cents' => $line['price'] - $platformFee,
                    'fee_bps' => $bps,
                    'attribution' => $source,
                    'purchase_code' => (string) Str::uuid(),
                ]);
            }

            return $order;
        });

        if ($order->total_cents === 0) {
            $this->complete->handle($order, null);

            return route('checkout.success', $order);
        }

        try {
            $session = $this->gateway->createCheckout(
                $order,
                route('checkout.return'),
                route('checkout.cancel'),
            );
        } catch (PaymentGatewayException $e) {
            $order->update(['status' => OrderStatus::Failed]);

            throw ValidationException::withMessages(['cart' => __('Payment service is unavailable. Please try again in a moment.')]);
        }

        $order->update(['gateway_ref' => $session->reference]);

        return $session->approveUrl;
    }
}
