<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIPLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Create a new notification instance. */
    public function __construct(
        protected User $user,
        protected string $ipAddress,
        protected ?string $userAgent = null
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
            ->subject('New Login Detected - ' . config('app.name'))
            ->greeting('Hello ' . $this->user->name . ',')
            ->line('We detected a login to your account from a new IP address.')
            ->line('IP Address: ' . $this->ipAddress)
            ->line('User Agent: ' . ($this->userAgent ?? 'Unknown'))
            ->line('Time: ' . now()->toDateTimeString())
            ->line('If this was not you, please change your password immediately.')
            ->action('Change Password', url('/'))
            ->line('Stay safe!');
    }

    /** Build the database/array representation of the notification. */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'login_from_new_ip',
            'title' => 'New Login Detected',
            'message' => "Login from new IP: {$this->ipAddress}",
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'user_id' => $this->user->id,
        ];
    }

    /** Build the SMS text representation of the notification. */
    public function toSms(object $notifiable): string
    {
        return config('app.name') . " Alert: New login from IP {$this->ipAddress}. If this wasn't you, change your password.";
    }

    /** Build the push notification payload for Firebase. */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'New Login Detected',
            'body' => "Login from new IP: {$this->ipAddress}",
            'data' => [
                'type' => 'login_from_new_ip',
                'ip_address' => $this->ipAddress,
                'user_id' => $this->user->id,
            ],
        ];
    }
}
