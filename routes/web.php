<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VerifyLicenseController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\Seller;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', HomeController::class)->name('home');
Volt::route('search', 'catalog.browse')->name('search');
Volt::route('category/{category}', 'catalog.browse')->name('categories.show');
Route::get('item/{product}', [ProductPageController::class, 'show'])->name('products.show');
Route::get('shop/{username}', [StorefrontController::class, 'show'])->name('shop.show');
Route::get('bundle/{bundle}', [BundleController::class, 'show'])->name('bundles.show');
Route::post('bundle/{bundle}/cart', [BundleController::class, 'addToCart'])->name('bundles.cart');
Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');

Route::get('verify', [VerifyLicenseController::class, 'form'])->name('verify.form');
Route::get('verify/{code}', [VerifyLicenseController::class, 'show'])->middleware('throttle:30,1')->name('verify.show');
Route::get('api/v1/licenses/{code}', [VerifyLicenseController::class, 'api'])->middleware('throttle:60,1')->name('api.licenses.show');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Disallow: /admin', 'Disallow: /seller', 'Disallow: /cart', 'Disallow: /checkout', 'Disallow: /downloads',
    '', 'Sitemap: '.route('sitemap'),
]), 200, ['Content-Type' => 'text/plain']))->name('robots');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('become-seller', 'seller.apply')->name('become-seller');

    Route::post('checkout', [CheckoutController::class, 'start'])->middleware('throttle:10,1')->name('checkout.start');
    Route::get('checkout/paypal/return', [CheckoutController::class, 'return'])->name('checkout.return');
    Route::get('checkout/paypal/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::get('downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::get('downloads/{orderItem}/file', [DownloadController::class, 'file'])->middleware('throttle:30,1')->name('downloads.file');
    Route::get('downloads/{orderItem}/stream/{file}', [DownloadController::class, 'stream'])->middleware('signed')->name('downloads.stream');
    Route::get('downloads/{orderItem}/license', [DownloadController::class, 'license'])->name('downloads.license');

    Volt::route('favorites', 'account.favorites')->name('favorites');
    Volt::route('collections', 'account.collections')->name('collections.index');
    Volt::route('affiliate', 'account.affiliate')->name('affiliate');

    Route::get('item/{product}/report', [ReportController::class, 'create'])->name('reports.create');
    Route::post('item/{product}/report', [ReportController::class, 'store'])->middleware('throttle:5,60')->name('reports.store');
});

Volt::route('cart', 'cart')->name('cart');
Route::post('webhooks/paypal', PayPalWebhookController::class)->name('webhooks.paypal');

Route::middleware(['auth', 'verified', 'seller'])
    ->prefix('seller')->name('seller.')
    ->group(function () {
        Route::get('/', Seller\DashboardController::class)->name('dashboard');
        Route::get('products', [Seller\ProductController::class, 'index'])->name('products.index');
        Volt::route('products/create', 'seller.products.form')->name('products.create');
        Volt::route('products/{product:id}/edit', 'seller.products.form')->name('products.edit');
        Route::post('products/{product:id}/submit', [Seller\ProductController::class, 'submit'])->name('products.submit');
        Route::delete('products/{product:id}', [Seller\ProductController::class, 'destroy'])->name('products.destroy');

        Route::post('paypal/connect', [Seller\PayPalController::class, 'connect'])->name('paypal.connect');
        Route::get('paypal/return', [Seller\PayPalController::class, 'return'])->name('paypal.return');
        Volt::route('coupons', 'seller.coupons')->name('coupons');
        Route::get('sales', [Seller\SalesController::class, 'index'])->name('sales.index');
        Route::get('sales/export', [Seller\SalesController::class, 'export'])->name('sales.export');
        Volt::route('tax', 'seller.tax')->name('tax');
        Volt::route('profile', 'seller.profile')->name('profile');
        Volt::route('reviews', 'seller.reviews')->name('reviews');
        Volt::route('bundles', 'seller.bundles')->name('bundles');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('sellers', [Admin\SellerProfileController::class, 'index'])->name('sellers.index');
        Route::get('sellers/{sellerProfile}', [Admin\SellerProfileController::class, 'show'])->name('sellers.show');
        Route::post('sellers/{sellerProfile}/approve', [Admin\SellerProfileController::class, 'approve'])->name('sellers.approve');
        Route::post('sellers/{sellerProfile}/suspend', [Admin\SellerProfileController::class, 'suspend'])->name('sellers.suspend');

        Route::get('products', [Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product:id}', [Admin\ProductController::class, 'show'])->name('products.show');
        Route::get('products/{product:id}/file', [Admin\ProductController::class, 'file'])->name('products.file');
        Route::post('products/{product:id}/approve', [Admin\ProductController::class, 'approve'])->name('products.approve');
        Route::post('products/{product:id}/reject', [Admin\ProductController::class, 'reject'])->name('products.reject');
        Route::post('products/{product:id}/feature', [Admin\ProductController::class, 'feature'])->name('products.feature');
        Route::post('products/{product:id}/free-of-the-week', [Admin\ProductController::class, 'freeOfTheWeek'])->name('products.free-of-the-week');

        Route::resource('categories', Admin\CategoryController::class)->except(['create', 'show'])->scoped(['category' => 'id']);
        Route::resource('tags', Admin\TagController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('licenses', Admin\LicenseController::class)->except(['create', 'show']);

        Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/items/{item}/refund', [Admin\OrderController::class, 'refund'])->name('orders.refund');

        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/unsuspend', [Admin\UserController::class, 'unsuspend'])->name('users.unsuspend');

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::patch('reports/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');

        Route::get('affiliates', [Admin\AffiliateController::class, 'index'])->name('affiliates.index');
        Route::post('affiliates/{user}/paid', [Admin\AffiliateController::class, 'markPaid'])->name('affiliates.paid');

        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });

require __DIR__.'/auth.php';
