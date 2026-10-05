<?php

namespace App\Actions\Seller;

use App\Contracts\PaymentGateway;
use App\Models\SellerProfile;

/** Syncs the seller's linked PayPal merchant account (merchant id, can receive payments). */
class RefreshPayPalStatus
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(SellerProfile $profile): SellerProfile
    {
        $status = $this->gateway->merchantStatus($profile);

        $profile->update($status ? [
            'paypal_merchant_id' => $status->merchantId,
            'paypal_payments_receivable' => $status->paymentsReceivable,
            'paypal_email_confirmed' => $status->emailConfirmed,
            'paypal_connected_at' => $profile->paypal_connected_at ?? now(),
        ] : [
            'paypal_payments_receivable' => false,
        ]);

        return $profile;
    }
}
