<?php

namespace Database\Seeders;

use App\Models\NotificationChannel;
use Illuminate\Database\Seeder;

class NotificationChannelSeeder extends Seeder
{
    /** Seed the three built-in notification channels. */
    public function run(): void
    {
        $channels = [
            [
                'name' => 'In-App',
                'slug' => 'database',
                'description' => "In-app notifications stored in Laravel's notifications table",
            ],
            [
                'name' => 'Email',
                'slug' => 'email',
                'description' => 'Email notifications via SMTP/Mailer',
            ],
            [
                'name' => 'SMS',
                'slug' => 'sms',
                'description' => 'SMS notifications via configured SMS provider',
            ],
            [
                'name' => 'Firebase Push',
                'slug' => 'firebase_push',
                'description' => 'Push notifications via Firebase Cloud Messaging',
            ],
        ];

        foreach ($channels as $channel) {
            NotificationChannel::firstOrCreate(['slug' => $channel['slug']], $channel);
        }
    }
}
