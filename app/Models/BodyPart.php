<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'image_url',
    'status',
    'show_in_mobile_app',
    'publish_status',
])]
class BodyPart extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'show_in_mobile_app' => 'boolean',
        ];
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
