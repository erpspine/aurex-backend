<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Daily Pass',
                'price_amount' => 10000,
                'duration_days' => 1,
                'billing_cycle' => 'Daily',
                'member_limit' => null,
                'status' => 'Active',
                'benefits' => ['Gym Access', 'Locker Access'],
                'access_type' => 'Public',
                'show_in_mobile_app' => true,
                'trial_days' => 0,
                'grace_period_days' => 0,
                'renewal_reminder_days' => 0,
                'cancellation_policy' => 'Daily passes are valid for one calendar day and cannot be refunded after check-in.',
                'publish_status' => 'Published',
                'featured' => false,
            ],
            [
                'name' => 'Silver Monthly',
                'price_amount' => 80000,
                'duration_days' => 30,
                'billing_cycle' => 'Monthly',
                'member_limit' => null,
                'status' => 'Active',
                'benefits' => ['Gym Access', 'Basic Workouts', 'Progress Tracking'],
                'access_type' => 'Members Only',
                'show_in_mobile_app' => true,
                'trial_days' => 0,
                'grace_period_days' => 3,
                'renewal_reminder_days' => 5,
                'cancellation_policy' => 'Members can renew, cancel or upgrade this plan before the next billing cycle.',
                'publish_status' => 'Published',
                'featured' => false,
            ],
            [
                'name' => 'Gold Monthly',
                'price_amount' => 150000,
                'duration_days' => 30,
                'billing_cycle' => 'Monthly',
                'member_limit' => null,
                'status' => 'Active',
                'benefits' => ['Gym Access', 'Trainer Support', 'Diet Plan', 'Mobile App Access'],
                'access_type' => 'Premium',
                'show_in_mobile_app' => true,
                'trial_days' => 0,
                'grace_period_days' => 3,
                'renewal_reminder_days' => 5,
                'cancellation_policy' => 'Members can cancel or upgrade this plan from the admin desk before renewal.',
                'publish_status' => 'Published',
                'featured' => true,
            ],
            [
                'name' => 'Gold Quarterly',
                'price_amount' => 420000,
                'duration_days' => 1,
                'billing_cycle' => 'Quarterly',
                'member_limit' => null,
                'status' => 'Active',
                'benefits' => ['Gym Access', 'Trainer Support', 'Diet Plan', 'Mobile App Access'],
                'access_type' => 'Premium',
                'show_in_mobile_app' => true,
                'trial_days' => 0,
                'grace_period_days' => 5,
                'renewal_reminder_days' => 10,
                'cancellation_policy' => 'Quarterly memberships run for three months from payment date.',
                'publish_status' => 'Published',
                'featured' => false,
            ],
            [
                'name' => 'Gold Half Year',
                'price_amount' => 780000,
                'duration_days' => 1,
                'billing_cycle' => 'Half Year',
                'member_limit' => null,
                'status' => 'Active',
                'benefits' => ['Gym Access', 'Trainer Support', 'Diet Plan', 'Mobile App Access'],
                'access_type' => 'Premium',
                'show_in_mobile_app' => true,
                'trial_days' => 0,
                'grace_period_days' => 7,
                'renewal_reminder_days' => 14,
                'cancellation_policy' => 'Half-year memberships run for six months from payment date.',
                'publish_status' => 'Published',
                'featured' => false,
            ],
            [
                'name' => 'Personal Training',
                'price_amount' => 300000,
                'duration_days' => 30,
                'billing_cycle' => 'Monthly',
                'member_limit' => 30,
                'status' => 'Draft',
                'benefits' => ['Personal Coach', 'Custom Workouts', 'Diet Plan', 'Progress Review'],
                'access_type' => 'Premium',
                'show_in_mobile_app' => false,
                'trial_days' => 0,
                'grace_period_days' => 2,
                'renewal_reminder_days' => 7,
                'cancellation_policy' => 'Personal training sessions must be rescheduled at least 24 hours before the booked time.',
                'publish_status' => 'Draft',
                'featured' => false,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(
                ['name' => $plan['name']],
                ['currency' => 'TZS', ...$plan],
            );
        }
    }
}
