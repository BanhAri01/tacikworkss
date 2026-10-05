<?php

namespace App\Jobs;

use App\Models\Product;
use App\Notifications\NewProductFromSeller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyFollowers implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $productId) {}

    public function handle(): void
    {
        $product = Product::with('seller.sellerProfile')->find($this->productId);

        if (! $product?->isPublished()) {
            return;
        }

        $product->seller->followers()->whereNull('suspended_at')->chunkById(200, function ($followers) use ($product) {
            Notification::send($followers, new NewProductFromSeller($product));
        }, 'users.id', 'id');
    }
}
