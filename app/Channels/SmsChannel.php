<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel implements NotificationChannelInterface
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /** Send the notification via SMS using the configured SMS provider. */
    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($notifiable);
            $this->smsService->send($notifiable->email, $message);
        }
    }

    /** SMS channel supports all event types. */
    public function supports(string $eventType): bool
    {
        return true;
    }

    /** Return the channel slug identifier. */
    public function getSlug(): string
    {
        return 'sms';
    }
}
