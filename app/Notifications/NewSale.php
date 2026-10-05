<?php

namespace App\Notifications;

use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to a seller with the fee breakdown of their items in one order. */
class NewSale extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  Collection<int, \App\Models\OrderItem>  $items */
    public function __construct(public Collection $items) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('New sale on '.config('app.name'));

        foreach ($this->items as $item) {
            if ($item->price_cents === 0 && $item->list_price_cents === 0) {
                $mail->line("• {$item->product_title} — free download");

                continue;
            }

            $mail->line("• {$item->product_title} ({$item->license_name})")
                ->line('  Price '.Money::format($item->price_cents)
                    .' + buyer processing fee '.Money::format($item->buyer_fee_cents)
                    .' − PayPal fee '.Money::format($item->payment_fee_cents)
                    .' − platform fee '.Money::format($item->platform_fee_cents).' ('.Money::percent($item->fee_bps).', '.$item->attribution->value.' traffic)'
                    .' = you receive '.Money::format($item->seller_net_cents));
        }

        return $mail->line('The money is already in your PayPal account.');
    }
}
