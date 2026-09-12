<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'phone',
    'expiry_date',
    'days_before_expiry',
    'reminder_type',
    'membership_amount',
    'status',
    'message',
    'provider_message_id',
    'provider_send_reference',
    'provider_status_name',
    'provider_response',
    'error_message',
    'sent_at',
])]
class MembershipExpiryReminder extends Model
{
    use HasUuids;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'membership_amount' => 'integer',
            'provider_response' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}