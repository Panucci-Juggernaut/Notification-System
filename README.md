# Event-Driven Notification System

A multi-channel notification system built with Laravel 12 using Event-Driven Architecture and the Publisher/Subscriber pattern. Supports **Email**, **SMS**, and **Firebase Push Notifications** with an extensible service connector that allows any new channel to plug in.

---

## Architecture

```
 ┌─────────────┐         ┌──────────────────────┐
 │ Controller  │──fires──▶  Event (Publisher)    │
 └─────────────┘         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │ Listener (Subscriber) │
                         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │ NotificationService   │  ◄── checks user preferences
                         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │   ChannelManager      │  ◄── service connector registry
                         └───┬──────┬───────┬───┘
                             │      │       │
                             ▼      ▼       ▼
                         ┌──────┐┌─────┐┌──────────┐
                         │Email ││ SMS ││ Firebase  │
                         └──┬───┘└──┬──┘└────┬─────┘
                            │       │        │
                            ▼       ▼        ▼
                     ┌─────────────────────────────┐
                     │  ProcessNotificationJob      │  ◄── async via Laravel Queue
                     └──────────────┬──────────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │   NotificationLog     │  ◄── audit trail
                         └──────────────────────┘
```

### Key Design Decisions

- **Publishers** are Events (`UserRegistered`, `PasswordChanged`, `LoginFromNewIP`) that carry event data
- **Subscribers** are Listeners that receive events, consult user notification preferences, and dispatch jobs per enabled channel
- **Service Connector** pattern via `ChannelManager` -- any service implements `NotificationChannelInterface` and registers itself
- **Async Processing** -- all notifications are dispatched via Laravel Jobs on the `notifications` queue
- **User Preferences** -- per-user, per-channel, per-event toggles stored in the database. Defaults to enabled when no preference exists
- **Scheduler Safety** -- `ProcessPendingNotificationsJob` runs every minute with `withoutOverlapping()` and `runInBackground()` to retry stuck notifications

---

## Setup

### Prerequisites

- PHP 8.2+
- Composer
- SQLite (default) or any Laravel-supported database

### Installation

```bash
# 1. Install dependencies
composer install

# 2. Copy environment file (if needed)
cp .env.example .env
php artisan key:generate

# 3. Install Laravel API scaffolding (Sanctum + api.php)
php artisan install:api

# 4. Run migrations and seed channels
php artisan migrate
php artisan db:seed

# 5. Start the development server
php artisan serve
```

### Environment Variables

Add these to your `.env` file:

```env
# SMS Configuration (simulated by default)
SMS_PROVIDER=simulated
SMS_API_KEY=
SMS_FROM_NUMBER=

# Firebase Configuration (simulated in local/testing)
FIREBASE_SERVER_KEY=
FIREBASE_PROJECT_ID=
```

### Running the Queue Worker

```bash
# Process notification jobs
php artisan queue:work --queue=notifications
```

### Running the Scheduler

```bash
# Start the scheduler (runs ProcessPendingNotificationsJob every minute)
php artisan schedule:work
```

---

## API Endpoints

**Base URL:** `http://localhost:8000/api`

All protected routes require the header:
```
Authorization: Bearer {token}
```

### Authentication

#### Register a User

```
POST /api/register
```

**Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
    "message": "User registered successfully.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "1|abc123..."
}
```

**Events Fired:** `UserRegistered` -- sends welcome notification via all enabled channels.

---

#### Login

```
POST /api/login
```

**Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "message": "Login successful.",
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
    "token": "2|def456...",
    "new_ip_detected": true
}
```

**Events Fired:** `LoginFromNewIP` (only when the IP address hasn't been seen before for this user).

---

#### Change Password (Protected)

```
POST /api/change-password
Authorization: Bearer {token}
```

**Body:**
```json
{
    "current_password": "password123",
    "password": "newpassword456",
    "password_confirmation": "newpassword456"
}
```

**Response (200):**
```json
{
    "message": "Password changed successfully."
}
```

**Events Fired:** `PasswordChanged` -- sends security alert notification.

---

### Notifications

#### List Notifications (Protected)

```
GET /api/notifications
Authorization: Bearer {token}
```

**Query Parameters:**
- `unread_only=true` -- filter to unread notifications only

**Response (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": "uuid-here",
            "type": "App\\Notifications\\UserRegisteredNotification",
            "data": {
                "type": "user_registered",
                "title": "Welcome!",
                "message": "Your account has been successfully created."
            },
            "read_at": null,
            "created_at": "2025-01-01T00:00:00.000000Z"
        }
    ],
    "total": 1
}
```

---

#### Mark Notification as Read (Protected)

```
POST /api/notifications/{id}/read
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Notification marked as read."
}
```

---

#### Mark All Notifications as Read (Protected)

```
POST /api/notifications/mark-all-read
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "All notifications marked as read."
}
```

---

#### Notification Logs (Protected)

```
GET /api/notifications/logs
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "channel_slug": "email",
            "event_type": "user_registered",
            "notification_class": "App\\Notifications\\UserRegisteredNotification",
            "status": "sent",
            "sent_at": "2025-01-01T00:00:05.000000Z"
        }
    ]
}
```

---

#### Trigger Test Event (Protected)

```
POST /api/notifications/test
Authorization: Bearer {token}
```

**Body:**
```json
{
    "event": "user_registered"
}
```

**Allowed values:** `user_registered`, `password_changed`, `login_from_new_ip`

**Response (200):**
```json
{
    "message": "Test event 'user_registered' dispatched successfully."
}
```

---

### Notification Preferences

#### Get Preferences (Protected)

```
GET /api/notification-preferences
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "preferences": {
        "user_registered": [
            {
                "id": 1,
                "event_type": "user_registered",
                "is_enabled": true,
                "notification_channel": { "id": 1, "name": "Email", "slug": "email" }
            },
            {
                "id": 2,
                "event_type": "user_registered",
                "is_enabled": true,
                "notification_channel": { "id": 2, "name": "SMS", "slug": "sms" }
            }
        ]
    }
}
```

---

#### Update Preferences (Protected)

```
PUT /api/notification-preferences
Authorization: Bearer {token}
```

**Body:**
```json
{
    "preferences": [
        {
            "channel_slug": "sms",
            "event_type": "user_registered",
            "is_enabled": false
        },
        {
            "channel_slug": "firebase_push",
            "event_type": "login_from_new_ip",
            "is_enabled": true
        }
    ]
}
```

**Response (200):**
```json
{
    "message": "Preferences updated successfully.",
    "preferences": [...]
}
```

---

### Device Tokens (Firebase Push)

#### List Device Tokens (Protected)

```
GET /api/device-tokens
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "device_tokens": [
        {
            "id": 1,
            "token": "fcm-token-abc123",
            "platform": "android",
            "device_name": "Pixel 7",
            "is_active": true
        }
    ]
}
```

---

#### Register Device Token (Protected)

```
POST /api/device-tokens
Authorization: Bearer {token}
```

**Body:**
```json
{
    "token": "fcm-token-abc123",
    "platform": "android",
    "device_name": "Pixel 7"
}
```

**Allowed platforms:** `android`, `ios`, `web`

**Response (201):**
```json
{
    "message": "Device token registered successfully.",
    "device_token": {
        "id": 1,
        "token": "fcm-token-abc123",
        "platform": "android",
        "device_name": "Pixel 7"
    }
}
```

---

#### Remove Device Token (Protected)

```
DELETE /api/device-tokens/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Device token removed successfully."
}
```

---

## Testing Guide

### Full End-to-End Test Flow

```bash
# Start the server and queue worker in separate terminals:
php artisan serve
php artisan queue:work --queue=notifications

# 1. Register a user (fires UserRegistered event)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
# Save the token from the response

# 2. Login (fires LoginFromNewIP on first login)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jane@example.com",
    "password": "password123"
  }'

# 3. Check notifications (use token from step 1 or 2)
curl http://localhost:8000/api/notifications \
  -H "Authorization: Bearer {token}"

# 4. Check notification logs
curl http://localhost:8000/api/notifications/logs \
  -H "Authorization: Bearer {token}"

# 5. Register a device token for push notifications
curl -X POST http://localhost:8000/api/device-tokens \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "fcm-test-token-12345",
    "platform": "android",
    "device_name": "Test Device"
  }'

# 6. Trigger a test event
curl -X POST http://localhost:8000/api/notifications/test \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"event": "password_changed"}'

# 7. View preferences
curl http://localhost:8000/api/notification-preferences \
  -H "Authorization: Bearer {token}"

# 8. Disable SMS for password_changed events
curl -X PUT http://localhost:8000/api/notification-preferences \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "preferences": [
      {"channel_slug": "sms", "event_type": "password_changed", "is_enabled": false}
    ]
  }'

# 9. Change password (fires PasswordChanged event)
curl -X POST http://localhost:8000/api/change-password \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "password123",
    "password": "newpassword456",
    "password_confirmation": "newpassword456"
  }'

# 10. Check logs -- SMS should NOT appear for password_changed
curl http://localhost:8000/api/notifications/logs \
  -H "Authorization: Bearer {token}"
```

### Verifying Simulated Channels

Since SMS and Firebase are simulated in development, check the Laravel log:

```bash
# View simulated SMS and push notification output
tail -f storage/logs/laravel.log
```

You will see entries like:
```
[INFO] SMS Sent {"to":"jane@example.com","message":"Welcome to Laravel...","provider":"simulated"}
[INFO] Firebase Push Notification (Simulated) {"to":"fcm-test-token-12345","notification":{"title":"Welcome","body":"..."}}
```

---

## Extending: Adding a Custom Channel

Any service can connect to the notification system by implementing `NotificationChannelInterface` and registering with the `ChannelManager`.

### Step 1: Implement the Interface

```php
<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use Illuminate\Notifications\Notification;

class WebhookChannel implements NotificationChannelInterface
{
    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toWebhook')) {
            $payload = $notification->toWebhook($notifiable);
            // Send HTTP request to webhook URL
        }
    }

    public function supports(string $eventType): bool
    {
        return true; // or filter by specific event types
    }

    public function getSlug(): string
    {
        return 'webhook';
    }
}
```

### Step 2: Register the Channel

In any service provider's `boot()` method:

```php
$manager = app(\App\Services\ChannelManager::class);
$manager->register('webhook', app(\App\Channels\WebhookChannel::class));
```

### Step 3: Seed the Database

Add a row to `notification_channels`:

```php
NotificationChannel::create([
    'name' => 'Webhook',
    'slug' => 'webhook',
    'description' => 'Webhook notifications',
]);
```

The new channel is now active and will be included in user preference management and notification dispatch.

---

## Project Structure

```
app/
├── Channels/                         # Channel implementations
│   ├── EmailChannel.php
│   ├── SmsChannel.php
│   └── FirebasePushChannel.php
├── Contracts/                        # Interfaces
│   └── NotificationChannelInterface.php
├── Events/                           # Publishers
│   ├── UserRegistered.php
│   ├── PasswordChanged.php
│   └── LoginFromNewIP.php
├── Http/Controllers/                 # API Controllers
│   ├── AuthController.php
│   ├── NotificationController.php
│   ├── NotificationPreferenceController.php
│   └── DeviceTokenController.php
├── Jobs/                             # Queue Jobs
│   ├── ProcessNotificationJob.php
│   └── ProcessPendingNotificationsJob.php
├── Listeners/                        # Subscribers
│   ├── SendUserRegisteredNotification.php
│   ├── SendPasswordChangedNotification.php
│   └── SendLoginFromNewIPNotification.php
├── Models/                           # Eloquent Models
│   ├── User.php
│   ├── NotificationChannel.php
│   ├── UserNotificationPreference.php
│   ├── DeviceToken.php
│   ├── NotificationLog.php
│   └── UserLoginHistory.php
├── Notifications/                    # Notification Classes
│   ├── UserRegisteredNotification.php
│   ├── PasswordChangedNotification.php
│   └── NewIPLoginNotification.php
├── Providers/                        # Service Providers
│   ├── AppServiceProvider.php
│   └── NotificationServiceProvider.php
└── Services/                         # Core Services
    ├── ChannelManager.php
    ├── NotificationService.php
    ├── SmsService.php
    └── FirebaseService.php

database/migrations/
├── ..._create_notifications_table.php
├── ..._create_notification_channels_table.php
├── ..._create_user_notification_preferences_table.php
├── ..._create_device_tokens_table.php
├── ..._create_notification_logs_table.php
└── ..._create_user_login_histories_table.php

routes/
├── api.php                           # All API endpoints
└── console.php                       # Scheduler configuration
```

---

## Database Schema

| Table | Purpose |
|-------|---------|
| `users` | User accounts (Laravel default) |
| `notifications` | Laravel's built-in notification storage |
| `notification_channels` | Channel registry (email, sms, firebase_push) |
| `user_notification_preferences` | Per-user, per-channel, per-event toggles |
| `device_tokens` | Firebase device tokens per user |
| `notification_logs` | Audit trail with status tracking (pending/sent/failed) |
| `user_login_histories` | IP address tracking for new-IP detection |
| `jobs` / `failed_jobs` | Laravel queue tables |
