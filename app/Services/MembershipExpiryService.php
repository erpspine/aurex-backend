<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipPlan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class MembershipExpiryService
{
    public function extendMemberFromPayment(
        Member $member,
        MembershipPlan $plan,
        CarbonInterface $paymentDate,
        bool $isNewMembership = false,
    ): CarbonInterface {
        $baseDate = $paymentDate->copy()->startOfDay();

        if (! $isNewMembership && $member->expiry_date) {
            $currentExpiry = Carbon::parse($member->expiry_date)->startOfDay();

            if ($currentExpiry->greaterThan($baseDate)) {
                $baseDate = $currentExpiry;
            }
        }

        return $this->calculateExpiry($baseDate, $plan)->endOfDay();
    }

    public function calculateExpiry(
        CarbonInterface $baseDate,
        MembershipPlan $plan,
    ): CarbonInterface {
        $quantity = max(1, (int) $plan->duration_days);
        $cycle = strtolower(trim((string) $plan->billing_cycle));
        $date = $baseDate->copy();

        return match ($cycle) {
            'daily' => $date->addDays($quantity),
            'weekly' => $this->looksLikeStoredDays($quantity, 7)
                ? $date->addDays($quantity)
                : $date->addWeeks($quantity),
            'monthly' => $quantity >= 28
                ? $date->addDays($quantity)
                : $date->addMonthsNoOverflow($quantity),
            'quarterly' => $quantity >= 90
                ? $date->addDays($quantity)
                : $date->addMonthsNoOverflow($quantity * 3),
            'half year', 'half-year', 'semi annual', 'semi-annual' => $quantity >= 180
                ? $date->addDays($quantity)
                : $date->addMonthsNoOverflow($quantity * 6),
            'yearly' => $quantity >= 365
                ? $date->addDays($quantity)
                : $date->addYearsNoOverflow($quantity),
            default => $date->addDays($quantity),
        };
    }

    private function looksLikeStoredDays(int $quantity, int $cycleDays): bool
    {
        return $quantity > $cycleDays && $quantity % $cycleDays === 0;
    }
}
