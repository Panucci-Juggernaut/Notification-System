<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Create a new notification instance. */
    public function __construct(
        protected User $user
    ) {}

    /** Get the notification's delivery channels for Laravel's built-in system. */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** Build the mail representation of the notification. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Thank you for registering with ' . config('app.name') . '.')
            ->line('Your account has been successfully created.')
            ->action('Visit Dashboard', url('/'))
            ->line('Welcome aboard!');
    }

    /** Build the database/array representation of the notification. */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_registered',
            'title' => 'Welcome!',
            'message' => 'Your account has been successfully created.',
            'user_id' => $this->user->id,
        ];
    }

    /** Build the SMS text representation of the notification. */
    public function toSms(object $notifiable): string
    {
        return "Welcome to " . config('app.name') . ", {$this->user->name}! Your account is ready.";
    }

    /** Build the push notification payload for Firebase. */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Welcome to ' . config('app.name'),
            'body' => 'Your account has been successfully created.',
            'data' => ['type' => 'user_registered', 'user_id' => $this->user->id],
        ];
    }
}
