<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\ChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /** Create a new job instance for sending a notification via a specific channel. */
    public function __construct(
        protected User $user,
        protected string $channelSlug,
        protected Notification $notification,
        protected int $logId
    ) {
        $this->onQueue('notifications');
    }

    /** Execute the job by sending the notification through the resolved channel. */
    public function handle(ChannelManager $channelManager): void
    {
        $channel = $channelManager->get($this->channelSlug);
        $channel->send($this->user, $this->notification);

        NotificationLog::where('id', $this->logId)->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /** Handle a job failure by updating the notification log with the error. */
    public function failed(Throwable $exception): void
    {
        NotificationLog::where('id', $this->logId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
