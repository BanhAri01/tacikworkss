<?php

namespace App\Models;

use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    protected $fillable = ['seller_id', 'type', 'amount_cents', 'order_item_id', 'description'];

    protected function casts(): array
    {
        return ['type' => LedgerType::class, 'amount_cents' => 'integer'];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
