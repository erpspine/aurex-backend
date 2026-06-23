<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MemberCredentialsMail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'members' => Member::query()
                ->with(['membershipPlan:id,name', 'user:id,email,status'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());
        $plainPassword = Str::random(12);

        $member = DB::transaction(function () use ($validated, $plainPassword): Member {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'user_type' => 'Member',
                'role' => 'Member',
                'status' => $this->userStatusFor($validated['membership_status']),
                'password' => Hash::make($plainPassword),
                'two_factor_enabled' => false,
                'force_password_change' => true,
            ]);

            return Member::create([
                ...$validated,
                'user_id' => $user->id,
            ]);
        });

        Mail::to($member->email)->send(new MemberCredentialsMail(
            member: $member,
            plainPassword: $plainPassword,
            appUrl: (string) config('app.mobile_app_url'),
        ));

        return response()->json([
            'message' => 'Member created successfully and mobile app credentials email sent.',
            'member' => $member->load(['membershipPlan:id,name', 'user:id,email,status']),
        ], 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json([
            'member' => $member->load(['membershipPlan:id,name', 'user:id,email,status']),
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate($this->updateRules($member));

        DB::transaction(function () use ($member, $validated): void {
            $member->update($validated);

            if ($member->user) {
                $member->user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'status' => $this->userStatusFor($validated['membership_status']),
                ]);
            }
        });

        return response()->json([
            'message' => 'Member updated successfully.',
            'member' => $member->fresh()->load(['membershipPlan:id,name', 'user:id,email,status']),
        ]);
    }

    public function destroy(Member $member): JsonResponse
    {
        DB::transaction(function () use ($member): void {
            $user = $member->user;

            $member->delete();

            if ($user && $user->user_type === 'Member') {
                $user->delete();
            }
        });

        return response()->json([
            'message' => 'Member and mobile app login account deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeRules(): array
    {
        return [
            ...$this->baseRules(),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updateRules(Member $member): array
    {
        $emailRules = ['required', 'email', 'max:255'];
        $emailRules[] = $member->user_id
            ? Rule::unique('users', 'email')->ignore($member->user_id)
            : Rule::unique('users', 'email');

        return [
            ...$this->baseRules(),
            'email' => $emailRules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'membership_plan_id' => ['nullable', 'uuid', 'exists:membership_plans,id'],
            'membership_status' => ['required', Rule::in(['Active', 'Pending', 'Expired', 'Suspended'])],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['Cash', 'M-Pesa', 'Airtel Money', 'Bank Transfer', 'Card'])],
            'payment_status' => ['required', Rule::in(['Paid', 'Pending', 'Failed'])],
            'height_cm' => ['nullable', 'integer', 'min:1'],
            'weight_kg' => ['nullable', 'integer', 'min:1'],
            'fitness_goal' => ['nullable', Rule::in(['Weight Loss', 'Muscle Gain', 'Strength', 'General Fitness'])],
            'workout_level' => ['nullable', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function userStatusFor(string $membershipStatus): string
    {
        return $membershipStatus === 'Active' ? 'Active' : 'Inactive';
    }
}
