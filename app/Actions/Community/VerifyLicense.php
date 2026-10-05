<?php

namespace App\Actions\Community;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Illuminate\Support\Str;

/** Public lookup of a purchase code. Reveals only what a client/marketplace needs; buyer name is masked. */
class VerifyLicense
{
    /** @return array<string, mixed>|null */
    public function handle(string $code): ?array
    {
        if (! Str::isUuid($code)) {
            return null;
        }

        $item = OrderItem::query()
            ->where('purchase_code', strtolower($code))
            ->with(['order:id,buyer_id,status,paid_at', 'order.buyer:id,name', 'seller.sellerProfile:id,user_id,username,display_name', 'product:id,slug,title'])
            ->first();

        if (! $item || ! in_array($item->order->status, [OrderStatus::Paid, OrderStatus::Refunded], true)) {
            return null;
        }

        $valid = $item->refunded_at === null && $item->order->status === OrderStatus::Paid;

        return [
            'valid' => $valid,
            'status' => $valid ? 'active' : 'revoked',
            'purchase_code' => $item->purchase_code,
            'item' => $item->product_title,
            'item_url' => $item->product ? route('products.show', $item->product) : null,
            'license' => $item->license_name,
            'author' => $item->seller->sellerProfile?->display_name,
            'licensee' => $this->mask($item->order->buyer->name),
            'purchased_at' => $item->order->paid_at?->toIso8601String(),
        ];
    }

    /** "Budi Santoso" → "B*** S******" */
    private function mask(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))
            ->map(fn ($part) => mb_substr($part, 0, 1).str_repeat('*', max(1, mb_strlen($part) - 1)))
            ->implode(' ');
    }
}
