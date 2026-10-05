<?php

namespace App\Jobs;

use App\Actions\Checkout\CompleteOrder;
use App\Actions\Seller\RefreshPayPalStatus;
use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Models\GatewayEvent;
use App\Models\Order;
use App\Models\SellerProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandlePayPalEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $eventId) {}

    public function handle(PaymentGateway $gateway, CompleteOrder $complete, RefreshPayPalStatus $refresh): void
    {
        $event = GatewayEvent::find($this->eventId);

        if (! $event || $event->processed_at) {
            return;
        }

        $resource = $event->payload['resource'] ?? [];

        match ($event->type) {
            // Buyer approved but may have closed the tab before returning to us: capture now.
            'CHECKOUT.ORDER.APPROVED' => $this->withPendingOrder($resource['id'] ?? null, fn (Order $o) => $complete->handle($o, $gateway->capture($o->gateway_ref))),

            'PAYMENT.CAPTURE.COMPLETED' => $this->withPendingOrder(
                $resource['supplementary_data']['related_ids']['order_id'] ?? null,
                fn (Order $o) => $complete->handle($o, $gateway->fetch($o->gateway_ref)),
            ),

            'PAYMENT.CAPTURE.DENIED' => $this->withPendingOrder(
                $resource['supplementary_data']['related_ids']['order_id'] ?? null,
                fn (Order $o) => $o->update(['status' => OrderStatus::Failed]),
            ),

            'MERCHANT.ONBOARDING.COMPLETED', 'MERCHANT.PARTNER-CONSENT.REVOKED' => $this->withSeller($resource['tracking_id'] ?? null, $refresh),

            default => null,
        };

        $event->update(['processed_at' => now()]);
    }

    private function withPendingOrder(?string $reference, callable $callback): void
    {
        $order = $reference ? Order::where('gateway_ref', $reference)->first() : null;

        if ($order && $order->status === OrderStatus::Pending) {
            $callback($order);
        }
    }

    private function withSeller(?string $trackingId, RefreshPayPalStatus $refresh): void
    {
        if ($trackingId && preg_match('/^seller-(\d+)$/', $trackingId, $m) && $profile = SellerProfile::find($m[1])) {
            $refresh->handle($profile);
        }
    }
}
