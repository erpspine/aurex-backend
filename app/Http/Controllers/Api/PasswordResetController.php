<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetController extends Controller
{
    public function send(Request $request, PasswordResetService $reset): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            $reset->send($validated['email']);
        } catch (TransportExceptionInterface $error) {
            report($error);
            return response()->json(['message' => 'Unable to send the reset email. Please try again shortly.'], 503);
        }

        return response()->json([
            'message' => 'If an active account uses this email, a reset code has been sent. Check your inbox and spam folder. You can request up to five codes per hour.',
            'retry_after' => 60,
        ]);
    }

    public function verify(Request $request, PasswordResetService $reset): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);
        $token = $reset->verify($validated['email'], $validated['code']);
        if (! $token) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code. After five incorrect attempts, request a new code.'],
            ]);
        }

        return response()->json(['message' => 'Code verified. Choose your new password.', 'reset_token' => $token])
            ->header('Cache-Control', 'no-store');
    }

    public function reset(Request $request, PasswordResetService $reset): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);
        if (! $reset->reset($validated['email'], $validated['reset_token'], $validated['password'])) {
            throw ValidationException::withMessages([
                'reset_token' => ['Your reset session is invalid or expired. Request a new code.'],
            ]);
        }

        return response()->json(['message' => 'Your password has been changed. Log in with your new password.']);
    }
}
