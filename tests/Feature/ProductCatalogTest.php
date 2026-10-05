<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Jobs\ProcessProductPreview;
use App\Models\Category;
use App\Models\License;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\ProductModerated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Storage::fake('products');
    Storage::fake('public');
});

function fillProductForm($component, Category $category, License $license)
{
    return $component
        ->set('title', 'Minimal Logo Kit')
        ->set('category_id', $category->id)
        ->set('description', str_repeat('A clean minimal logo kit for brands. ', 3))
        ->set('tags', 'logo, Minimal, logo')
        ->set('file_formats', ['AI', 'SVG'])
        ->set('compatible_software', ['Illustrator'])
        ->set("prices.{$license->id}", '12.50');
}

test('buyers and pending sellers cannot open the product form', function () {
    $buyer = User::factory()->create()->assignRole(UserRole::Buyer);
    $this->actingAs($buyer)->get('/seller/products/create')->assertRedirect(route('become-seller'));

    $pending = SellerProfile::factory()->create()->user;
    $this->actingAs($pending)->get('/seller/products/create')->assertRedirect(route('become-seller'));
});

test('seller creates a draft with previews, zip, tags and prices in cents', function () {
    Queue::fake();
    $seller = seller();
    $category = Category::factory()->create();
    $license = License::factory()->create();

    $component = fillProductForm(Volt::actingAs($seller)->test('seller.products.form'), $category, $license)
        ->set('newPreviews', [UploadedFile::fake()->image('a.jpg', 1200, 800), UploadedFile::fake()->image('b.png', 1200, 800)])
        ->set('zip', UploadedFile::fake()->create('kit.zip', 500, 'application/zip'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstWhere('title', 'Minimal Logo Kit');
    $component->assertRedirect(route('seller.products.edit', $product->id));

    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->seller_id)->toBe($seller->id)
        ->and($product->min_price_cents)->toBe(1250)
        ->and($product->licensePrices()->first()->price_cents)->toBe(1250)
        ->and($product->tags()->pluck('name')->all())->toBe(['logo', 'minimal'])
        ->and($product->previews()->count())->toBe(2)
        ->and($product->files()->count())->toBe(1);

    Storage::disk('products')->assertExists($product->latestFile->path);
    Queue::assertPushed(ProcessProductPreview::class, 2);
});

test('invalid price and non-zip files are rejected', function () {
    $seller = seller();

    fillProductForm(Volt::actingAs($seller)->test('seller.products.form'), Category::factory()->create(), $license = License::factory()->create())
        ->set("prices.{$license->id}", '0.50')
        ->set('zip', UploadedFile::fake()->create('virus.exe', 10))
        ->call('save')
        ->assertHasErrors(["prices.{$license->id}", 'zip']);
});

test('preview job writes thumbnail and watermarked webp, then removes the source', function () {
    $product = Product::factory()->create();
    $path = UploadedFile::fake()->image('a.jpg', 1800, 1200)->store('preview-sources/'.$product->id, ['disk' => 'products']);
    $preview = $product->previews()->create(['source_path' => $path]);

    (new ProcessProductPreview($preview->id))->handle();

    $preview->refresh();
    expect($preview->path)->toEndWith('.webp')->and($preview->source_path)->toBeNull();
    Storage::disk('public')->assertExists([$preview->path, $preview->thumb_path]);
    Storage::disk('products')->assertMissing($path);

    [$width] = getimagesizefromstring(Storage::disk('public')->get($preview->path));
    expect($width)->toBe(1600);
});

test('submit requires preview, file and price', function () {
    $seller = seller();
    $product = Product::factory()->forSeller($seller)->create();

    $this->actingAs($seller)->post(route('seller.products.submit', $product->id))
        ->assertSessionHasErrors(['previews', 'zip', 'prices']);

    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
});

test('complete product can be submitted, then approved by admin', function () {
    Notification::fake();
    $seller = seller();
    $product = Product::factory()->forSeller($seller)->priced(1000)->create();
    $product->previews()->create(['path' => 'p.webp', 'thumb_path' => 't.webp']);
    $product->files()->create(['path' => 'f.zip', 'original_name' => 'f.zip', 'size' => 1, 'version' => '1.0']);

    $this->actingAs($seller)->post(route('seller.products.submit', $product->id))->assertSessionHasNoErrors();
    expect($product->fresh()->status)->toBe(ProductStatus::Pending);

    $this->actingAs(admin())->post(route('admin.products.approve', $product->id))->assertRedirect();

    expect($product->fresh()->status)->toBe(ProductStatus::Approved)
        ->and($product->fresh()->published_at)->not->toBeNull();
    Notification::assertSentTo($seller, ProductModerated::class);
});

test('rejecting needs a reason which the seller sees', function () {
    Notification::fake();
    $product = Product::factory()->pending()->create();

    $this->actingAs(admin())->post(route('admin.products.reject', $product->id), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs(admin())->post(route('admin.products.reject', $product->id), ['reason' => 'Preview images are blurry.'])->assertRedirect();

    expect($product->fresh())->status->toBe(ProductStatus::Rejected)->rejection_reason->toBe('Preview images are blurry.');
});

test('only admins moderate, and sellers cannot edit others\' products', function () {
    $product = Product::factory()->pending()->create();
    $other = seller();

    $this->actingAs($other)->post(route('admin.products.approve', $product->id))->assertForbidden();
    $this->actingAs($other)->get(route('seller.products.edit', $product->id))->assertForbidden();
    $this->actingAs($other)->post(route('seller.products.submit', $product->id))->assertForbidden();
});

test('editing text of a live product sends it back to review, price changes do not', function () {
    $seller = seller();
    $license = License::factory()->create();
    $product = Product::factory()->forSeller($seller)->approved()->priced(1000, $license)->create();

    Volt::actingAs($seller)->test('seller.products.form', ['product' => $product])
        ->set("prices.{$license->id}", '15')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh())->status->toBe(ProductStatus::Approved)->min_price_cents->toBe(1500);

    Volt::actingAs($seller)->test('seller.products.form', ['product' => $product->fresh()])
        ->set('title', 'A brand new title')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->status)->toBe(ProductStatus::Pending);
});

test('admin manages categories, tags and licenses', function () {
    $admin = admin();

    $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Fonts'])->assertSessionHasNoErrors();
    $fonts = Category::firstWhere('slug', 'fonts');
    $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Serif', 'parent_id' => $fonts->id])->assertSessionHasNoErrors();

    // A category can't become a child of its own descendant.
    $serif = Category::firstWhere('name', 'Serif');
    $this->actingAs($admin)->put(route('admin.categories.update', $fonts->id), ['name' => 'Fonts', 'parent_id' => $serif->id])->assertSessionHasErrors('parent_id');

    $this->actingAs($admin)->post(route('admin.tags.store'), ['name' => 'Retro'])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('admin.licenses.store'), ['name' => 'Personal', 'description' => 'Personal use'])->assertSessionHasNoErrors();

    foreach (['categories', 'tags', 'licenses', 'products'] as $page) {
        $this->actingAs($admin)->get(route("admin.{$page}.index"))->assertOk();
    }

    $buyer = User::factory()->create()->assignRole(UserRole::Buyer);
    $this->actingAs($buyer)->post(route('admin.tags.store'), ['name' => 'nope'])->assertForbidden();
});
