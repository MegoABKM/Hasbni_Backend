<?php

namespace App\Saas\Filament\Widgets;

use App\Models\User;
use App\Saas\Services\KpiService;
use Filament\Widgets\Widget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaaSCountryAnalyticsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.saas-country-analytics-widget';

    public static function canView(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'finance_admin']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'countries' => Cache::remember(
                'saas:country-analytics:v1',
                now()->addHour(),
                fn (): array => $this->countryMetrics(),
            ),
        ];
    }

    /**
     * @return array<int, array{country: string, registered_users: int, active_subscriptions: int, mrr: float}>
     */
    private function countryMetrics(): array
    {
        $asOf = now();
        $subscriptions = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', 'active')
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.starts_at')
                    ->orWhere('subscriptions.starts_at', '<=', $asOf);
            })
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>=', $asOf);
            })
            ->groupBy('subscriptions.user_id')
            ->selectRaw('subscriptions.user_id')
            ->selectRaw('COUNT(subscriptions.id) as active_subscriptions')
            ->selectRaw("SUM(CASE
                WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12
                WHEN subscriptions.billing_cycle = 'lifetime' THEN 0
                ELSE plans.monthly_price
            END) as mrr");
        $countryExpression = "COALESCE(NULLIF(TRIM(users.country), ''), '".KpiService::UNKNOWN_COUNTRY."')";

        $rows = DB::table('users')
            ->leftJoinSub($subscriptions, 'subscription_metrics', function ($join): void {
                $join->on('subscription_metrics.user_id', '=', 'users.id');
            })
            ->where('users.role', config('saas.tenant.owner_role', 'tenant'))
            ->selectRaw("{$countryExpression} as country")
            ->selectRaw('COUNT(users.id) as registered_users')
            ->selectRaw('COALESCE(SUM(subscription_metrics.active_subscriptions), 0) as active_subscriptions')
            ->selectRaw('COALESCE(SUM(subscription_metrics.mrr), 0) as mrr')
            ->groupBy('users.country')
            ->orderByDesc('mrr')
            ->get();
        $countries = [];

        foreach ($rows as $row) {
            $country = (string) $row->country;
            $countries[$country] ??= [
                'country' => $country,
                'registered_users' => 0,
                'active_subscriptions' => 0,
                'mrr' => 0.0,
            ];
            $countries[$country]['registered_users'] += (int) $row->registered_users;
            $countries[$country]['active_subscriptions'] += (int) $row->active_subscriptions;
            $countries[$country]['mrr'] += (float) $row->mrr;
        }

        return collect($countries)
            ->sortByDesc('mrr')
            ->values()
            ->map(fn (array $country): array => [
                ...$country,
                'mrr' => round($country['mrr'], 2),
            ])
            ->all();
    }
}
