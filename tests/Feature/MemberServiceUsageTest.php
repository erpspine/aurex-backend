<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Member;
use App\Models\MemberServiceUsage;
use App\Models\Payment;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberServiceUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_record_service_usage_for_member(): void
    {
        $user = $this->createReceptionistUser();

        $plainToken = 'test-token-123';
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'Test Token',
            'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
            'scopes' => ['dashboard'],
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Aurex Elite',
            'price_amount' => 10000,
            'currency' => 'KES',
            'duration_days' => 365,
            'billing_cycle' => 'Monthly',
            'member_limit' => 100,
            'status' => 'Active',
            'benefits' => ['Sauna - 3 sessions'],
            'access_type' => 'standard',
            'show_in_mobile_app' => true,
        ]);

        $member = Member::create([
            'full_name' => 'Sally Test',
            'email' => 'sally@example.com',
            'phone' => '0799000000',
            'membership_plan_id' => $plan->id,
            'membership_status' => 'Active',
            'amount_paid' => 10000,
            'payment_status' => 'Paid',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/member-service-usages', [
                'member_id' => $member->id,
                'service_name' => 'Sauna',
                'allowed_sessions' => 3,
                'used_sessions' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('service_usage.service_name', 'Sauna')
            ->assertJsonPath('service_usage.used_sessions', 1);

        $this->assertDatabaseHas('member_service_usages', [
            'member_id' => $member->id,
            'service_name' => 'Sauna',
            'allowed_sessions' => 3,
            'used_sessions' => 1,
        ]);
    }

    public function test_plan_benefit_services_are_visible_for_member_without_existing_usage(): void
    {
        $user = $this->createReceptionistUser();
        $plainToken = 'test-token-plan-visible';

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'Plan Visible Token',
            'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
            'scopes' => ['dashboard'],
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Aurex Team Plan',
            'price_amount' => 12000,
            'currency' => 'KES',
            'duration_days' => 30,
            'billing_cycle' => 'Monthly',
            'member_limit' => 20,
            'status' => 'Active',
            'benefits' => ['Boxing - 2 sessions'],
            'access_type' => 'standard',
            'show_in_mobile_app' => true,
        ]);

        $member = Member::create([
            'full_name' => 'Lina Boxer',
            'email' => 'lina@example.com',
            'phone' => '0700222333',
            'membership_plan_id' => $plan->id,
            'membership_status' => 'Active',
            'amount_paid' => 12000,
            'payment_status' => 'Paid',
            'user_id' => $user->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/member-service-usages?member_id='.$member->id)
            ->assertOk()
            ->assertJsonFragment([
                'service_name' => 'Boxing',
                'allowed_sessions' => 2,
            ]);

        $this->assertDatabaseHas('member_service_usages', [
            'member_id' => $member->id,
            'service_name' => 'Boxing',
            'allowed_sessions' => 2,
        ]);
    }

    public function test_plan_benefit_quota_edit_syncs_existing_member_usage(): void
    {
        $user = $this->createReceptionistUser();
        $plainToken = 'test-token-sync';

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'Sync Token',
            'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
            'scopes' => ['dashboard'],
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Aurex Boxing',
            'price_amount' => 8000,
            'currency' => 'KES',
            'duration_days' => 30,
            'billing_cycle' => 'Monthly',
            'member_limit' => 50,
            'status' => 'Active',
            'benefits' => ['Boxing - 1 sessions'],
            'access_type' => 'standard',
            'show_in_mobile_app' => true,
        ]);

        $member = Member::create([
            'full_name' => 'Ben Boxer',
            'email' => 'ben@example.com',
            'phone' => '0700111222',
            'membership_plan_id' => $plan->id,
            'membership_status' => 'Active',
            'amount_paid' => 8000,
            'payment_status' => 'Paid',
            'user_id' => $user->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/member-service-usages', [
                'member_id' => $member->id,
                'service_name' => 'Boxing',
                'allowed_sessions' => 1,
                'used_sessions' => 0,
            ])
            ->assertCreated();

        $plan->update([
            'benefits' => ['Boxing - 2 sessions'],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/member-service-usages?member_id='.$member->id)
            ->assertOk()
            ->assertJsonFragment([
                'service_name' => 'Boxing',
                'allowed_sessions' => 2,
            ]);

        $this->assertDatabaseHas('member_service_usages', [
            'member_id' => $member->id,
            'service_name' => 'Boxing',
            'allowed_sessions' => 2,
        ]);
    }

    public function test_membership_renewal_resets_service_usage_sessions(): void
    {
        $user = $this->createReceptionistUser();
        $plainToken = 'test-token-renewal';

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'Renewal Token',
            'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $plainToken),
            'scopes' => ['dashboard'],
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Aurex Renewal Plan',
            'price_amount' => 15000,
            'currency' => 'KES',
            'duration_days' => 30,
            'billing_cycle' => 'Monthly',
            'member_limit' => 20,
            'status' => 'Active',
            'benefits' => ['Sauna - 3 sessions'],
            'access_type' => 'standard',
            'show_in_mobile_app' => true,
        ]);

        $member = Member::create([
            'full_name' => 'Rene Renewal',
            'email' => 'rene@example.com',
            'phone' => '0700555666',
            'membership_plan_id' => $plan->id,
            'membership_status' => 'Active',
            'amount_paid' => 15000,
            'payment_status' => 'Paid',
            'user_id' => $user->id,
        ]);

        $member->serviceUsages()->create([
            'service_name' => 'Sauna',
            'allowed_sessions' => 3,
            'used_sessions' => 2,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/payments', [
                'payer_type' => 'Member',
                'member_id' => $member->id,
                'payment_for' => 'Membership Renewal',
                'item_name' => 'Membership Renewal',
                'membership_plan_id' => $plan->id,
                'amount' => 15000,
                'currency' => 'KES',
                'payment_method' => 'M-Pesa',
                'payment_date' => now()->toDateString(),
                'payment_status' => 'Paid',
            ])
            ->assertCreated();

        $usage = MemberServiceUsage::query()
            ->where('member_id', $member->id)
            ->where('service_name', 'Sauna')
            ->firstOrFail();

        $this->assertSame(0, $usage->used_sessions);
        $this->assertSame(3, $usage->allowed_sessions);
    }

    private function createReceptionistUser(): User
    {
        return User::create([
            'name' => 'Receptionist',
            'email' => 'receptionist@example.com',
            'phone' => '0712345678',
            'role' => 'Receptionist',
            'user_type' => 'Staff',
            'status' => 'Active',
            'password' => bcrypt('secret123'),
        ]);
    }
}
