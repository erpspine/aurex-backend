<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'member_name',
    'member_phone',
    'plan_name',
    'check_in_at',
    'check_out_at',
    'entry_method',
    'gym_zone',
    'staff_notes',
    'status',
])]
class Attendance extends Model
{
    use HasUuids;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }
}
