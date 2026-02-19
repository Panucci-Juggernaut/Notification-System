<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Notifications\PasswordChangedNotification;
use App\Services\NotificationService;

class SendPasswordChangedNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /** Handle the PasswordChanged event by dispatching notifications across all enabled channels. */
    public function handle(PasswordChanged $event): void
    {
        $this->notificationService->dispatch(
            $event->user,
            'password_changed',
            new PasswordChangedNotification($event->user)
        );
    }
}
