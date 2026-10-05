<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your seller account is approved')
            ->greeting('Welcome aboard, '.$notifiable->name.'!')
            ->line('Your application to sell on '.config('app.name').' has been approved.')
            ->action('Open seller dashboard', route('seller.dashboard'))
            ->line('You can start uploading products from your dashboard.');
    }
}
