<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Notifications\Notification;

interface NotificationChannelInterface
{
    /** Send the notification to the given user via this channel. */
    public function send(User $notifiable, Notification $notification): void;

    /** Determine whether this channel supports the given event type. */
    public function supports(string $eventType): bool;

    /** Return the unique slug identifier for this channel. */
    public function getSlug(): string;
}
