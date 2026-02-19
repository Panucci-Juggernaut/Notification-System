<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /** Get all notification preferences for the authenticated user. */
    public function index(Request $request): JsonResponse
    {
        $preferences = UserNotificationPreference::with('notificationChannel')
            ->where('user_id', $request->user()->id)
            ->get()
            ->groupBy('event_type');

        return response()->json(['preferences' => $preferences]);
    }

    /** Update notification preferences for the authenticated user in bulk. */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.channel_slug' => 'required|string|exists:notification_channels,slug',
            'preferences.*.event_type' => 'required|string|in:user_registered,password_changed,login_from_new_ip',
            'preferences.*.is_enabled' => 'required|boolean',
        ]);

        $user = $request->user();

        foreach ($validated['preferences'] as $pref) {
            $channel = NotificationChannel::where('slug', $pref['channel_slug'])->first();

            UserNotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_channel_id' => $channel->id,
                    'event_type' => $pref['event_type'],
                ],
                [
                    'is_enabled' => $pref['is_enabled'],
                ]
            );
        }

        $updated = UserNotificationPreference::with('notificationChannel')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferences' => $updated,
        ]);
    }
}
