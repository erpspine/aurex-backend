<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'category',
    'brand',
    'model',
    'serial_number',
    'location',
    'primary_muscle_group',
    'secondary_muscle_group',
    'supported_level',
    'linked_exercises',
    'purchase_date',
    'purchase_price',
    'last_service_date',
    'next_service_date',
    'status',
    'maintenance_priority',
    'description',
    'safety_instructions',
    'operation_video_url',
    'show_in_mobile_app',
    'access_type',
    'publish_status',
])]
class Equipment extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'linked_exercises' => 'integer',
            'purchase_price' => 'integer',
            'purchase_date' => 'date',
            'last_service_date' => 'date',
            'next_service_date' => 'date',
            'show_in_mobile_app' => 'boolean',
        ];
    }
}
