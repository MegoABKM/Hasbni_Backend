<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSnapshot extends Model
{
    protected $table = 'kpi_snapshots';

    protected $fillable = [
        'snapshot_date',
        'country',
        'platform_revenue',
        'mrr',
        'arr',
        'active_subscriptions',
        'new_subscriptions',
        'churned_subscriptions',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'platform_revenue' => 'float',
        'mrr' => 'float',
        'arr' => 'float',
        'active_subscriptions' => 'integer',
        'new_subscriptions' => 'integer',
        'churned_subscriptions' => 'integer',
    ];
}