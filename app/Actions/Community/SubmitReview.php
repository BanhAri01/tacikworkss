<?php

namespace App\Actions\Community;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Verified buyers only: one review per purchased order item. */
class SubmitReview
{
    public function handle(User $user, Product $product, int $rating, ?string $body): Review
    {
        $item = static::reviewableItem($user, $product);

        if (! $item) {
            throw ValidationException::withMessages(['rating' => __('Only buyers who haven\'t reviewed this item yet can leave a review.')]);
        }

        return DB::transaction(function () use ($user, $product, $item, $rating, $body) {
            $review = Review::create([
                'order_item_id' => $item->id,
                'product_id' => $product->id,
                'user_id' => $user->id,
                'rating' => max(1, min(5, $rating)),
                'body' => $body,
            ]);

            static::refreshRating($product);

            return $review;
        });
    }

    public static function reviewableItem(User $user, Product $product): ?OrderItem
    {
        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereNull('refunded_at')
            ->whereDoesntHave('review')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id)->where('status', OrderStatus::Paid))
            ->oldest('id')
            ->first();
    }

    public static function refreshRating(Product $product): void
    {
        $stats = Review::where('product_id', $product->id)->selectRaw('COUNT(*) as n, AVG(rating) as avg')->first();

        $product->forceFill([
            'reviews_count' => (int) $stats->n,
            'rating_avg' => round((float) $stats->avg, 2),
        ])->saveQuietly();
    }
}
