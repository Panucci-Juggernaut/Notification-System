<?php

namespace App\Providers;

use App\Channels\EmailChannel;
use App\Channels\FirebasePushChannel;
use App\Channels\SmsChannel;
use App\Services\ChannelManager;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /** Register the ChannelManager as a singleton and bind services into the container. */
    public function register(): void
    {
        $this->app->singleton(ChannelManager::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(FirebaseService::class);

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService($app->make(ChannelManager::class));
        });
    }

    /** Bootstrap the notification system by registering all built-in channels. */
    public function boot(): void
    {
        $manager = $this->app->make(ChannelManager::class);

        $manager->register('email', $this->app->make(EmailChannel::class));
        $manager->register('sms', $this->app->make(SmsChannel::class));
        $manager->register('firebase_push', $this->app->make(FirebasePushChannel::class));
    }
}
