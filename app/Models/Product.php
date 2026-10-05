<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'seller_id', 'category_id', 'title', 'slug', 'description', 'status', 'rejection_reason',
        'file_formats', 'compatible_software', 'is_free', 'featured_at', 'version',
        'min_price_cents', 'submitted_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'file_formats' => 'array',
            'compatible_software' => 'array',
            'is_free' => 'boolean',
            'featured_at' => 'datetime',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'sales_count' => 'integer',
            'min_price_cents' => 'integer',
            'rating_avg' => 'float',
            'reviews_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function previews(): HasMany
    {
        return $this->hasMany(ProductPreview::class)->orderBy('sort')->orderBy('id');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(ProductPreview::class)->whereNotNull('path')->orderBy('sort')->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class)->latest('id');
    }

    public function latestFile(): HasOne
    {
        return $this->hasOne(ProductFile::class)->latestOfMany();
    }

    public function licensePrices(): HasMany
    {
        return $this->hasMany(ProductLicensePrice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class);
    }

    /** Visible in the marketplace: approved product of an approved (not suspended) seller. */
    public function scopePublished(Builder $query): void
    {
        $query->where('products.status', ProductStatus::Approved)
            ->whereHas('seller.sellerProfile', fn ($q) => $q->where('status', SellerStatus::Approved));
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Approved
            && $this->seller?->sellerProfile?->status === SellerStatus::Approved;
    }

    /** Price in cents for a license, or null if this product doesn't offer it. Free products cost 0. */
    public function priceFor(int $licenseId): ?int
    {
        if ($this->is_free) {
            return 0;
        }

        return $this->licensePrices->firstWhere('license_id', $licenseId)?->price_cents;
    }

    public function refreshMinPrice(): void
    {
        $this->forceFill([
            'min_price_cents' => $this->is_free ? 0 : (int) $this->licensePrices()->min('price_cents'),
        ])->saveQuietly();
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($title, 80, '')) ?: 'item';
        $slug = $base;

        for ($i = 2; static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /** Scout (database engine searches these columns with LIKE). */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
