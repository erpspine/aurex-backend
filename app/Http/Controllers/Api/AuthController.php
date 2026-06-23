<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('member.membershipPlan:id,name')
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'Active') {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $plainToken = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $plainToken,
            'user' => $user,
            'member' => $user->member,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('member.membershipPlan:id,name');

        return response()->json([
            'user' => $user,
            'member' => $user->member,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $plainToken = $request->bearerToken();

        if ($plainToken) {
            ApiToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => $validated['password'],
            'force_password_change' => false,
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
