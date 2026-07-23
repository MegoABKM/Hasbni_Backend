<?php

namespace App\Saas\Filament\Widgets;

use App\Models\User;
use App\Saas\Models\Payment;
use App\Saas\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SystemOverviewWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $stats = Cache::remember('saas:system-overview:v1', 300, fn (): array => [
            'tenants' => User::query()->tenants()->count(),
            'new_tenants' => User::query()->tenants()->where('created_at', '>=', now()->subDays(30))->count(),
            'active_subscriptions' => Subscription::query()->activeAt(now())->count(),
            'monthly_revenue' => Payment::query()
                ->successful()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ]);

        return [
            Stat::make(__('Total Tenants'), number_format($stats['tenants']))
                ->description(__('Registered tenant accounts'))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
            Stat::make(__('New Tenants'), number_format($stats['new_tenants']))
                ->description(__('Registered in the last 30 days'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
            Stat::make(__('Active Subscriptions'), number_format($stats['active_subscriptions']))
                ->description(__('Currently active paid access'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make(__('Revenue This Month'), '$'.number_format((float) $stats['monthly_revenue'], 2))
                ->description(__('Successful subscription payments'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
