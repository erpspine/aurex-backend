<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'goal',
    'workout_level',
    'workout_type',
    'duration',
    'calories_burn',
    'description',
    'days_count',
    'workout_days',
    'exercises',
    'warm_up',
    'trainer_notes',
    'cool_down',
    'cover_image_url',
    'publish_status',
    'show_in_mobile_app',
    'access_type',
])]
class Workout extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'days_count' => 'integer',
            'workout_days' => 'array',
            'exercises' => 'array',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
