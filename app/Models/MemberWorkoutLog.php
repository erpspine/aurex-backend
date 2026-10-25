<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'client_id',
    'exercise_id',
    'exercise_name',
    'body_part',
    'completed_at',
    'duration_seconds',
    'sets',
])]
class MemberWorkoutLog extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'sets' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'exercise_id' => $this->exercise_id,
            'exercise_name' => $this->exercise_name,
            'body_part' => $this->body_part,
            'completed_at' => $this->completed_at->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'sets' => array_map(static fn (array $set): array => [
                'reps' => (int) ($set['reps'] ?? 0),
                'weight_kg' => (float) ($set['weight_kg'] ?? 0),
            ], $this->sets ?? []),
        ];
    }
}
