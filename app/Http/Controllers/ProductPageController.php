<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductPageController extends Controller
{
    public function show(Request $request, Product $product)
    {
        $product->load(['previews', 'licensePrices.license', 'tags', 'category.parent', 'seller.sellerProfile', 'latestFile']);

        // Unpublished items are visible only to their seller and admins (preview before approval).
        $user = $request->user();
        abort_unless($product->isPublished() || $user?->id === $product->seller_id || $user?->isAdmin(), 404);

        $card = fn ($q) => $q->published()->with(['cover', 'seller.sellerProfile:id,user_id,username,display_name'])->whereKeyNot($product->id)->limit(4);

        return view('products.show', [
            'product' => $product,
            'profile' => $product->seller->sellerProfile,
            'licensePrices' => $product->licensePrices->sortBy('license.sort')->values(),
            'related' => Product::query()->tap($card)->where('category_id', $product->category_id)->orderByDesc('sales_count')->get(),
            'moreFromSeller' => Product::query()->tap($card)->where('seller_id', $product->seller_id)->latest('published_at')->get(),
            'bundles' => $product->bundles()->where('is_active', true)->with('products.seller.sellerProfile')->get()->filter->isAvailable(),
        ]);
    }
}
