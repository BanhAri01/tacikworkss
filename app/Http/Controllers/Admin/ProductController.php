<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Product\ModerateProduct;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectProductRequest;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = ProductStatus::tryFrom($filters['status'] ?? '') ?? ProductStatus::Pending;

        $products = Product::query()
            ->with(['cover', 'seller.sellerProfile:id,user_id,username', 'category:id,name'])
            ->where('status', $status)
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            // Oldest first in the review queue so nobody waits forever.
            ->when($status === ProductStatus::Pending, fn ($q) => $q->oldest('submitted_at'), fn ($q) => $q->latest('updated_at'))
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'status'));
    }

    public function show(Product $product)
    {
        $product->load(['previews', 'latestFile', 'licensePrices.license', 'tags', 'category', 'seller.sellerProfile']);

        return view('admin.products.show', compact('product'));
    }

    public function approve(Product $product, ModerateProduct $moderate)
    {
        Gate::authorize('moderate', $product);

        $moderate->approve($product);

        return redirect()->route('admin.products.index')->with('status', __('Approved ":title".', ['title' => $product->title]));
    }

    public function reject(RejectProductRequest $request, Product $product, ModerateProduct $moderate)
    {
        $moderate->reject($product, $request->validated('reason'));

        return redirect()->route('admin.products.index')->with('status', __('Rejected ":title".', ['title' => $product->title]));
    }

    public function feature(Product $product)
    {
        Gate::authorize('feature', $product);

        $product->update(['featured_at' => $product->featured_at ? null : now()]);

        return back()->with('status', $product->featured_at ? __('Featured.') : __('Removed from featured.'));
    }

    public function freeOfTheWeek(Product $product)
    {
        Gate::authorize('feature', $product);
        abort_unless($product->is_free, 422, 'Only free products can be free of the week.');

        Setting::put('free_of_the_week_product_id', $product->id);

        return back()->with('status', __('Set as free of the week.'));
    }

    /** Lets moderators inspect the uploaded zip. */
    public function file(Product $product)
    {
        $file = $product->latestFile;
        abort_unless($file, 404);

        return Storage::disk(config('marketplace.products_disk'))->download($file->path, $file->original_name);
    }
}
