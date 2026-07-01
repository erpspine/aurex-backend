<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'full_name',
    'user_id',
    'phone',
    'email',
    'access_code',
    'gender',
    'date_of_birth',
    'address',
    'membership_plan_id',
    'membership_status',
    'start_date',
    'expiry_date',
    'amount_paid',
    'payment_method',
    'payment_status',
    'height_cm',
    'weight_kg',
    'fitness_goal',
    'workout_level',
    'emergency_contact_name',
    'emergency_contact_relationship',
    'emergency_contact_phone',
])]
class Member extends Model
{
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'start_date' => 'date',
            'expiry_date' => 'date',
            'amount_paid' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'integer',
        ];
    }
}
