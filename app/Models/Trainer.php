<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'full_name',
    'phone',
    'email',
    'gender',
    'date_of_birth',
    'address',
    'specialty',
    'experience',
    'certification',
    'status',
    'assigned_classes',
    'assigned_clients',
    'rating',
    'bio',
    'availability_days',
    'start_time',
    'end_time',
    'payment_type',
    'rate_amount',
    'payment_method',
    'payment_reference',
    'allow_dashboard_login',
    'trainer_app_access',
    'role',
])]
class Trainer extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'assigned_classes' => 'integer',
            'assigned_clients' => 'integer',
            'rating' => 'decimal:1',
            'availability_days' => 'array',
            'rate_amount' => 'integer',
            'allow_dashboard_login' => 'boolean',
            'trainer_app_access' => 'boolean',
        ];
    }
}
