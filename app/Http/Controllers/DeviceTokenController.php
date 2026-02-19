<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /** List all device tokens for the authenticated user. */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->deviceTokens()->get();

        return response()->json(['device_tokens' => $tokens]);
    }

    /** Register a new device token for push notifications. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
            'platform' => 'required|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $validated['token'],
            ],
            [
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'] ?? null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Device token registered successfully.',
            'device_token' => $deviceToken,
        ], 201);
    }

    /** Remove a device token by its ID. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deviceToken = DeviceToken::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$deviceToken) {
            return response()->json(['message' => 'Device token not found.'], 404);
        }

        $deviceToken->delete();

        return response()->json(['message' => 'Device token removed successfully.']);
    }
}
