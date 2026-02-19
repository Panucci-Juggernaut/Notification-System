<?php

namespace App\Http\Controllers;

use App\Events\LoginFromNewIP;
use App\Events\PasswordChanged;
use App\Events\UserRegistered;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** List all notifications for the authenticated user with optional filtering. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->notifications();

        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->paginate(15);

        return response()->json($notifications);
    }

    /** Mark a specific notification as read by its ID. */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /** Mark all notifications as read for the authenticated user. */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /** Get the notification log history for the authenticated user. */
    public function logs(Request $request): JsonResponse
    {
        $logs = NotificationLog::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($logs);
    }

    /** Fire a test event for the authenticated user to verify the notification pipeline. */
    public function triggerTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => 'required|string|in:user_registered,password_changed,login_from_new_ip',
        ]);

        $user = $request->user();

        match ($validated['event']) {
            'user_registered' => event(new UserRegistered($user)),
            'password_changed' => event(new PasswordChanged($user)),
            'login_from_new_ip' => event(new LoginFromNewIP($user, $request->ip(), $request->userAgent())),
        };

        return response()->json([
            'message' => "Test event '{$validated['event']}' dispatched successfully.",
        ]);
    }
}
