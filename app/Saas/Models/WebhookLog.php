<?php

declare(strict_types=1);

namespace App\Saas\Models;

use Illuminate\Database\Eloquent\Model;

final class WebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'status',
        'error_message',
        'attempts',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
