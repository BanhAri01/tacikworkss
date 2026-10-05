<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace listing: keyword search (Scout) + filters + sort.
 */
class BrowseProducts
{
    public const SORTS = [
        'newest' => 'Newest',
        'best-selling' => 'Best selling',
        'rating' => 'Top rated',
        'price-asc' => 'Price: low to high',
        'price-desc' => 'Price: high to low',
    ];

    /**
     * @param  array{q?: ?string, category?: ?Category, min?: ?int, max?: ?int, format?: ?string, software?: ?string, free?: bool, sort?: ?string, seller_id?: ?int}  $filters  min/max in cents
     */
    public function handle(array $filters, int $perPage = 24): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $sort = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'newest';

        $constrain = function (Builder $query) use ($filters) {
            $query->published()
                ->with(['cover', 'seller.sellerProfile:id,user_id,username,display_name'])
                ->when($filters['category'] ?? null, fn ($q, Category $c) => $q->whereIn('category_id', $c->descendantAndSelfIds()))
                ->when($filters['seller_id'] ?? null, fn ($q, $id) => $q->where('seller_id', $id))
                ->when($filters['free'] ?? false, fn ($q) => $q->where('is_free', true))
                ->when(isset($filters['min']), fn ($q) => $q->where('min_price_cents', '>=', $filters['min']))
                ->when(isset($filters['max']), fn ($q) => $q->where('min_price_cents', '<=', $filters['max']))
                ->when($filters['format'] ?? null, fn ($q, $f) => $q->whereJsonContains('file_formats', $f))
                ->when($filters['software'] ?? null, fn ($q, $s) => $q->whereJsonContains('compatible_software', $s));
        };

        [$column, $direction] = match ($sort) {
            'best-selling' => ['sales_count', 'desc'],
            'rating' => ['rating_avg', 'desc'],
            'price-asc' => ['min_price_cents', 'asc'],
            'price-desc' => ['min_price_cents', 'desc'],
            default => ['published_at', 'desc'],
        };

        if ($q !== '') {
            return Product::search($q)
                ->query($constrain)
                ->orderBy($column, $direction)
                ->orderBy('id', 'desc')
                ->paginate($perPage);
        }

        return Product::query()
            ->tap($constrain)
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
