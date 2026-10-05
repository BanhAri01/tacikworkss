<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Product\SubmitProduct;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->products()
            ->with(['cover', 'category:id,name'])
            ->latest('updated_at')
            ->paginate(20);

        return view('seller.products.index', compact('products'));
    }

    public function submit(Product $product, SubmitProduct $submit)
    {
        Gate::authorize('submit', $product);

        $submit->handle($product);

        return back()->with('status', __('Submitted for review.'));
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        // Soft delete: buyers keep access to files they already purchased.
        $product->unsearchable();
        $product->delete();

        return redirect()->route('seller.products.index')->with('status', __('Product deleted.'));
    }
}
