<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Jobs\NotifyFollowers;
use App\Models\Product;
use App\Notifications\ProductModerated;

class ModerateProduct
{
    public function approve(Product $product): void
    {
        $firstPublish = $product->published_at === null;

        $product->update([
            'status' => ProductStatus::Approved,
            'rejection_reason' => null,
            'published_at' => $product->published_at ?? now(),
        ]);

        $product->searchable();
        $product->seller->notify(new ProductModerated($product));

        // Followers hear about brand-new items only, not re-approvals after edits.
        if ($firstPublish) {
            NotifyFollowers::dispatch($product->id);
        }
    }

    public function reject(Product $product, string $reason): void
    {
        $product->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        $product->unsearchable();
        $product->seller->notify(new ProductModerated($product));
    }
}
