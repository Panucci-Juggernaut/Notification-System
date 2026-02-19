<?php

namespace App\Http\Controllers;

use App\Events\LoginFromNewIP;
use App\Events\PasswordChanged;
use App\Events\UserRegistered;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Register a new user, issue a Sanctum token, and fire the UserRegistered event. */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new UserRegistered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /** Authenticate a user, detect new IP addresses, and fire LoginFromNewIP when appropriate. */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $isNewIp = !UserLoginHistory::where('user_id', $user->id)
            ->where('ip_address', $ipAddress)
            ->exists();

        UserLoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_new_ip' => $isNewIp,
            'logged_in_at' => now(),
        ]);

        if ($isNewIp) {
            event(new LoginFromNewIP($user, $ipAddress, $userAgent));
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
            'new_ip_detected' => $isNewIp,
        ]);
    }

    /** Change the authenticated user's password and fire the PasswordChanged event. */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        event(new PasswordChanged($user));

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
