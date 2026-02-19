<?php

namespace App\Services;

use App\Jobs\ProcessNotificationJob;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Notifications\Notification;

class NotificationService
{
    public function __construct(
        protected ChannelManager $channelManager
    ) {}

    /** Dispatch notifications for a user across all enabled channels for the given event type. */
    public function dispatch(User $user, string $eventType, Notification $notification): void
    {
        $supportedChannels = $this->channelManager->getChannelsForEvent($eventType);

        foreach ($supportedChannels as $slug => $channel) {
            if ($this->userHasChannelEnabled($user, $slug, $eventType)) {
                $log = $this->createLog($user, $slug, $eventType, get_class($notification));
                ProcessNotificationJob::dispatch($user, $slug, $notification, $log->id);
            }
        }
    }

    /** Check whether the user has enabled a given channel for a specific event type. */
    protected function userHasChannelEnabled(User $user, string $channelSlug, string $eventType): bool
    {
        $channelModel = NotificationChannel::where('slug', $channelSlug)
            ->where('is_active', true)
            ->first();

        if (!$channelModel) {
            return false;
        }

        $preference = UserNotificationPreference::where('user_id', $user->id)
            ->where('notification_channel_id', $channelModel->id)
            ->where('event_type', $eventType)
            ->first();

        if (!$preference) {
            return true;
        }

        return $preference->is_enabled;
    }

    /** Create a pending notification log entry for auditing. */
    protected function createLog(User $user, string $channelSlug, string $eventType, string $notificationClass): NotificationLog
    {
        return NotificationLog::create([
            'user_id' => $user->id,
            'channel_slug' => $channelSlug,
            'event_type' => $eventType,
            'notification_class' => $notificationClass,
            'status' => 'pending',
        ]);
    }

    /** Create default notification preferences for a newly registered user. */
    public function createDefaultPreferences(User $user): void
    {
        $channels = NotificationChannel::where('is_active', true)->get();
        $eventTypes = ['user_registered', 'password_changed', 'login_from_new_ip'];

        foreach ($channels as $channel) {
            foreach ($eventTypes as $eventType) {
                UserNotificationPreference::firstOrCreate([
                    'user_id' => $user->id,
                    'notification_channel_id' => $channel->id,
                    'event_type' => $eventType,
                ], [
                    'is_enabled' => true,
                ]);
            }
        }
    }
}
