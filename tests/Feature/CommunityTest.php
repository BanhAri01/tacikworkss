<?php

use App\Enums\TrafficSource;
use App\Models\Bundle;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Notifications\NewProductFromSeller;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

beforeEach(function () {
    configurePayPal();
    Notification::fake();
});

test('only verified buyers review, once per purchase, and the average updates', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();

    Volt::actingAs($buyer)->test('product-reviews', ['productId' => $product->id])->assertDontSee('Post review');

    addToCart($buyer, $product);
    payCart($buyer);

    Volt::actingAs($buyer)->test('product-reviews', ['productId' => $product->id])
        ->set('rating', 4)->set('body', 'Great kit')->call('submit')->assertHasNoErrors();

    Volt::actingAs($buyer)->test('product-reviews', ['productId' => $product->id])
        ->assertDontSee('Post review')
        ->call('submit')->assertHasErrors('rating');

    expect(Review::count())->toBe(1)
        ->and($product->fresh())->reviews_count->toBe(1)->rating_avg->toBe(4.0);
});

test('sellers reply only to reviews of their own items', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    addToCart($buyer, $product);
    $order = payCart($buyer);
    $review = Review::create(['order_item_id' => $order->items->first()->id, 'product_id' => $product->id, 'user_id' => $buyer->id, 'rating' => 5]);

    $other = connectedProduct(1000)->seller;
    rescue(fn () => Volt::actingAs($other)->test('seller.reviews')->set("replies.{$review->id}", 'hi')->call('reply', $review->id), report: false);
    expect($review->fresh()->seller_reply)->toBeNull();

    Volt::actingAs($product->seller)->test('seller.reviews')->set("replies.{$review->id}", 'Thank you!')->call('reply', $review->id);
    expect($review->fresh()->seller_reply)->toBe('Thank you!');
});

test('favorites and private collections', function () {
    $product = connectedProduct(1000);
    $user = buyer();

    Volt::actingAs($user)->test('save-buttons', ['productId' => $product->id])
        ->call('toggleFavorite')
        ->set('newCollection', 'Logo ideas')->call('createCollection');

    expect($user->favorites()->whereKey($product->id)->exists())->toBeTrue();
    $collection = Collection::sole();
    expect($collection->products()->whereKey($product->id)->exists())->toBeTrue()->and($collection->is_public)->toBeFalse();

    $this->actingAs($user)->get(route('collections.show', $collection))->assertOk()->assertSee($product->title);
    $this->actingAs(buyer())->get(route('collections.show', $collection))->assertNotFound();

    $collection->update(['is_public' => true]);
    $this->actingAs(buyer())->get(route('collections.show', $collection))->assertOk();
    $this->actingAs($user)->get(route('favorites'))->assertOk()->assertSee($product->title);
});

test('followers are emailed when a seller publishes a new item, not on re-approval', function () {
    $product = Product::factory()->pending()->create();
    $follower = buyer();
    $follower->following()->attach($product->seller_id);

    $this->actingAs(admin())->post(route('admin.products.approve', $product->id));
    Notification::assertSentToTimes($follower, NewProductFromSeller::class, 1);

    $product->update(['status' => \App\Enums\ProductStatus::Pending]);
    $this->actingAs(admin())->post(route('admin.products.approve', $product->id));
    Notification::assertSentToTimes($follower, NewProductFromSeller::class, 1);
});

test('a bundle splits its price across items and checks out through the seller\'s PayPal', function () {
    $a = connectedProduct(1000);
    $b = Product::factory()->forSeller($a->seller)->approved()->priced(3000, $a->licensePrices->first()->license)->create();
    $bundle = Bundle::create(['seller_id' => $a->seller_id, 'license_id' => $a->licensePrices->first()->license_id, 'title' => 'Mega bundle', 'slug' => 'mega-bundle', 'price_cents' => 2000]);
    $bundle->products()->attach([$a->id, $b->id]);
    $buyer = buyer();

    $this->get(route('bundles.show', $bundle))->assertOk()->assertSee('save $20.00');
    $this->actingAs($buyer)->post(route('bundles.cart', $bundle))->assertRedirect(route('cart'));

    $order = payCart($buyer);
    $items = $order->items->keyBy('product_id');

    expect($order->status)->toBe(\App\Enums\OrderStatus::Paid)
        ->and($items[$a->id]->price_cents)->toBe(500)   // 1000 : 3000 split of $20
        ->and($items[$b->id]->price_cents)->toBe(1500)
        ->and($items->every(fn ($i) => $i->bundle_id === $bundle->id))->toBeTrue();
});

test('affiliates earn on marketplace sales only, never on their own purchase', function () {
    $affiliate = buyer();
    $code = $affiliate->affiliateCode();
    $product = connectedProduct(1000);
    $buyer = buyer();

    $this->get('/?aff='.$code)->assertCookie('dm_aff', (string) $affiliate->id);

    addToCart($buyer, $product);
    fakePayPal(null);
    $this->actingAs($buyer)->withCookie('dm_aff', (string) $affiliate->id)->post(route('checkout.start'));

    $item = Order::sole()->items->sole();
    expect($item->attribution)->toBe(TrafficSource::Marketplace)
        ->and($item->affiliate_id)->toBe($affiliate->id)
        ->and($item->affiliate_commission_cents)->toBe(100); // 10% of $10, out of our $3 fee
});

test('affiliate commission is skipped on seller-brought traffic and self-referrals', function () {
    $affiliate = buyer();
    $product = connectedProduct(1000);
    addToCart($affiliate, $product);
    fakePayPal(null);

    $this->actingAs($affiliate)->withCookie('dm_aff', (string) $affiliate->id)->post(route('checkout.start'));

    expect(Order::sole()->items->sole()->affiliate_id)->toBeNull();
});

test('public license check shows masked licensee and the JSON API', function () {
    $product = connectedProduct(1000);
    $buyer = buyer();
    $buyer->update(['name' => 'Budi Santoso']);
    addToCart($buyer, $product);
    $code = payCart($buyer)->items->first()->purchase_code;
    auth()->logout();

    $this->get(route('verify.show', $code))->assertOk()->assertSee('Valid license')->assertSee('B*** S******')->assertDontSee('Budi');

    $this->getJson(route('api.licenses.show', $code))->assertOk()->assertJson(['valid' => true, 'status' => 'active', 'licensee' => 'B*** S******']);
    $this->getJson(route('api.licenses.show', 'not-a-code'))->assertNotFound()->assertJson(['valid' => false]);
});
