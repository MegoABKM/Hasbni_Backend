<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;

class SaaSMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $last30Days = Carbon::now()->subDays(30);

        $activeSubscribers = Subscription::where('status', 'active')
            ->where('ends_at', '>=', now())
            ->distinct('user_id')
            ->count('user_id');

        $revenueLast30Days = Payment::where('status', 'successful')
            ->where('paid_at', '>=', $last30Days)
            ->sum('amount');

        $arpu = $activeSubscribers > 0 ? ($revenueLast30Days / $activeSubscribers) : 0;

        $churnedUsers = Subscription::whereIn('status', ['expired', 'canceled'])
            ->where('ends_at', '>=', $last30Days)
            ->count();
            
        $totalEvaluated = $activeSubscribers + $churnedUsers;
        $churnRate = $totalEvaluated > 0 ? ($churnedUsers / $totalEvaluated) * 100 : 0;

        $churnColor = $churnRate > 10 ? 'danger' : ($churnRate > 5 ? 'warning' : 'success');

        $ltv = $churnRate > 0 ? ($arpu / ($churnRate / 100)) : 0;

        return [
            Stat::make(__('ARPU (Avg Revenue Per User)'), '$' . number_format($arpu, 2))
                ->description(__('Avg active user monthly pay'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make(__('Churn Rate'), number_format($churnRate, 2) . '%')
                ->description($churnedUsers . ' ' . __('users left in last 30 days'))
                ->descriptionIcon($churnRate > 5 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-hand-thumb-up')
                ->color($churnColor),

            Stat::make(__('LTV (Lifetime Value)'), '$' . number_format($ltv, 2))
                ->description(__('Avg total customer value'))
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),
        ];
    }
}