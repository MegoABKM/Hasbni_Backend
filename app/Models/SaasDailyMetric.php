<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasDailyMetric extends Model
{
    protected $fillable = [
        'date',
        'country',
        'mrr',
        'active_subscriptions',
        'new_signups',
        'churned_subscriptions',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mrr' => 'decimal:2',
            'active_subscriptions' => 'integer',
            'new_signups' => 'integer',
            'churned_subscriptions' => 'integer',
        ];
    }
}
