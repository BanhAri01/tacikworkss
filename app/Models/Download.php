<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_item_id', 'user_id', 'product_file_id', 'ip', 'user_agent'];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
