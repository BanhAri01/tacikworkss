<?php

namespace App\Models;

use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    /** @use HasFactory<\Database\Factories\SellerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'bio',
        'avatar',
        'banner',
        'country',
        'paypal_email',
        'paypal_merchant_id',
        'paypal_payments_receivable',
        'paypal_email_confirmed',
        'paypal_connected_at',
        'tax_info',
        'portfolio_url',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SellerStatus::class,
            'tax_info' => 'array',
            'approved_at' => 'datetime',
            'paypal_payments_receivable' => 'boolean',
            'paypal_email_confirmed' => 'boolean',
            'paypal_connected_at' => 'datetime',
        ];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar) : null;
    }

    public function bannerUrl(): ?string
    {
        return $this->banner ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->banner) : null;
    }

    /** Buyers can pay this seller directly (PayPal multiparty: payee = seller). */
    public function canReceivePayments(): bool
    {
        return $this->isApproved()
            && $this->paypal_merchant_id !== null
            && $this->paypal_payments_receivable
            && $this->paypal_email_confirmed;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === SellerStatus::Approved;
    }
}
