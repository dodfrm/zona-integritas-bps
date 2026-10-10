<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ZiNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Internal (database) notifications. External channels (email/WhatsApp) are not configured.
 */
class Notifier
{
    /**
     * @param  iterable<User>|User  $users
     */
    public function send(iterable|User $users, string $title, string $message, string $url): void
    {
        $recipients = collect($users instanceof User ? [$users] : $users)
            ->filter(fn (User $user) => $user->is_active && $user->id !== auth()->id())
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ZiNotification($title, $message, $url));
        }
    }
}
