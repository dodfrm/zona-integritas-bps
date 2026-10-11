<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ZiNotification extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
