<?php

namespace App\Http\Controllers;

use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = [['loc' => route('home'), 'lastmod' => null]];

            foreach (Category::query()->get(['slug', 'updated_at']) as $category) {
                $urls[] = ['loc' => route('categories.show', $category), 'lastmod' => $category->updated_at];
            }

            Product::published()->select(['id', 'slug', 'updated_at', 'seller_id', 'status'])
                ->lazyById(500)
                ->each(function ($product) use (&$urls) {
                    $urls[] = ['loc' => route('products.show', $product), 'lastmod' => $product->updated_at];
                });

            foreach (SellerProfile::where('status', SellerStatus::Approved)->get(['username', 'updated_at']) as $profile) {
                $urls[] = ['loc' => route('shop.show', $profile->username), 'lastmod' => $profile->updated_at];
            }

            return view('sitemap', compact('urls'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
