<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimUpdate extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url = '/employee/progres',
        public ?string $invoiceUrl = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('claimly.notify_mail') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('[Product Claim] '.$this->title)
            ->greeting('Halo '.($notifiable->name ?? 'karyawan').',')
            ->line($this->body)
            ->action('Buka Product Claim', url($this->url));

        if ($this->invoiceUrl !== null) {
            $message->line('Invoice: '.url($this->invoiceUrl));
        }

        return $message->salutation('Salam, Tim Employee Purchase');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
