<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'difficulty_rank',
    'recommended_duration',
    'intensity',
    'recommended_sets',
    'recommended_reps',
    'description',
    'calories_range',
    'rest_time',
    'training_frequency',
    'suitable_for',
    'trainer_instructions',
    'safety_notes',
    'linked_workouts',
    'linked_exercises',
    'status',
    'cover_image_url',
    'publish_status',
    'show_in_mobile_app',
    'access_type',
])]
class WorkoutLevel extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'difficulty_rank' => 'integer',
            'linked_workouts' => 'integer',
            'linked_exercises' => 'integer',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
