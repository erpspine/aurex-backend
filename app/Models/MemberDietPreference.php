<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'calorie_goal',
    'diet_plan_id',
])]
class MemberDietPreference extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'calorie_goal' => 'integer',
        ];
    }
}
