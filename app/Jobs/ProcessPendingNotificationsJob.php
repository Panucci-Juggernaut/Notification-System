<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\ChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPendingNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Create a new batch-processor job instance. */
    public function __construct()
    {
        $this->onQueue('notifications');
    }

    /** Process all pending notification logs that have been stuck for more than 5 minutes. */
    public function handle(ChannelManager $channelManager): void
    {
        $pendingLogs = NotificationLog::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->limit(100)
            ->get();

        foreach ($pendingLogs as $logEntry) {
            try {
                $user = User::find($logEntry->user_id);

                if (!$user) {
                    $logEntry->update(['status' => 'failed', 'error_message' => 'User not found']);
                    continue;
                }

                if (!$channelManager->has($logEntry->channel_slug)) {
                    $logEntry->update(['status' => 'failed', 'error_message' => "Channel {$logEntry->channel_slug} not registered"]);
                    continue;
                }

                $notificationClass = $logEntry->notification_class;

                if (!class_exists($notificationClass)) {
                    $logEntry->update(['status' => 'failed', 'error_message' => "Class {$notificationClass} not found"]);
                    continue;
                }

                $notification = new $notificationClass($user);
                $channel = $channelManager->get($logEntry->channel_slug);
                $channel->send($user, $notification);

                $logEntry->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (Throwable $e) {
                $logEntry->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                Log::error("ProcessPendingNotificationsJob failed for log #{$logEntry->id}: {$e->getMessage()}");
            }
        }
    }
}
