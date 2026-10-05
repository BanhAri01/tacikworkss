<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProductFromSeller extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $seller = $this->product->seller->sellerProfile->display_name;

        return (new MailMessage)
            ->subject("New from {$seller}: {$this->product->title}")
            ->line("{$seller}, a designer you follow, just published \"{$this->product->title}\".")
            ->action('Take a look', route('products.show', $this->product))
            ->line('You get these emails because you follow this shop.');
    }
}
