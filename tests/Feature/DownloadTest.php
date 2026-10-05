<?php

use App\Enums\OrderStatus;
use App\Enums\TrafficSource;
use App\Models\Download;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(fn () => Storage::fake('products'));

function purchasedItem(User $buyer, OrderStatus $status = OrderStatus::Paid): OrderItem
{
    $product = Product::factory()->approved()->create();
    Storage::disk('products')->put('files/'.$product->id.'/kit.zip', 'ZIPDATA');
    $product->files()->create(['path' => 'files/'.$product->id.'/kit.zip', 'original_name' => 'kit.zip', 'size' => 7, 'version' => '1.0']);
    $license = License::factory()->create();

    $order = Order::create([
        'buyer_id' => $buyer->id, 'subtotal_cents' => 1000, 'discount_cents' => 0, 'buyer_fee_cents' => 78,
        'total_cents' => 1078, 'status' => $status, 'gateway' => 'paypal', 'paid_at' => now(),
    ]);

    return $order->items()->create([
        'product_id' => $product->id, 'license_id' => $license->id, 'seller_id' => $product->seller_id,
        'product_title' => $product->title, 'license_name' => $license->name, 'list_price_cents' => 1000,
        'price_cents' => 1000, 'attribution' => TrafficSource::Marketplace, 'purchase_code' => (string) Str::uuid(),
    ]);
}

test('buyer gets a 15-minute signed link, download is logged and streams the file', function () {
    $buyer = buyer();
    $item = purchasedItem($buyer);

    $this->actingAs($buyer)->get(route('downloads.index'))->assertOk()->assertSee($item->purchase_code);

    $link = $this->actingAs($buyer)->get(route('downloads.file', $item))->assertRedirect()->headers->get('Location');
    expect($link)->toContain('signature=')->toContain('expires=');
    expect(Download::where('order_item_id', $item->id)->where('user_id', $buyer->id)->count())->toBe(1);

    $response = $this->actingAs($buyer)->get($link)->assertOk();
    expect($response->streamedContent())->toBe('ZIPDATA');

    // Expired after 15 minutes.
    $this->travel(16)->minutes();
    $this->actingAs($buyer)->get($link)->assertForbidden();
});

test('other users, unpaid orders and unsigned links cannot download', function () {
    $buyer = buyer();
    $item = purchasedItem($buyer);

    $this->actingAs(buyer())->get(route('downloads.file', $item))->assertForbidden();
    $this->actingAs($buyer)->get(route('downloads.stream', ['orderItem' => $item->id, 'file' => $item->product->latestFile->id]))->assertForbidden();

    $pending = purchasedItem($buyer, OrderStatus::Pending);
    $this->actingAs($buyer)->get(route('downloads.file', $pending))->assertForbidden();
});

test('guests are sent to login', function () {
    $item = purchasedItem(buyer());

    $this->get(route('downloads.file', $item))->assertRedirect(route('login'));
});

test('a signed link is bound to its buyer', function () {
    $buyer = buyer();
    $item = purchasedItem($buyer);
    $link = $this->actingAs($buyer)->get(route('downloads.file', $item))->headers->get('Location');

    $this->actingAs(buyer())->get($link)->assertForbidden();
});

test('buyer can download the license certificate PDF', function () {
    $buyer = buyer();
    $item = purchasedItem($buyer);

    $this->actingAs($buyer)->get(route('downloads.license', $item))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(buyer())->get(route('downloads.license', $item))->assertForbidden();
});
