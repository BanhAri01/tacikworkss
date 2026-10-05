<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        $mail = (new MailMessage)
            ->subject('Your '.config('app.name').' order')
            ->greeting('Thanks for your purchase!');

        foreach ($order->items as $item) {
            $mail->line("• {$item->product_title} ({$item->license_name}) — purchase code: {$item->purchase_code}");
        }

        if ($order->total_cents > 0) {
            $mail->line('Items: '.Money::format($order->subtotal_cents - $order->discount_cents)
                .' · Processing fee: '.Money::format($order->buyer_fee_cents)
                .' · Total paid: '.Money::format($order->total_cents));
            $mail->line('Payments go directly to each seller via PayPal.');
        }

        return $mail->action('Go to my downloads', route('downloads.index'));
    }
}
