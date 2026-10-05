<?php

namespace App\Actions\Admin;

use App\Actions\Community\SubmitReview;
use App\Contracts\PaymentGateway;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Models\LedgerEntry;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Refunds one order item: the money goes back from the seller's PayPal (with our fee returned),
 * the sale is reversed in the ledger and the buyer loses download/license access.
 */
class RefundOrderItem
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(OrderItem $item): void
    {
        $item->loadMissing('order', 'seller.sellerProfile', 'product');

        if ($item->refunded_at) {
            return;
        }

        if ($item->order->status !== OrderStatus::Paid) {
            throw ValidationException::withMessages(['refund' => __('Only paid orders can be refunded.')]);
        }

        $amount = $item->price_cents + $item->buyer_fee_cents;
        $refundRef = null;

        if ($amount > 0) {
            $merchantId = $item->seller->sellerProfile?->paypal_merchant_id;

            if (! $item->gateway_capture_id || ! $merchantId) {
                throw ValidationException::withMessages(['refund' => __('No PayPal capture found for this item.')]);
            }

            $refundRef = $this->gateway->refund($item->gateway_capture_id, $merchantId, $amount, $item->platform_fee_cents, 'refund-item-'.$item->id);
        }

        DB::transaction(function () use ($item, $refundRef) {
            $item->update(['refunded_at' => now(), 'refund_ref' => $refundRef]);

            LedgerEntry::firstOrCreate(
                ['order_item_id' => $item->id, 'type' => LedgerType::Refund],
                ['seller_id' => $item->seller_id, 'amount_cents' => -$item->seller_net_cents, 'description' => 'Refund: '.$item->product_title],
            );

            if ($item->product && $item->product->sales_count > 0) {
                $item->product->decrement('sales_count');
            }

            // A refunded purchase is no longer a verified review.
            if (Review::where('order_item_id', $item->id)->delete() && $item->product) {
                SubmitReview::refreshRating($item->product);
            }

            if ($item->order->items()->whereNull('refunded_at')->doesntExist()) {
                $item->order->update(['status' => OrderStatus::Refunded]);
            }
        });
    }
}
