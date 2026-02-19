<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Create a new notification instance. */
    public function __construct(
        protected User $user
    ) {}

    /** Get the notification's delivery channels. */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** Build the mail representation of the notification. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Changed - ' . config('app.name'))
            ->greeting('Hello ' . $this->user->name . ',')
            ->line('Your password was changed successfully.')
            ->line('If you did not make this change, please contact support immediately.')
            ->action('Contact Support', url('/'))
            ->line('Stay secure!');
    }

    /** Build the database/array representation of the notification. */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_changed',
            'title' => 'Password Changed',
            'message' => 'Your password was changed successfully. If this was not you, contact support.',
            'user_id' => $this->user->id,
        ];
    }

    /** Build the SMS text representation of the notification. */
    public function toSms(object $notifiable): string
    {
        return config('app.name') . " Security Alert: Your password was changed. If this wasn't you, contact support.";
    }

    /** Build the push notification payload for Firebase. */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Password Changed',
            'body' => 'Your password was changed successfully.',
            'data' => ['type' => 'password_changed', 'user_id' => $this->user->id],
        ];
    }
}
