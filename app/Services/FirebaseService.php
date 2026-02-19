<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key', '');
    }

    /** Send a push notification to a specific device token via FCM. */
    public function sendToDevice(string $token, array $payload): bool
    {
        $data = [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'] ?? 'Notification',
                'body' => $payload['body'] ?? '',
            ],
            'data' => $payload['data'] ?? [],
        ];

        if (empty($this->serverKey) || app()->environment('local', 'testing')) {
            Log::channel('single')->info('Firebase Push Notification (Simulated)', $data);
            return true;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $this->serverKey,
            'Content-Type' => 'application/json',
        ])->post($this->fcmUrl, $data);

        return $response->successful();
    }

    /** Send a push notification to multiple device tokens. */
    public function sendToMultipleDevices(array $tokens, array $payload): bool
    {
        foreach ($tokens as $token) {
            $this->sendToDevice($token, $payload);
        }

        return true;
    }
}
