<?php

namespace App\Models;

use App\Enums\TrafficSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'license_id', 'seller_id', 'product_title', 'license_name',
        'list_price_cents', 'discount_cents', 'price_cents', 'buyer_fee_cents', 'platform_fee_cents',
        'payment_fee_cents', 'seller_net_cents', 'fee_bps', 'attribution', 'purchase_code', 'gateway_capture_id',
        'bundle_id', 'affiliate_id', 'affiliate_commission_cents', 'affiliate_paid_at', 'refunded_at', 'refund_ref',
    ];

    protected function casts(): array
    {
        return [
            'attribution' => TrafficSource::class,
            'list_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'price_cents' => 'integer',
            'buyer_fee_cents' => 'integer',
            'platform_fee_cents' => 'integer',
            'payment_fee_cents' => 'integer',
            'seller_net_cents' => 'integer',
            'fee_bps' => 'integer',
            'affiliate_commission_cents' => 'integer',
            'affiliate_paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function review(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }
}
