<?php

namespace App\Saas\Models;

use Illuminate\Database\Eloquent\Model;

class SaasDailyMetric extends Model
{
    protected $fillable = [
        'date',
        'country',
        'mrr',
        'new_mrr',
        'expansion_mrr',
        'contraction_mrr',
        'churned_mrr',
        'active_subscriptions',
        'new_signups',
        'churned_subscriptions',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mrr' => 'decimal:2',
            'new_mrr' => 'decimal:2',
            'expansion_mrr' => 'decimal:2',
            'contraction_mrr' => 'decimal:2',
            'churned_mrr' => 'decimal:2',
            'active_subscriptions' => 'integer',
            'new_signups' => 'integer',
            'churned_subscriptions' => 'integer',
        ];
    }
}
