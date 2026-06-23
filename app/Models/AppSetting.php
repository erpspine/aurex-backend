<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'value',
])]
class AppSetting extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
