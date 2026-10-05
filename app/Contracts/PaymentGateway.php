<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\SellerProfile;
use App\Payments\CheckoutSession;
use App\Payments\GatewayOrder;
use App\Payments\MerchantStatus;
use Illuminate\Http\Request;

/**
 * Split-payment gateway: each seller is paid directly, the platform fee is deducted by the gateway.
 */
interface PaymentGateway
{
    public function name(): string;

    /** Create the remote order (one unit per seller) and return where to send the buyer. */
    public function createCheckout(Order $order, string $returnUrl, string $cancelUrl): CheckoutSession;

    /** Capture an approved order. Safe to call twice. */
    public function capture(string $reference): GatewayOrder;

    /** Current remote state of an order. */
    public function fetch(string $reference): GatewayOrder;

    public function verifyWebhook(Request $request): bool;

    /**
     * Refund part of a seller's capture (on the seller's behalf) and give back our platform fee share.
     *
     * @return string gateway refund id
     */
    public function refund(string $captureId, string $sellerMerchantId, int $amountCents, int $platformFeeCents, string $idempotencyKey): string;

    /** URL where a seller links their account to the platform. */
    public function onboardingUrl(SellerProfile $profile, string $returnUrl): string;

    /** Look up the seller's linked account (by our tracking id); null when not linked yet. */
    public function merchantStatus(SellerProfile $profile): ?MerchantStatus;
}
