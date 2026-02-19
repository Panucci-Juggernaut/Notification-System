<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\UserRegisteredNotification;
use App\Services\NotificationService;

class SendUserRegisteredNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /** Handle the UserRegistered event by dispatching notifications across all enabled channels. */
    public function handle(UserRegistered $event): void
    {
        $this->notificationService->createDefaultPreferences($event->user);

        $this->notificationService->dispatch(
            $event->user,
            'user_registered',
            new UserRegisteredNotification($event->user)
        );
    }
}
