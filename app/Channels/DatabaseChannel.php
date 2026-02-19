<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class DatabaseChannel implements NotificationChannelInterface
{
    /** Persist the notification to Laravel's notifications table (in-app). */
    public function send(User $notifiable, Notification $notification): void
    {
        NotificationFacade::sendNow($notifiable, $notification, ['database']);
    }

    public function supports(string $eventType): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'database';
    }
}

