<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_channel_id',
        'event_type',
        'is_enabled',
    ];

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    /** Get the user who owns this preference. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the notification channel for this preference. */
    public function notificationChannel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class);
    }
}
