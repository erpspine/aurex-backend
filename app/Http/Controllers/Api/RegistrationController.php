<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationController extends Controller
{
    public function register(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);
        if (User::query()->whereRaw('LOWER(email) = ?', [$validated['email']])->exists()) {
            throw ValidationException::withMessages(['email' => ['This email is already registered. Log in to continue or verify your email.']]);
        }

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => trim($validated['name']),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'user_type' => 'Member',
                'role' => 'Member',
                'status' => 'Active',
                'force_password_change' => false,
                'two_factor_enabled' => false,
            ]);
            Member::create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => '',
                'membership_status' => 'Pending',
                'payment_status' => 'Pending',
                'amount_paid' => 0,
            ]);
            return $user;
        });

        try {
            $retryAfter = $verification->send($user);
        } catch (TransportExceptionInterface $error) {
            report($error);
            return response()->json([
                'message' => 'Your account was created, but the verification email could not be sent. Please resend the code.',
                'verification_required' => true,
                'retry_after' => 0,
            ], 503);
        }

        return response()->json([
            'message' => 'Check your email for your verification code.',
            'verification_required' => true,
            'retry_after' => $retryAfter,
        ], 201);
    }

    public function verify(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);
        if (! $verification->verify(trim($validated['email']), $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code. After five incorrect attempts, request a new code.'],
            ]);
        }

        return response()->json(['message' => 'Email verified. You can now log in.']);
    }

    public function resend(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($validated['email']))])->first();
        $retryAfter = 60;
        try {
            if ($user && ! $user->email_verified_at && $user->status === 'Active') {
                $retryAfter = $verification->send($user);
            }
        } catch (TransportExceptionInterface $error) {
            report($error);
            return response()->json(['message' => 'Unable to send the verification email. Please try again shortly.'], 503);
        }

        return response()->json([
            'message' => 'If this account needs verification, a code has been sent. Check your inbox and spam folder.',
            'retry_after' => $retryAfter,
        ]);
    }
}
