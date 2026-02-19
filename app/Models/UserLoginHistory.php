<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'location',
        'is_new_ip',
        'logged_in_at',
    ];

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'is_new_ip' => 'boolean',
            'logged_in_at' => 'datetime',
        ];
    }

    /** Get the user who owns this login history entry. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
