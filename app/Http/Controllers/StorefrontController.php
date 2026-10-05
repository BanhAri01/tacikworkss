<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\BrowseProducts;
use App\Enums\SellerStatus;
use App\Models\Bundle;
use App\Models\SellerProfile;
use App\Support\Attribution;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function show(Request $request, string $username, BrowseProducts $browse)
    {
        $profile = SellerProfile::query()
            ->where('username', strtolower($username))
            ->where('status', SellerStatus::Approved)
            ->firstOrFail();

        // Visiting a seller's own storefront counts as seller-brought traffic.
        Attribution::remember($profile->username);

        $sort = $request->string('sort')->value();

        return view('shop.show', [
            'profile' => $profile,
            'products' => $browse->handle(['seller_id' => $profile->user_id, 'sort' => $sort], 24)->withQueryString(),
            'followers' => $profile->user->followers()->count(),
            'bundles' => Bundle::where('seller_id', $profile->user_id)->where('is_active', true)
                ->with(['products.seller.sellerProfile', 'products.cover'])->latest()->get()->filter->isAvailable(),
            'sort' => array_key_exists($sort, BrowseProducts::SORTS) ? $sort : 'newest',
        ]);
    }
}
