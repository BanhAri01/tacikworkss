<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\ProductFile;
use App\Models\User;
use App\Notifications\ProductUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/** Emails everyone who owns the product (paid, not refunded) about a new file version. */
class NotifyBuyersOfUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $fileId) {}

    public function handle(): void
    {
        $file = ProductFile::with('product')->find($this->fileId);

        if (! $file?->product) {
            return;
        }

        $buyerIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $file->product_id)
            ->whereNull('order_items.refunded_at')
            ->where('orders.status', OrderStatus::Paid)
            ->distinct()
            ->pluck('orders.buyer_id');

        User::whereIn('id', $buyerIds)->chunkById(200, function ($users) use ($file) {
            Notification::send($users, new ProductUpdated($file));
        });
    }
}
