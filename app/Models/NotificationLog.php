<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel_slug',
        'event_type',
        'notification_class',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /** Get the user this log entry belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
