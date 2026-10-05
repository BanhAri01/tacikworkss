<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    public const NAV_CACHE_KEY = 'nav.categories';

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'sort'];

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(self::NAV_CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** This category id plus all descendant ids (categories are a small table). */
    public function descendantAndSelfIds(): array
    {
        $all = Category::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$this->id];

        for ($i = 0; $i < count($ids); $i++) {
            foreach ($all->get($ids[$i], []) as $child) {
                $ids[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * Flat list ordered as a tree, for selects: id => "Parent › Child".
     *
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        $all = static::query()->orderBy('sort')->orderBy('name')->get()->groupBy('parent_id');
        $options = collect();

        $walk = function ($parentId, string $prefix) use (&$walk, $all, $options) {
            foreach ($all->get($parentId, []) as $category) {
                $options[$category->id] = $prefix.$category->name;
                $walk($category->id, $prefix.$category->name.' › ');
            }
        };
        $walk(null, '');

        return $options;
    }
}
