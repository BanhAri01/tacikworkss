<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductPreview extends Model
{
    protected $fillable = ['product_id', 'path', 'thumb_path', 'source_path', 'sort'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isProcessed(): bool
    {
        return $this->path !== null;
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function thumbUrl(): ?string
    {
        return $this->thumb_path ? Storage::disk('public')->url($this->thumb_path) : null;
    }
}
