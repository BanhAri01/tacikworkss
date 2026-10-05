<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\SellerProfile;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * PayPal Commerce Platform (multiparty, Orders v2).
 * Each seller is a purchase unit with `payee` = seller merchant id; our fee goes in `platform_fees`.
 */
class PayPalGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paypal';
    }

    public function createCheckout(Order $order, string $returnUrl, string $cancelUrl): CheckoutSession
    {
        $order->loadMissing('items.seller.sellerProfile');

        $units = $order->items
            ->filter(fn ($item) => $item->price_cents + $item->buyer_fee_cents > 0)
            ->groupBy('seller_id')
            ->map(function ($items, $sellerId) use ($order) {
                $profile = $items->first()->seller->sellerProfile;
                $itemTotal = $items->sum('price_cents');
                $handling = $items->sum('buyer_fee_cents');
                $platformFee = $items->sum('platform_fee_cents');

                $unit = [
                    'reference_id' => 'seller-'.$sellerId,
                    'custom_id' => $order->uuid,
                    'invoice_id' => $order->uuid.'-'.$sellerId,
                    'description' => Str::limit(config('app.name').' order '.$order->uuid, 127, ''),
                    'payee' => ['merchant_id' => $profile->paypal_merchant_id],
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => Money::toDecimal($itemTotal + $handling),
                        'breakdown' => [
                            'item_total' => ['currency_code' => 'USD', 'value' => Money::toDecimal($itemTotal)],
                            'handling' => ['currency_code' => 'USD', 'value' => Money::toDecimal($handling)],
                        ],
                    ],
                    'items' => $items->map(fn ($item) => [
                        'name' => Str::limit($item->product_title.' — '.$item->license_name, 127, ''),
                        'quantity' => '1',
                        'sku' => (string) $item->product_id,
                        'category' => 'DIGITAL_GOODS',
                        'unit_amount' => ['currency_code' => 'USD', 'value' => Money::toDecimal($item->price_cents)],
                    ])->values()->all(),
                ];

                if ($platformFee > 0) {
                    $unit['payment_instruction'] = [
                        'disbursement_mode' => 'INSTANT',
                        'platform_fees' => [['amount' => ['currency_code' => 'USD', 'value' => Money::toDecimal($platformFee)]]],
                    ];
                }

                return $unit;
            })
            ->values()
            ->all();

        $response = $this->api()
            ->withHeaders(['PayPal-Request-Id' => 'create-'.$order->uuid])
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => $units,
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name' => Str::limit(config('app.name'), 127, ''),
                            'shipping_preference' => 'NO_SHIPPING',
                            'user_action' => 'PAY_NOW',
                            'return_url' => $returnUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ],
                ],
            ]);

        $data = $this->json($response, 'create order');
        $approve = collect($data['links'] ?? [])->firstWhere('rel', 'payer-action')['href']
            ?? collect($data['links'] ?? [])->firstWhere('rel', 'approve')['href']
            ?? null;

        if (! $approve) {
            throw new PaymentGatewayException('PayPal did not return an approval link.');
        }

        return new CheckoutSession($data['id'], $approve);
    }

    public function capture(string $reference): GatewayOrder
    {
        $response = $this->api()
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$reference])
            ->withBody('{}', 'application/json')
            ->post('/v2/checkout/orders/'.rawurlencode($reference).'/capture');

        // Captured earlier (e.g. by the webhook): just read the current state.
        if ($response->status() === 422 && str_contains($response->body(), 'ORDER_ALREADY_CAPTURED')) {
            return $this->fetch($reference);
        }

        return $this->toGatewayOrder($this->json($response, 'capture order'));
    }

    public function fetch(string $reference): GatewayOrder
    {
        return $this->toGatewayOrder($this->json(
            $this->api()->get('/v2/checkout/orders/'.rawurlencode($reference)),
            'get order',
        ));
    }

    public function verifyWebhook(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');
        $event = json_decode($request->getContent(), true);

        if (! $webhookId || ! is_array($event)) {
            return false;
        }

        $response = $this->api()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    public function refund(string $captureId, string $sellerMerchantId, int $amountCents, int $platformFeeCents, string $idempotencyKey): string
    {
        $body = ['amount' => ['currency_code' => 'USD', 'value' => Money::toDecimal($amountCents)]];

        if ($platformFeeCents > 0) {
            $body['payment_instruction'] = [
                'platform_fees' => [['amount' => ['currency_code' => 'USD', 'value' => Money::toDecimal($platformFeeCents)]]],
            ];
        }

        // The capture belongs to the seller: act on their behalf with an (unsigned) auth assertion.
        $assertion = $this->base64Url(json_encode(['alg' => 'none'])).'.'
            .$this->base64Url(json_encode(['iss' => config('services.paypal.client_id'), 'payer_id' => $sellerMerchantId])).'.';

        $response = $this->api()
            ->withHeaders(['PayPal-Request-Id' => $idempotencyKey, 'PayPal-Auth-Assertion' => $assertion])
            ->post('/v2/payments/captures/'.rawurlencode($captureId).'/refund', $body);

        return (string) ($this->json($response, 'refund')['id'] ?? '');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public function onboardingUrl(SellerProfile $profile, string $returnUrl): string
    {
        $response = $this->api()->post('/v2/customer/partner-referrals', [
            'tracking_id' => $this->trackingId($profile),
            'email' => $profile->paypal_email,
            'operations' => [[
                'operation' => 'API_INTEGRATION',
                'api_integration_preference' => [
                    'rest_api_integration' => [
                        'integration_method' => 'PAYPAL',
                        'integration_type' => 'THIRD_PARTY',
                        'third_party_details' => ['features' => ['PAYMENT', 'REFUND', 'PARTNER_FEE']],
                    ],
                ],
            ]],
            'products' => ['EXPRESS_CHECKOUT'],
            'legal_consents' => [['type' => 'SHARE_DATA_CONSENT', 'granted' => true]],
            'partner_config_override' => ['return_url' => $returnUrl],
        ]);

        $url = collect($this->json($response, 'partner referral')['links'] ?? [])->firstWhere('rel', 'action_url')['href'] ?? null;

        if (! $url) {
            throw new PaymentGatewayException('PayPal did not return an onboarding link.');
        }

        return $url;
    }

    public function merchantStatus(SellerProfile $profile): ?MerchantStatus
    {
        $partner = rawurlencode((string) config('services.paypal.partner_merchant_id'));

        // Resolve the merchant id by our own tracking id (return-URL params can be forged).
        $lookup = $this->api()->get("/v1/customer/partners/{$partner}/merchant-integrations", [
            'tracking_id' => $this->trackingId($profile),
        ]);

        if ($lookup->status() === 404) {
            return null;
        }

        $merchantId = $this->json($lookup, 'merchant lookup')['merchant_id'] ?? null;

        if (! $merchantId) {
            return null;
        }

        $details = $this->json(
            $this->api()->get("/v1/customer/partners/{$partner}/merchant-integrations/".rawurlencode($merchantId)),
            'merchant status',
        );

        return new MerchantStatus(
            merchantId: $merchantId,
            paymentsReceivable: (bool) ($details['payments_receivable'] ?? false),
            emailConfirmed: (bool) ($details['primary_email_confirmed'] ?? false),
        );
    }

    private function trackingId(SellerProfile $profile): string
    {
        return 'seller-'.$profile->id;
    }

    private function toGatewayOrder(array $data): GatewayOrder
    {
        $units = [];

        foreach ($data['purchase_units'] ?? [] as $unit) {
            if (! preg_match('/^seller-(\d+)$/', $unit['reference_id'] ?? '', $m)) {
                continue;
            }

            $capture = $unit['payments']['captures'][0] ?? null;
            $breakdown = $capture['seller_receivable_breakdown'] ?? [];

            $units[(int) $m[1]] = new CapturedUnit(
                sellerId: (int) $m[1],
                captureId: $capture['id'] ?? null,
                status: $capture['status'] ?? 'NONE',
                grossCents: $this->cents($breakdown['gross_amount']['value'] ?? $capture['amount']['value'] ?? '0'),
                gatewayFeeCents: $this->cents($breakdown['paypal_fee']['value'] ?? '0'),
                platformFeeCents: collect($breakdown['platform_fees'] ?? [])->sum(fn ($f) => $this->cents($f['amount']['value'] ?? '0')),
            );
        }

        return new GatewayOrder($data['id'] ?? '', $data['status'] ?? 'UNKNOWN', $units);
    }

    private function cents(string $value): int
    {
        return Money::toCents($value);
    }

    private function api(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 500, throw: false);

        if ($bn = config('services.paypal.bn_code')) {
            $request->withHeaders(['PayPal-Partner-Attribution-Id' => $bn]);
        }

        return $request;
    }

    private function accessToken(): string
    {
        $cacheKey = 'paypal.token.'.config('services.paypal.mode').'.'.md5((string) config('services.paypal.client_id'));

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = Http::baseUrl($this->baseUrl())
            ->asForm()
            ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
            ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        $data = $this->json($response, 'oauth token');
        Cache::put($cacheKey, $data['access_token'], max(60, ($data['expires_in'] ?? 3600) - 120));

        return $data['access_token'];
    }

    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function json(Response $response, string $action): array
    {
        if ($response->failed()) {
            report(new PaymentGatewayException("PayPal {$action} failed ({$response->status()}): ".Str::limit($response->body(), 1000)));

            throw new PaymentGatewayException("PayPal {$action} failed.");
        }

        return $response->json() ?? [];
    }
}
