<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Find or create tags from free-text names.
     *
     * @param  array<int, string>  $names
     * @return array<int, int> tag ids
     */
    public static function idsFromNames(array $names): array
    {
        return collect($names)
            ->map(fn ($name) => trim(mb_strtolower($name)))
            ->filter(fn ($name) => $name !== '' && Str::slug($name) !== '')
            ->unique(fn ($name) => Str::slug($name))
            ->map(fn ($name) => static::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)
            ->values()
            ->all();
    }
}
