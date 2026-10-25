<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'log_date',
    'cups',
])]
class MemberWaterLog extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'cups' => 'integer',
        ];
    }
}
