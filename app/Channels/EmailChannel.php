<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    /** Send the notification via email using Laravel's built-in mail system. */
    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toMail')) {
            $notifiable->notify($notification);
        }

        Log::info("EmailChannel: Notification sent to {$notifiable->email}");
    }

    /** Email channel supports all event types. */
    public function supports(string $eventType): bool
    {
        return true;
    }

    /** Return the channel slug identifier. */
    public function getSlug(): string
    {
        return 'email';
    }
}
