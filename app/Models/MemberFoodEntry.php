<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'client_id',
    'entry_date',
    'meal_type',
    'entry_time',
    'name',
    'servings',
    'amount_grams',
    'calories',
    'protein',
    'carbs',
    'fat',
])]
class MemberFoodEntry extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->entry_date->toDateString(),
            'meal_type' => $this->meal_type,
            'time' => $this->entry_time,
            'name' => $this->name,
            'servings' => (float) $this->servings,
            'amount_grams' => (float) $this->amount_grams,
            'calories' => (float) $this->calories,
            'protein' => (float) $this->protein,
            'carbs' => (float) $this->carbs,
            'fat' => (float) $this->fat,
        ];
    }
}
