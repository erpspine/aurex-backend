<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_membership_payment_extends_member_expiry_from_billing_cycle(): void
    {
        Mail::fake();
        $headers = $this->headers();
        $plan = MembershipPlan::create([
            'name' => 'Three Month Plan',
            'price_amount' => 150000,
            'currency' => 'TZS',
            'duration_days' => 3,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ]);
        $member = Member::create([
            'full_name' => 'Expiry Test',
            'phone' => '+255700000001',
            'email' => null,
            'membership_status' => 'Pending',
            'amount_paid' => 0,
            'payment_status' => 'Pending',
        ]);

        $this->postJson('/api/payments', [
            'payer_type' => 'Member',
            'member_id' => $member->id,
            'payment_for' => 'New Membership',
            'item_name' => $plan->name,
            'membership_plan_id' => $plan->id,
            'amount' => 150000,
            'currency' => 'TZS',
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-12',
            'payment_status' => 'Paid',
        ], $headers)->assertCreated();

        $member->refresh();

        $this->assertSame($plan->id, $member->membership_plan_id);
        $this->assertSame('Active', $member->membership_status);
        $this->assertSame('Paid', $member->payment_status);
        $this->assertSame('2026-10-12', $member->expiry_date?->toDateString());
    }

    public function test_pending_payment_does_not_extend_member_expiry(): void
    {
        $headers = $this->headers();
        $plan = MembershipPlan::create([
            'name' => 'Daily Plan',
            'price_amount' => 5000,
            'currency' => 'TZS',
            'duration_days' => 1,
            'billing_cycle' => 'Daily',
            'status' => 'Active',
        ]);
        $member = Member::create([
            'full_name' => 'Pending Test',
            'phone' => '+255700000002',
            'email' => null,
            'membership_plan_id' => $plan->id,
            'membership_status' => 'Pending',
            'expiry_date' => null,
            'amount_paid' => 0,
            'payment_status' => 'Pending',
        ]);

        $this->postJson('/api/payments', [
            'payer_type' => 'Member',
            'member_id' => $member->id,
            'payment_for' => 'Membership Renewal',
            'item_name' => $plan->name,
            'membership_plan_id' => $plan->id,
            'amount' => 5000,
            'currency' => 'TZS',
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-12',
            'payment_status' => 'Pending',
        ], $headers)->assertCreated();

        $member->refresh();

        $this->assertNull($member->expiry_date);
        $this->assertSame('Pending', $member->membership_status);
    }

    public function test_quarterly_and_half_year_cycles_extend_by_three_and_six_months(): void
    {
        $headers = $this->headers();
        $quarterly = MembershipPlan::create([
            'name' => 'Quarterly',
            'price_amount' => 300000,
            'currency' => 'TZS',
            'duration_days' => 1,
            'billing_cycle' => 'Quarterly',
            'status' => 'Active',
        ]);
        $halfYear = MembershipPlan::create([
            'name' => 'Half Year',
            'price_amount' => 550000,
            'currency' => 'TZS',
            'duration_days' => 1,
            'billing_cycle' => 'Half Year',
            'status' => 'Active',
        ]);
        $quarterlyMember = $this->member('Quarterly Test');
        $halfYearMember = $this->member('Half Year Test');

        $this->postPayment($headers, $quarterlyMember->id, $quarterly);
        $this->postPayment($headers, $halfYearMember->id, $halfYear);

        $this->assertSame('2026-10-12', $quarterlyMember->fresh()->expiry_date?->toDateString());
        $this->assertSame('2027-01-12', $halfYearMember->fresh()->expiry_date?->toDateString());
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $user = User::create([
            'name' => 'Payment Admin',
            'email' => 'payment-admin@example.test',
            'user_type' => 'Admin',
            'role' => 'Administrator',
            'status' => 'Active',
            'password' => 'test-password',
        ]);
        $plainToken = Str::random(80);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'payment-test-token',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return ['Authorization' => "Bearer {$plainToken}"];
    }

    private function member(string $name): Member
    {
        return Member::create([
            'full_name' => $name,
            'phone' => '+255700'.random_int(100000, 999999),
            'email' => null,
            'membership_status' => 'Pending',
            'amount_paid' => 0,
            'payment_status' => 'Pending',
        ]);
    }

    private function postPayment(array $headers, string $memberId, MembershipPlan $plan): void
    {
        $this->postJson('/api/payments', [
            'payer_type' => 'Member',
            'member_id' => $memberId,
            'payment_for' => 'New Membership',
            'item_name' => $plan->name,
            'membership_plan_id' => $plan->id,
            'amount' => $plan->price_amount,
            'currency' => 'TZS',
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-12',
            'payment_status' => 'Paid',
        ], $headers)->assertCreated();
    }
}
