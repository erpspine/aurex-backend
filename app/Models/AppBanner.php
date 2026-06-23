<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'subtitle',
    'banner_type',
    'target_audience',
    'button_text',
    'button_action',
    'action_url',
    'display_order',
    'start_date',
    'end_date',
    'publish_status',
    'show_in_mobile_app',
    'priority',
    'allow_dismiss',
    'background_style',
    'text_alignment',
    'background_color',
    'accent_color',
    'description',
    'image_url',
])]
class AppBanner extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'show_in_mobile_app' => 'boolean',
            'allow_dismiss' => 'boolean',
        ];
    }
}
