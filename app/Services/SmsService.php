<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /** Send an SMS message to the given recipient (simulated in development). */
    public function send(string $recipient, string $message): bool
    {
        Log::channel('single')->info('SMS Sent', [
            'to' => $recipient,
            'message' => $message,
            'provider' => config('services.sms.provider', 'simulated'),
            'timestamp' => now()->toIso8601String(),
        ]);

        return true;
    }
}
