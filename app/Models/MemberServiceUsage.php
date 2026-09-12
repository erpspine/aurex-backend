<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'service_name',
    'allowed_sessions',
    'used_sessions',
    'notes',
])]
class MemberServiceUsage extends Model
{
    use HasUuids;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    protected function casts(): array
    {
        return [
            'allowed_sessions' => 'integer',
            'used_sessions' => 'integer',
        ];
    }
}
