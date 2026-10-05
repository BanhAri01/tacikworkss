<?php

use App\Models\OrderItem;
use App\Notifications\ProductUpdated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    configurePayPal();
    Notification::fake();
});

test('sales page shows only the seller\'s own sales with fee totals, and exports CSV', function () {
    $mine = connectedProduct(1000);
    $other = connectedProduct(2000);
    $buyer = buyer();
    addToCart($buyer, $mine);
    addToCart($buyer, $other);
    payCart($buyer);

    $item = OrderItem::where('product_id', $mine->id)->sole();

    $this->actingAs($mine->seller)->get(route('seller.sales.index'))
        ->assertOk()
        ->assertSee($mine->title)
        ->assertDontSee($other->title)
        ->assertSee(\App\Support\Money::format($item->seller_net_cents));

    $csv = $this->actingAs($mine->seller)->get(route('seller.sales.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('platform_fee')->toContain($mine->title)->not->toContain($other->title);

    $this->actingAs($buyer)->get(route('seller.sales.index'))->assertRedirect(route('become-seller'));
});

test('tax form requires certification and stores signed data', function () {
    $seller = seller();

    Volt::actingAs($seller)->test('seller.tax')
        ->set('legal_name', 'Budi Santoso')->set('address', 'Jl. Sudirman 1')->set('city', 'Jakarta')->set('signature', 'Budi Santoso')
        ->call('save')
        ->assertHasErrors('certify')
        ->set('certify', true)
        ->call('save')
        ->assertHasNoErrors();

    $tax = $seller->sellerProfile->fresh()->tax_info;
    expect($tax['legal_name'])->toBe('Budi Santoso')->and($tax['signed_at'])->not->toBeNull()->and($tax)->not->toHaveKey('certify');
});

test('uploading a new file version emails existing buyers with the changelog', function () {
    Storage::fake('products');
    $product = connectedProduct(1000);
    $product->files()->create(['path' => 'f.zip', 'original_name' => 'f.zip', 'size' => 1, 'version' => '1.0']);
    $buyer = buyer();
    addToCart($buyer, $product);
    payCart($buyer);
    $stranger = buyer();

    Volt::actingAs($product->seller)->test('seller.products.form', ['product' => $product->fresh()])
        ->set('zip', UploadedFile::fake()->create('v2.zip', 10, 'application/zip'))
        ->set('version', '2.0')
        ->set('changelog', 'Added 20 new icons')
        ->call('save')
        ->assertHasNoErrors();

    Notification::assertSentTo($buyer, ProductUpdated::class, fn ($n) => $n->file->version === '2.0' && $n->file->changelog === 'Added 20 new icons');
    Notification::assertNotSentTo($stranger, ProductUpdated::class);
    expect($product->fresh()->version)->toBe('2.0');
});
