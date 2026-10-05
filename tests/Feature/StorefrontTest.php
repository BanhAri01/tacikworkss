<?php

use App\Enums\SellerStatus;
use App\Models\Product;
use Livewire\Volt\Volt;

test('public pages render and only show published items', function () {
    $live = Product::factory()->approved()->priced(1200)->create(['title' => 'Live Poster Pack']);
    $draft = Product::factory()->priced(900)->create(['title' => 'Secret Draft']);

    $this->get('/')->assertOk();
    $this->get(route('categories.show', $live->category))->assertOk()->assertSee('Live Poster Pack');

    Volt::test('catalog.browse')->assertSee('Live Poster Pack')->assertDontSee('Secret Draft');
    Volt::test('catalog.browse')->set('q', 'Poster')->assertSee('Live Poster Pack');
    Volt::test('catalog.browse')->set('free', true)->assertDontSee('Live Poster Pack');
    Volt::test('catalog.browse')->set('max', '10')->assertDontSee('Live Poster Pack');

    $this->get(route('products.show', $live))->assertOk()->assertSee('application/ld+json', false);
    $this->get(route('products.show', $draft))->assertNotFound();
    $this->actingAs($draft->seller)->get(route('products.show', $draft))->assertOk()->assertSee('Preview');
});

test('suspending a seller hides their products', function () {
    $product = Product::factory()->approved()->priced(1000)->create();
    $product->seller->sellerProfile->update(['status' => SellerStatus::Suspended]);

    $this->get(route('products.show', $product))->assertNotFound();
    $this->get(route('shop.show', $product->seller->sellerProfile->username))->assertNotFound();
});

test('storefront visit and ?ref= link set the 30-day attribution cookie', function () {
    $product = Product::factory()->approved()->priced(1000)->create();
    $username = $product->seller->sellerProfile->username;

    $this->get(route('shop.show', $username))->assertOk()->assertSee($product->title)->assertCookie('dm_ref', (string) $product->seller_id);
    $this->get('/?ref='.$username)->assertCookie('dm_ref', (string) $product->seller_id);
});

test('unknown referral usernames are ignored', function () {
    $this->get('/?ref=nobody')->assertCookieMissing('dm_ref');
});

test('sitemap and robots list public urls', function () {
    $product = Product::factory()->approved()->priced(1000)->create();

    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee(route('products.show', $product));
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'));
});

test('follow requires login and toggles', function () {
    $product = Product::factory()->approved()->priced(1000)->create();
    $buyer = \App\Models\User::factory()->create();

    Volt::test('follow-button', ['sellerId' => $product->seller_id])->call('toggle')->assertRedirect(route('login'));

    Volt::actingAs($buyer)->test('follow-button', ['sellerId' => $product->seller_id])->call('toggle')->assertSet('following', true);
    expect($buyer->following()->whereKey($product->seller_id)->exists())->toBeTrue();
});
