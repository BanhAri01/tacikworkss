<?php

namespace App\Providers;

use App\Actions\Cart\MergeGuestCart;
use App\Contracts\PaymentGateway;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use App\Payments\PayPalGateway;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, PayPalGateway::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Event::listen(Login::class, fn (Login $event) => app(MergeGuestCart::class)->handle($event->user));

        // Top-level categories for the storefront's category bar.
        View::composer('components.layouts.public', fn ($view) => $view->with(
            'navCategories',
            Cache::remember(Category::NAV_CACHE_KEY, 600, fn () => Category::whereNull('parent_id')->orderBy('sort')->get(['id', 'name', 'slug'])),
        ));
    }
}
