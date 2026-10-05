<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bundle extends Model
{
    use SoftDeletes;

    protected $fillable = ['seller_id', 'license_id', 'title', 'slug', 'description', 'price_cents', 'is_active'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Buyable: active, seller approved, and every item still published. */
    public function isAvailable(): bool
    {
        $this->loadMissing(['products.seller.sellerProfile', 'seller.sellerProfile']);

        return $this->is_active
            && ! $this->trashed()
            && $this->products->count() >= 2
            && $this->products->every(fn (Product $p) => $p->isPublished());
    }

    /** What the items would cost separately with the bundle's license (or their lowest price). */
    public function separateTotal(): int
    {
        $this->loadMissing('products.licensePrices');

        return $this->products->sum(fn (Product $p) => $p->priceFor($this->license_id) ?? $p->min_price_cents);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($title, 80, '')) ?: 'bundle';
        $slug = $base;

        for ($i = 2; static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
