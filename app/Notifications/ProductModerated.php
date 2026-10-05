<?php

namespace App\Notifications;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductModerated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product;

        if ($product->status === ProductStatus::Approved) {
            return (new MailMessage)
                ->subject('Approved: '.$product->title)
                ->line('Your product "'.$product->title.'" is now live.')
                ->action('View product', route('products.show', $product));
        }

        return (new MailMessage)
            ->subject('Changes needed: '.$product->title)
            ->line('Your product "'.$product->title.'" was not approved.')
            ->line('Reason: '.$product->rejection_reason)
            ->action('Edit product', route('seller.products.edit', $product))
            ->line('Update it and submit again for review.');
    }
}
