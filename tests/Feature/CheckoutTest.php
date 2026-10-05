<?php

use App\Actions\Cart\AddToCart;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\TrafficSource;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\GatewayEvent;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\NewSale;
use App\Notifications\OrderPaid;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    configurePayPal();
    Notification::fake();
});

test('checkout sends one PayPal unit per seller with payee and platform fee', function () {
    $a = connectedProduct(1000);
    $b = connectedProduct(2000);
    $buyer = buyer();
    addToCart($buyer, $a);
    addToCart($buyer, $b);
    fakePayPal([]);

    $this->actingAs($buyer)->post(route('checkout.start'))->assertRedirect('https://paypal.test/approve');

    $order = Order::with('items')->sole();
    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->gateway_ref)->toBe('PP-ORDER-1')
        ->and($order->subtotal_cents)->toBe(3000)
        // 4.4% + 30¢ per seller: 1000 → 78, 2000 → 124
        ->and($order->buyer_fee_cents)->toBe(78 + 124)
        ->and($order->total_cents)->toBe(3202);

    $itemA = $order->items->firstWhere('product_id', $a->id);
    expect($itemA->attribution)->toBe(TrafficSource::Marketplace)
        ->and($itemA->platform_fee_cents)->toBe(300)
        ->and($itemA->buyer_fee_cents)->toBe(78);

    Http::assertSent(function (Request $request) use ($a, $b) {
        if ($request->url() !== PP.'/v2/checkout/orders') {
            return false;
        }
        $units = collect($request['purchase_units'])->keyBy('reference_id');

        return count($units) === 2
            && $units['seller-'.$a->seller_id]['payee']['merchant_id'] === $a->seller->sellerProfile->paypal_merchant_id
            && $units['seller-'.$a->seller_id]['amount']['value'] === '10.78'
            && $units['seller-'.$a->seller_id]['amount']['breakdown']['handling']['value'] === '0.78'
            && $units['seller-'.$a->seller_id]['payment_instruction']['platform_fees'][0]['amount']['value'] === '3.00'
            && $units['seller-'.$b->seller_id]['payment_instruction']['platform_fees'][0]['amount']['value'] === '6.00';
    });
});

test('seller referral cookie gives the low fee on that seller\'s items only', function () {
    $own = connectedProduct(1000);
    $other = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $own);
    addToCart($buyer, $other);
    fakePayPal([]);

    $this->actingAs($buyer)->withCookie('dm_ref', (string) $own->seller_id)->post(route('checkout.start'));

    $items = Order::sole()->items->keyBy('product_id');
    expect($items[$own->id])->attribution->toBe(TrafficSource::Own)->platform_fee_cents->toBe(100)->fee_bps->toBe(1000)
        ->and($items[$other->id])->attribution->toBe(TrafficSource::Marketplace)->platform_fee_cents->toBe(300);
});

test('capture completes the order once with real fees, ledger and emails', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    // gross 10.78, PayPal fee 0.77, platform fee 3.00
    fakePayPal([$product->seller_id => [1078, 77, 300]]);

    $this->actingAs($buyer)->post(route('checkout.start'));
    $order = Order::sole();

    $this->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']))->assertRedirect(route('checkout.success', $order));
    $this->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']))->assertRedirect(route('checkout.success', $order));

    $order->refresh();
    $item = $order->items->sole();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($item->payment_fee_cents)->toBe(77)
        ->and($item->seller_net_cents)->toBe(1000 + 78 - 77 - 300)
        ->and($item->gateway_capture_id)->toBe('CAP-'.$product->seller_id)
        ->and(LedgerEntry::sole()->amount_cents)->toBe(701)
        ->and($product->fresh()->sales_count)->toBe(1)
        ->and(Cart::where('user_id', $buyer->id)->first()->items()->count())->toBe(0);

    Notification::assertSentToTimes($buyer, OrderPaid::class, 1);
    Notification::assertSentToTimes($product->seller, NewSale::class, 1);

    $this->actingAs($buyer)->get(route('checkout.success', $order))->assertOk();
    $this->actingAs(buyer())->get(route('checkout.success', $order))->assertNotFound();
});

test('a capture whose amount differs from the order is not fulfilled', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    fakePayPal([$product->seller_id => [500, 30, 150]]);

    $this->actingAs($buyer)->post(route('checkout.start'));
    $this->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']));

    expect(Order::sole()->status)->toBe(OrderStatus::Failed)
        ->and(LedgerEntry::count())->toBe(0);
});

test('pending captures leave the order pending', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    fakePayPal([$product->seller_id => [1078, 77, 300]], status: 'PENDING');

    $this->actingAs($buyer)->post(route('checkout.start'));
    $this->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']))->assertRedirect(route('cart'));

    expect(Order::sole()->status)->toBe(OrderStatus::Pending);
});

test('buyer can cancel at PayPal', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    fakePayPal([]);

    $this->actingAs($buyer)->post(route('checkout.start'));
    $this->actingAs($buyer)->get(route('checkout.cancel', ['token' => 'PP-ORDER-1']))->assertRedirect(route('cart'));

    expect(Order::sole()->status)->toBe(OrderStatus::Cancelled);
    $this->actingAs(buyer())->get(route('checkout.cancel', ['token' => 'PP-ORDER-1']))->assertNotFound();
});

test('seller coupon discounts only that seller\'s items and fees follow the discounted price', function () {
    $product = connectedProduct(1000);
    $other = connectedProduct(1000);
    $coupon = Coupon::factory()->create(['seller_id' => $product->seller_id, 'code' => 'SAVE20', 'type' => CouponType::Percent, 'value' => 20]);
    $buyer = buyer();
    addToCart($buyer, $product);
    addToCart($buyer, $other);
    Cart::where('user_id', $buyer->id)->update(['coupon_id' => $coupon->id]);
    fakePayPal([]);

    $this->actingAs($buyer)->post(route('checkout.start'));

    $items = Order::sole()->items->keyBy('product_id');
    expect($items[$product->id])->discount_cents->toBe(200)->price_cents->toBe(800)->platform_fee_cents->toBe(240)
        ->and($items[$other->id])->discount_cents->toBe(0)->price_cents->toBe(1000);
});

test('items from sellers without connected PayPal cannot be added', function () {
    $product = Product::factory()->approved()->priced(1000)->create();

    expect(fn () => addToCart(buyer(), $product))->toThrow(ValidationException::class);
});

test('checkout requires a verified login', function () {
    $this->post(route('checkout.start'))->assertRedirect(route('login'));

    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->post(route('checkout.start'))->assertRedirect(route('verification.notice'));
});

test('webhook rejects bad signatures and processes each event once', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    fakePayPal([$product->seller_id => [1078, 77, 300]]);
    $this->actingAs($buyer)->post(route('checkout.start'));
    auth()->logout();

    $event = [
        'id' => 'WH-EVT-1',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => ['id' => 'CAP-1', 'supplementary_data' => ['related_ids' => ['order_id' => 'PP-ORDER-1']]],
    ];

    $this->postJson(route('webhooks.paypal'), $event)->assertOk();
    $this->postJson(route('webhooks.paypal'), $event)->assertOk();

    expect(GatewayEvent::count())->toBe(1)
        ->and(Order::sole()->status)->toBe(OrderStatus::Paid)
        ->and(LedgerEntry::count())->toBe(1);
});

test('webhook with an invalid signature is refused', function () {
    fakePayPal([], verify: 'FAILURE');

    $this->postJson(route('webhooks.paypal'), ['id' => 'X', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED'])->assertStatus(400);
    expect(GatewayEvent::count())->toBe(0);
});

test('free items need login and create one $0 order', function () {
    $product = Product::factory()->approved()->free()->create();
    \App\Models\License::factory()->create();

    Volt::test('add-to-cart', ['product' => $product])->call('claim')->assertRedirect(route('login'));

    $buyer = buyer();
    Volt::actingAs($buyer)->test('add-to-cart', ['product' => $product])->call('claim')->assertRedirect(route('downloads.index'));
    Volt::actingAs($buyer)->test('add-to-cart', ['product' => $product])->call('claim');

    $order = Order::sole();
    expect($order)->status->toBe(OrderStatus::Paid)->total_cents->toBe(0)->gateway->toBe('free')
        ->and($order->items()->count())->toBe(1);
});
