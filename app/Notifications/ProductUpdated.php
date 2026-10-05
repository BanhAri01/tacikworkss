<?php

namespace App\Notifications;

use App\Models\ProductFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductFile $file) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Update available: '.$this->file->product->title.' v'.$this->file->version)
            ->line('A new version of "'.$this->file->product->title.'" (v'.$this->file->version.') is available for you.');

        if ($this->file->changelog) {
            $mail->line('What\'s new: '.$this->file->changelog);
        }

        return $mail->action('Download the update', route('downloads.index'));
    }
}
