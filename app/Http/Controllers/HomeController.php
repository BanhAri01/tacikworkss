<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function __invoke()
    {
        $base = fn () => Product::published()->with(['cover', 'seller.sellerProfile:id,user_id,username,display_name']);

        $freeId = Setting::get('free_of_the_week_product_id');

        return view('home', [
            'featured' => $base()->whereNotNull('featured_at')->latest('featured_at')->limit(8)->get(),
            'newest' => $base()->latest('published_at')->limit(8)->get(),
            'popular' => $base()->where('sales_count', '>', 0)->orderByDesc('sales_count')->limit(8)->get(),
            'freeOfTheWeek' => $freeId ? $base()->where('is_free', true)->find($freeId) : null,
            'categories' => Category::whereNull('parent_id')->orderBy('sort')->get(),
        ]);
    }
}
