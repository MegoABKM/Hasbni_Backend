<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Plan;

class PlanStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $freePlanId = Plan::where('name', 'Free')->value('id');
        $proPlanId = Plan::where('name', 'Pro')->value('id');
        $enterprisePlanId = Plan::where('name', 'Enterprise')->value('id');

        $freeCount = User::whereHas('subscription', function($q) use ($freePlanId) {
            $q->where('plan_id', $freePlanId)->where('status', 'active');
        })->count();

        $proCount = User::whereHas('subscription', function($q) use ($proPlanId) {
            $q->where('plan_id', $proPlanId)->where('status', 'active');
        })->count();

        $entCount = User::whereHas('subscription', function($q) use ($enterprisePlanId) {
            $q->where('plan_id', $enterprisePlanId)->where('status', 'active');
        })->count();

        $bannedCount = User::where('is_banned', true)->count();

        return [
            Stat::make('Pro Users', $proCount)->color('success')->icon('heroicon-o-star'),
            Stat::make('Enterprise Users', $entCount)->color('primary')->icon('heroicon-o-building-office'),
            Stat::make('Free Users', $freeCount)->color('gray'),
            Stat::make('Banned Accounts', $bannedCount)->color('danger')->icon('heroicon-o-no-symbol'),
        ];
    }
}