<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddBundleToCart;
use App\Models\Bundle;

class BundleController extends Controller
{
    public function show(Bundle $bundle)
    {
        $bundle->load(['license', 'seller.sellerProfile', 'products' => fn ($q) => $q->with(['cover', 'licensePrices', 'seller.sellerProfile'])]);

        abort_unless($bundle->isAvailable() || auth()->id() === $bundle->seller_id, 404);

        return view('bundles.show', ['bundle' => $bundle, 'separate' => $bundle->separateTotal()]);
    }

    public function addToCart(Bundle $bundle, AddBundleToCart $add)
    {
        $add->handle($bundle);

        return redirect()->route('cart')->with('status', __('Bundle added to your cart.'));
    }
}
