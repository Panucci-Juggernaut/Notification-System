<?php

namespace App\Providers;

use App\Events\LoginFromNewIP;
use App\Events\PasswordChanged;
use App\Events\UserRegistered;
use App\Listeners\SendLoginFromNewIPNotification;
use App\Listeners\SendPasswordChangedNotification;
use App\Listeners\SendUserRegisteredNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Register any application services. */
    public function register(): void
    {
        //
    }

    /** Bootstrap event-listener bindings and application services. */
    public function boot(): void
    {
        Event::listen(UserRegistered::class, SendUserRegisteredNotification::class);
        Event::listen(PasswordChanged::class, SendPasswordChangedNotification::class);
        Event::listen(LoginFromNewIP::class, SendLoginFromNewIPNotification::class);
    }
}
