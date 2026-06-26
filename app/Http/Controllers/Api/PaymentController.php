<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MembershipPaymentMail;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'payments' => Payment::query()
                ->with(['member:id,full_name,phone,email', 'membershipPlan:id,name,price_amount,currency'])
                ->latest()
                ->get(),
        ]);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'payment' => $payment->load(['member:id,full_name,phone,email', 'membershipPlan:id,name,price_amount,currency']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payer_type' => ['required', Rule::in(['Member', 'Walk-in'])],
            'member_id' => ['nullable', 'required_if:payer_type,Member', 'uuid', 'exists:members,id'],
            'walk_in_name' => ['nullable', 'required_if:payer_type,Walk-in', 'string', 'max:255'],
            'walk_in_mobile' => ['nullable', 'required_if:payer_type,Walk-in', 'string', 'max:50'],
            'payment_for' => ['required', Rule::in([
                'Membership Renewal',
                'New Membership',
                'Daily Pass',
                'Class',
                'Other Service',
            ])],
            'item_name' => ['required', 'string', 'max:255'],
            'membership_plan_id' => ['nullable', 'uuid', 'exists:membership_plans,id'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'payment_method' => ['required', Rule::in(['Cash', 'M-Pesa', 'Airtel Money', 'Bank Transfer', 'Card'])],
            'reference_number' => ['nullable', 'string', 'max:100', 'unique:payments,reference_number'],
            'payment_date' => ['required', 'date'],
            'payment_status' => ['required', Rule::in(['Paid', 'Pending', 'Failed', 'Refunded'])],
            'notes' => ['nullable', 'string'],
        ]);

        if (($validated['payer_type'] ?? null) === 'Member') {
            $validated['walk_in_name'] = null;
            $validated['walk_in_mobile'] = null;
        } else {
            $validated['member_id'] = null;
        }

        $validated['reference_number'] = $validated['reference_number']
            ?? $this->newReferenceNumber();

        $payment = Payment::create($validated);

        // Handle membership payment email and expiry date update
        if (
            $validated['payment_status'] === 'Paid' &&
            $validated['member_id'] &&
            in_array($validated['payment_for'], ['Membership Renewal', 'New Membership'])
        ) {
            $member = Member::with('membershipPlan')->find($validated['member_id']);

            if ($member && $member->email) {
                // Calculate new expiry date
                $startDate = now();
                if ($validated['payment_for'] === 'Membership Renewal' && $member->expiry_date) {
                    // If renewing, extend from current expiry date if it's in the future
                    $currentExpiry = \Carbon\Carbon::parse($member->expiry_date);
                    if ($currentExpiry->isFuture()) {
                        $startDate = $currentExpiry;
                    }
                }

                // Get duration from membership plan
                $durationDays = 30; // Default
                if ($member->membershipPlan && $member->membershipPlan->duration_days) {
                    $durationDays = $member->membershipPlan->duration_days;
                } elseif ($validated['membership_plan_id']) {
                    $plan = MembershipPlan::find($validated['membership_plan_id']);
                    if ($plan && $plan->duration_days) {
                        $durationDays = $plan->duration_days;
                    }
                }

                $newExpiryDate = $startDate->copy()->addDays($durationDays);

                // Update member's expiry date and status
                $member->update([
                    'expiry_date' => $newExpiryDate,
                    'membership_status' => 'Active',
                    'start_date' => $validated['payment_for'] === 'New Membership' ? now() : $member->start_date,
                ]);

                // Send email notification
                try {
                    Mail::to($member->email)->send(new MembershipPaymentMail(
                        member: $member,
                        payment: $payment,
                        renewalDate: $newExpiryDate->format('F j, Y'),
                        appUrl: (string) config('app.mobile_app_url', config('app.frontend_url')),
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to send membership payment email', [
                        'member_id' => $member->id,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'payment' => $payment->load(['member:id,full_name,phone,email', 'membershipPlan:id,name,price_amount,currency']),
        ], 201);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully.',
        ]);
    }

    private function newReferenceNumber(): string
    {
        do {
            $reference = 'AUX-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Payment::query()->where('reference_number', $reference)->exists());

        return $reference;
    }
}
