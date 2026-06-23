<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'price_amount',
    'currency',
    'duration_days',
    'billing_cycle',
    'member_limit',
    'status',
    'benefits',
    'access_type',
    'show_in_mobile_app',
    'trial_days',
    'grace_period_days',
    'renewal_reminder_days',
    'cancellation_policy',
    'publish_status',
    'featured',
])]
class MembershipPlan extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'duration_days' => 'integer',
            'member_limit' => 'integer',
            'benefits' => 'array',
            'show_in_mobile_app' => 'boolean',
            'trial_days' => 'integer',
            'grace_period_days' => 'integer',
            'renewal_reminder_days' => 'integer',
            'featured' => 'boolean',
        ];
    }
}
