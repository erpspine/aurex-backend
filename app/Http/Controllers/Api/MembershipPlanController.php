<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembershipPlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'plans' => MembershipPlan::query()->latest()->get(),
        ]);
    }

    public function show(MembershipPlan $membershipPlan): JsonResponse
    {
        return response()->json([
            'plan' => $membershipPlan,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $plan = MembershipPlan::create($validated);

        return response()->json([
            'message' => 'Membership plan created successfully.',
            'plan' => $plan,
        ], 201);
    }

    public function update(Request $request, MembershipPlan $membershipPlan): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $membershipPlan->update($validated);

        return response()->json([
            'message' => 'Membership plan updated successfully.',
            'plan' => $membershipPlan->fresh(),
        ]);
    }

    public function destroy(MembershipPlan $membershipPlan): JsonResponse
    {
        $membershipPlan->delete();

        return response()->json([
            'message' => 'Membership plan deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'billing_cycle' => ['required', Rule::in(['Monthly', 'Weekly', 'Daily', 'Yearly', 'One Time'])],
            'member_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['Active', 'Draft', 'Hidden'])],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'access_type' => ['required', Rule::in(['Members Only', 'Premium', 'Public'])],
            'show_in_mobile_app' => ['required', 'boolean'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'renewal_reminder_days' => ['required', 'integer', 'min:0'],
            'cancellation_policy' => ['nullable', 'string'],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
            'featured' => ['required', 'boolean'],
        ];
    }
}
