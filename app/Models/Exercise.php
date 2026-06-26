<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'category',
    'body_part',
    'body_part_id',
    'equipment',
    'workout_level',
    'duration',
    'sets',
    'reps',
    'rest_time',
    'status',
    'description',
    'instructions',
    'muscle_tags',
    'image_url',
    'video_url',
    'show_in_mobile_app',
    'access_type',
    'publish_status',
])]
class Exercise extends Model
{
    use HasUuids;

    public function bodyPart(): BelongsTo
    {
        return $this->belongsTo(BodyPart::class);
    }

    protected function casts(): array
    {
        return [
            'instructions' => 'array',
            'muscle_tags' => 'array',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
