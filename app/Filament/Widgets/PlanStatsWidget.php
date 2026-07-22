<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Plan;

class PlanStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $stats = [];
        $plans = Plan::where('is_active', true)->get();

        foreach ($plans as $plan) {
            $count = User::whereHas('subscription', function($q) use ($plan) {
                $q->where('plan_id', $plan->id)->where('status', 'active');
            })->count();

            $color = match(strtolower($plan->name)) {
                'free' => 'gray',
                'pro' => 'success',
                'enterprise' => 'primary',
                default => 'info',
            };

            $icon = match(strtolower($plan->name)) {
                'enterprise' => 'heroicon-o-building-office',
                'pro' => 'heroicon-o-star',
                default => 'heroicon-o-user',
            };

            $stats[] = Stat::make("{$plan->name} Users", $count)
                        ->color($color)
                        ->icon($icon);
        }

        $bannedCount = User::where('is_banned', true)->count();

        $stats[] = Stat::make('Banned Accounts', $bannedCount)
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol');

        return $stats;
    }
}
