<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payer_type',
    'member_id',
    'walk_in_name',
    'walk_in_mobile',
    'payment_for',
    'item_name',
    'membership_plan_id',
    'class_name',
    'amount',
    'currency',
    'payment_method',
    'reference_number',
    'payment_date',
    'payment_status',
    'notes',
])]
class Payment extends Model
{
    use HasUuids;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_date' => 'date',
        ];
    }
}
