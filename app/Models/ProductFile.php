<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFile extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFileFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'path', 'original_name', 'size', 'version', 'changelog'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
