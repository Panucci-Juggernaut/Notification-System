<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFromNewIP
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Create a new LoginFromNewIP event instance. */
    public function __construct(
        public User $user,
        public string $ipAddress,
        public ?string $userAgent = null
    ) {}
}
