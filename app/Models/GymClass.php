<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'class_type',
    'workout_level',
    'status',
    'capacity',
    'booked_slots',
    'location',
    'description',
    'trainer_name',
    'class_date',
    'start_time',
    'end_time',
    'repeat_schedule',
    'booking_required',
    'booking_deadline',
    'cancellation_deadline',
    'late_entry_limit',
    'waitlist_limit',
    'notes',
    'price_amount',
    'currency',
    'show_in_mobile_app',
    'allow_booking_from_app',
    'access_type',
])]
class GymClass extends Model
{
    use HasUuids;

    protected $table = 'gym_classes';

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'booked_slots' => 'integer',
            'waitlist_limit' => 'integer',
            'price_amount' => 'integer',
            'class_date' => 'date',
            'booking_required' => 'boolean',
            'show_in_mobile_app' => 'boolean',
            'allow_booking_from_app' => 'boolean',
        ];
    }
}
