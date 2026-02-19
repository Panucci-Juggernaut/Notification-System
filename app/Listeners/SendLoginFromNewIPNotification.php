<?php

namespace App\Listeners;

use App\Events\LoginFromNewIP;
use App\Notifications\NewIPLoginNotification;
use App\Services\NotificationService;

class SendLoginFromNewIPNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /** Handle the LoginFromNewIP event by dispatching notifications across all enabled channels. */
    public function handle(LoginFromNewIP $event): void
    {
        $this->notificationService->dispatch(
            $event->user,
            'login_from_new_ip',
            new NewIPLoginNotification($event->user, $event->ipAddress, $event->userAgent)
        );
    }
}
