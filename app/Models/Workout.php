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
            'exercises' => 'array',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
