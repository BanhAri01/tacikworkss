<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function buyer(): App\Models\User
{
    return App\Models\User::factory()->create()->assignRole(App\Enums\UserRole::Buyer);
}

function admin(): App\Models\User
{
    return App\Models\User::factory()->create()->assignRole(App\Enums\UserRole::Admin);
}

const PP = 'https://api-m.sandbox.paypal.com';

function configurePayPal(): void
{
    config([
        'services.paypal.mode' => 'sandbox',
        'services.paypal.client_id' => 'client',
        'services.paypal.secret' => 'secret',
        'services.paypal.webhook_id' => 'WH-1',
        'services.paypal.partner_merchant_id' => 'PARTNER',
    ]);
}

/** Approved, PayPal-connected seller's published product with one license price. */
function connectedProduct(int $cents): App\Models\Product
{
    $profile = App\Models\SellerProfile::factory()->approved()->paypalConnected()->create();
    $profile->user->assignRole(App\Enums\UserRole::Seller);

    return App\Models\Product::factory()->forSeller($profile->user)->approved()->priced($cents)->create();
}

/**
 * @param  array<int, array{0:int,1:int,2:int}>|null  $units  seller_id => [gross, paypal fee, platform fee] in cents;
 *                                                            null = capture exactly what the order asked for (PayPal fee 50¢ per seller)
 */
function fakePayPal(?array $units, string $status = 'COMPLETED', string $verify = 'SUCCESS'): void
{
    $captured = function () use ($units, $status) {
        $units ??= App\Models\Order::where('gateway_ref', 'PP-ORDER-1')->with('items')->firstOrFail()
            ->items->groupBy('seller_id')
            ->map(fn ($items) => [$items->sum('price_cents') + $items->sum('buyer_fee_cents'), 50, $items->sum('platform_fee_cents')])
            ->all();

        return Illuminate\Support\Facades\Http::response(capturedOrder($units, $status), 201);
    };

    Illuminate\Support\Facades\Http::fake([
        PP.'/v1/oauth2/token' => Illuminate\Support\Facades\Http::response(['access_token' => 'TOKEN', 'expires_in' => 3600]),
        PP.'/v2/checkout/orders' => Illuminate\Support\Facades\Http::response(['id' => 'PP-ORDER-1', 'status' => 'PAYER_ACTION_REQUIRED', 'links' => [['rel' => 'payer-action', 'href' => 'https://paypal.test/approve']]], 201),
        PP.'/v2/checkout/orders/PP-ORDER-1/capture' => $captured,
        PP.'/v2/checkout/orders/PP-ORDER-1' => $captured,
        PP.'/v2/payments/captures/*' => Illuminate\Support\Facades\Http::response(['id' => 'REFUND-1', 'status' => 'COMPLETED'], 201),
        PP.'/v1/notifications/verify-webhook-signature' => Illuminate\Support\Facades\Http::response(['verification_status' => $verify]),
    ]);
}

function capturedOrder(array $units, string $status): array
{
    $dec = fn (int $c) => App\Support\Money::toDecimal($c);

    return [
        'id' => 'PP-ORDER-1',
        'status' => 'COMPLETED',
        'purchase_units' => collect($units)->map(fn ($u, $sellerId) => [
            'reference_id' => 'seller-'.$sellerId,
            'payments' => ['captures' => [[
                'id' => 'CAP-'.$sellerId,
                'status' => $status,
                'amount' => ['value' => $dec($u[0])],
                'seller_receivable_breakdown' => [
                    'gross_amount' => ['value' => $dec($u[0])],
                    'paypal_fee' => ['value' => $dec($u[1])],
                    'platform_fees' => [['amount' => ['value' => $dec($u[2])]]],
                ],
            ]]],
        ])->values()->all(),
    ];
}

function addToCart(App\Models\User $buyer, App\Models\Product $product): void
{
    test()->actingAs($buyer);
    app(App\Actions\Cart\AddToCart::class)->handle($product, $product->licensePrices->first()->license_id);
}

/** Runs a full PayPal checkout for $buyer's cart and returns the paid order. */
function payCart(App\Models\User $buyer): App\Models\Order
{
    fakePayPal(null);
    test()->actingAs($buyer)->post(route('checkout.start'));
    test()->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']));

    return App\Models\Order::latest('id')->with('items')->first();
}

/** Approved seller (PayPal not connected). */
function seller(): App\Models\User
{
    return App\Models\SellerProfile::factory()->approved()->create()->user->assignRole(App\Enums\UserRole::Seller);
}
