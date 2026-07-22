<?php

return [
    'tenant' => [
        'model' => App\Models\User::class,
        'foreign_key' => 'user_id',
        'owner_key' => 'id',
        'role_column' => 'role',
        'owner_role' => 'shop_owner',
        'country_column' => 'country',
    ],

    'kpi' => [
        'cache_ttl' => env('SAAS_KPI_CACHE_TTL', 300),
        'max_custom_range_days' => env('SAAS_KPI_MAX_CUSTOM_RANGE_DAYS', 366),
    ],

    'tables' => [
        'cash_transactions' => 'cash_transactions',
        'customers' => 'customers',
        'employees' => 'employees',
        'expenses' => 'expenses',
        'inventory_movements' => 'inventory_movements',
        'plans' => 'plans',
        'products' => 'products',
        'sale_items' => 'sale_items',
        'sales' => 'sales',
        'subscriptions' => 'subscriptions',
        'suppliers' => 'suppliers',
        'support_tickets' => 'support_tickets',
        'users' => 'users',
    ],

    'statuses' => [
        'sales_voided' => 'voided',
        'sales_unpaid' => ['partial', 'unpaid'],
        'subscriptions_active' => 'active',
        'subscriptions_churned' => ['expired', 'canceled'],
        'subscription_statuses' => ['active', 'canceled', 'expired'],
        'support_open' => 'open',
        'support_resolved' => ['answered', 'closed'],
        'support_statuses' => ['open', 'answered', 'closed'],
    ],
];
