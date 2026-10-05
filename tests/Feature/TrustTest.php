<?php

use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use App\Models\Setting;
use App\Support\Fees;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    configurePayPal();
    Notification::fake();
});

test('refund goes through the seller\'s capture, returns our fee and revokes access', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    $order = payCart($buyer);
    $item = $order->items->sole();
    Review::create(['order_item_id' => $item->id, 'product_id' => $product->id, 'user_id' => $buyer->id, 'rating' => 1]);

    $this->actingAs($buyer)->post(route('admin.orders.refund', [$order, $item]))->assertForbidden();
    $this->actingAs(admin())->post(route('admin.orders.refund', [$order, $item]))->assertRedirect();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/payments/captures/CAP-'.$product->seller_id.'/refund')
        && $r['amount']['value'] === '10.78'
        && $r['payment_instruction']['platform_fees'][0]['amount']['value'] === '3.00'
        && $r->hasHeader('PayPal-Auth-Assertion'));

    $item->refresh();
    expect($item->refunded_at)->not->toBeNull()
        ->and($item->refund_ref)->toBe('REFUND-1')
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and(LedgerEntry::where('type', LedgerType::Refund)->sole()->amount_cents)->toBe(-$item->seller_net_cents)
        ->and(Review::count())->toBe(0)
        ->and($product->fresh()->sales_count)->toBe(0);

    $this->actingAs($buyer)->get(route('downloads.file', $item))->assertForbidden();
    $this->getJson(route('api.licenses.show', $item->purchase_code))->assertJson(['valid' => false, 'status' => 'revoked']);

    // Refunding twice is a no-op.
    $this->actingAs(admin())->post(route('admin.orders.refund', [$order, $item]));
    expect(LedgerEntry::where('type', LedgerType::Refund)->count())->toBe(1);
});

test('suspended users are logged out and their shop disappears', function () {
    $product = connectedProduct(1000);
    $seller = $product->seller;

    $this->actingAs(admin())->post(route('admin.users.suspend', $seller), ['reason' => 'Fraud'])->assertRedirect();

    expect($seller->fresh()->isSuspended())->toBeTrue()
        ->and($seller->sellerProfile->fresh()->status)->toBe(SellerStatus::Suspended);

    auth()->logout();
    $this->get(route('products.show', $product))->assertNotFound();
    $this->actingAs($seller->fresh())->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('admins cannot be suspended and only admins reach admin tools', function () {
    $this->actingAs(admin())->post(route('admin.users.suspend', admin()), ['reason' => 'x'])->assertForbidden();

    foreach (['admin.orders.index', 'admin.users.index', 'admin.reports.index', 'admin.settings.edit', 'admin.affiliates.index'] as $route) {
        $this->actingAs(buyer())->get(route($route))->assertForbidden();
        $this->actingAs(admin())->get(route($route))->assertOk();
    }
});

test('copyright report goes to the admin queue and takedown unpublishes the item', function () {
    $product = Product::factory()->approved()->priced(1000)->create();
    $reporter = buyer();

    $this->actingAs($reporter)->post(route('reports.store', $product), ['reason' => 'copyright', 'details' => 'short'])->assertSessionHasErrors(['details', 'original_url']);
    $this->actingAs($reporter)->post(route('reports.store', $product), [
        'reason' => 'copyright', 'details' => 'This is my illustration from 2021, copied without permission.', 'original_url' => 'https://behance.net/me/work',
    ])->assertRedirect(route('products.show', $product));

    $report = Report::sole();
    $this->actingAs(admin())->patch(route('admin.reports.update', $report), ['action' => 'takedown', 'admin_note' => 'Confirmed'])->assertRedirect();

    expect($report->fresh()->status)->toBe('resolved')
        ->and($product->fresh()->status)->toBe(ProductStatus::Rejected);
    auth()->logout();
    $this->get(route('products.show', $product))->assertNotFound();
});

test('settings change fees used at checkout', function () {
    $this->actingAs(admin())->put(route('admin.settings.update'), [
        'fee_own' => '5', 'fee_marketplace' => '25', 'processing_fee' => '3.9', 'processing_fee_fixed' => '0.49',
        'affiliate' => '5', 'affiliate_payout_min' => '50', 'free_of_the_week_product_id' => '',
    ])->assertSessionHasNoErrors();

    expect(Setting::int('fee_marketplace_bps'))->toBe(2500)
        ->and(Setting::int('processing_fee_bps'))->toBe(390)
        ->and(Setting::int('processing_fee_fixed_cents'))->toBe(49)
        ->and(Fees::processingFee(1000))->toBe(92); // ceil(1049 / 0.961) − 1000

    $this->actingAs(admin())->put(route('admin.settings.update'), [
        'fee_own' => '95', 'fee_marketplace' => '25', 'processing_fee' => '3.9', 'processing_fee_fixed' => '0.49', 'affiliate' => '5', 'affiliate_payout_min' => '50',
    ])->assertSessionHasErrors('fee_own');
});

test('affiliate payouts are marked paid by admin', function () {
    $affiliate = buyer();
    $product = connectedProduct(10000);
    $buyer = buyer();
    addToCart($buyer, $product);
    fakePayPal(null);
    $this->actingAs($buyer)->withCookie('dm_aff', (string) $affiliate->id)->post(route('checkout.start'));
    $this->actingAs($buyer)->get(route('checkout.return', ['token' => 'PP-ORDER-1']));

    $this->actingAs(admin())->get(route('admin.affiliates.index'))->assertOk()->assertSee('$10.00');
    $this->actingAs(admin())->post(route('admin.affiliates.paid', $affiliate))->assertRedirect();

    expect(\App\Models\OrderItem::sole()->affiliate_paid_at)->not->toBeNull();
});

test('custom error pages render', function () {
    $this->get('/item/does-not-exist')->assertNotFound()->assertSee('Page not found');
});
