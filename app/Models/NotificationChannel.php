<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'configuration',
    ];

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration' => 'array',
        ];
    }

    /** Get all user preferences associated with this channel. */
    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }
}
