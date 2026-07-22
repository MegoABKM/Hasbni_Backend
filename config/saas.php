<?php

use App\Models\User;

return [
    'tenant' => [
        'model' => User::class,
        'foreign_key' => 'user_id',
        'owner_key' => 'id',
        'role_column' => 'role',
        'owner_role' => 'tenant',
        'country_column' => 'country',
    ],

    'kpi' => [
        'cache_ttl' => env('SAAS_KPI_CACHE_TTL', 300),
        'max_custom_range_days' => env('SAAS_KPI_MAX_CUSTOM_RANGE_DAYS', 366),
    ],

    'statuses' => [
        'subscriptions_active' => 'active',
        'subscriptions_churned' => ['expired', 'canceled'],
        'subscription_statuses' => ['active', 'canceled', 'expired'],
        'payments_successful' => 'successful',
        'payments_failed' => 'failed',
        'support_open' => 'open',
        'support_resolved' => ['answered', 'closed'],
    ],
];
