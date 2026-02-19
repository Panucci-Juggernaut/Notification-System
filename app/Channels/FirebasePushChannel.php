<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;

class FirebasePushChannel implements NotificationChannelInterface
{
    public function __construct(
        protected FirebaseService $firebaseService
    ) {}

    /** Send the notification via Firebase Cloud Messaging to all active device tokens. */
    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toPush')) {
            $payload = $notification->toPush($notifiable);
            $tokens = $notifiable->deviceTokens()
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            foreach ($tokens as $token) {
                $this->firebaseService->sendToDevice($token, $payload);
            }
        }
    }

    /** Firebase push channel supports all event types. */
    public function supports(string $eventType): bool
    {
        return true;
    }

    /** Return the channel slug identifier. */
    public function getSlug(): string
    {
        return 'firebase_push';
    }
}
