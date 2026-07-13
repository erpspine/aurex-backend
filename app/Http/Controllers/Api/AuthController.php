<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->with('member.membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days')
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
            'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $plainToken,
            'user' => $user,
            'member' => $this->memberProfilePayload($user->member),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('member.membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days');

        return response()->json([
            'user' => $user,
            'member' => $this->memberProfilePayload($user->member),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load('member');
        $member = $user->member;

        if (! $member) {
            throw ValidationException::withMessages([
                'member' => ['No member profile is linked to this account.'],
            ]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:300'],
            'weight_kg' => ['nullable', 'integer', 'min:1', 'max:500'],
            'fitness_goal' => ['nullable', Rule::in(['Weight Loss', 'Muscle Gain', 'Strength', 'General Fitness'])],
            'workout_level' => ['nullable', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($user, $member, $validated): void {
            $member->update($validated);
            $user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);
        });

        $user = $user->fresh()->load('member.membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
            'member' => $this->memberProfilePayload($user->member),
        ]);
    }

    public function updateProfilePhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'max:5120'],
        ]);

        $user = $request->user()->load('member.membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days');

        $this->deleteStoredFile($user->profile_photo_path);

        $user->update([
            'profile_photo_path' => Storage::disk('public')->url(
                $validated['profile_photo']->store('profile-photos', 'public')
            ),
        ]);

        $user = $user->fresh()->load('member.membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days');

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'user' => $user,
            'member' => $this->memberProfilePayload($user->member),
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

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_contains($url, '/storage/')) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $path), '/');

        if ($storagePath !== '') {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function memberProfilePayload(?Member $member): ?array
    {
        if (! $member) {
            return null;
        }

        $member->loadMissing('membershipPlan:id,name,price_amount,currency,billing_cycle,duration_days');

        $payments = $member->payments()
            ->with('membershipPlan:id,name,price_amount,currency')
            ->latest('payment_date')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($payment): array => [
                'id' => $payment->id,
                'payment_for' => $payment->payment_for,
                'item_name' => $payment->item_name,
                'membership_plan' => $payment->membershipPlan,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'payment_date' => $payment->payment_date?->toDateString(),
                'payment_status' => $payment->payment_status,
            ])
            ->values();

        $expiryDate = $member->expiry_date ? Carbon::parse($member->expiry_date)->startOfDay() : null;
        $daysRemaining = $expiryDate
            ? now()->startOfDay()->diffInDays($expiryDate, false)
            : null;

        return [
            ...$member->toArray(),
            'membership_plan' => $member->membershipPlan,
            'card_details' => [
                'card_number' => $member->access_code,
                'status' => $member->access_code ? 'Linked' : 'Not linked',
            ],
            'renewal_date' => $member->expiry_date?->toDateString(),
            'days_before_expiry' => $daysRemaining,
            'latest_payment' => $payments->first(),
            'recent_payments' => $payments,
        ];
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
