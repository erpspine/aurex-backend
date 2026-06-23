<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'goal',
    'workout_level',
    'diet_type',
    'daily_calories',
    'duration',
    'description',
    'protein',
    'carbs',
    'fat',
    'fiber',
    'meals',
    'meal_instructions',
    'nutritionist_notes',
    'cover_image_url',
    'show_in_mobile_app',
    'access_type',
    'publish_status',
])]
class DietPlan extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'meals' => 'array',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
