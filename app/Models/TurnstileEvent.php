<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_event_id',
    'agent_id',
    'member_id',
    'attendance_id',
    'card_number',
    'event_time',
    'direction',
    'controller_serial',
    'door',
    'reader',
    'event_type',
    'controller_allowed',
])]
class TurnstileEvent extends Model
{
    use HasUuids;

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'controller_allowed' => 'boolean',
            'door' => 'integer',
            'reader' => 'integer',
            'event_type' => 'integer',
        ];
    }
}
