<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KpiService
{
    private ?string $country = null;

    private array $tableExistsCache = [];

    private array $columnExistsCache = [];

    public function dashboard(string $department, array $filters = []): array
    {
        $range = $this->resolveDateRange($filters);
        $country = $this->normalizeCountryFilter($filters['country'] ?? null);

        // ðŸš€ Auto-record daily SaaS KPI snapshot into the database safely
        $this->recordDailySnapshot(now()->toDateString(), $country);

        $cacheKey = $this->cacheKey('dashboard', [
            'department' => $department,
            'period' => $range['period'],
            'comparison' => $range['comparison'],
            'start' => $range['start']->toDateString(),
            'end' => $range['end']->toDateString(),
            'previous_start' => $range['previous_start']->toDateString(),
            'previous_end' => $range['previous_end']->toDateString(),
            'country' => $country,
        ]);

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($department, $range, $country): array {
            $this->country = $country;

            try {
                return $this->dashboardUncached($department, $range);
            } finally {
                $this->country = null;
            }
        });
    }

    /**
     * ðŸš€ Restored method for KpiHomeOverviewWidget
     */
    public function homeMetrics(): array
    {
        return Cache::remember(
            $this->cacheKey('home-metrics', ['date' => now()->toDateString()]),
            $this->cacheTtl(),
            fn (): array => $this->homeMetricsUncached(),
        );
    }

    private function homeMetricsUncached(): array
    {
        $range = $this->resolveDateRange(['period' => 'last_30_days']);
        $activeSubscribers = $this->activeSubscriptionsCount();
        $mrr = $this->mrrAt(now());
        $previousMrr = $this->mrrAt(now()->subDays(30));
        $saasRevenue = $this->saasSubscriptionRevenue($range['start'], $range['end']);
        $previousSaasRevenue = $this->saasSubscriptionRevenue($range['previous_start'], $range['previous_end']);

        return [
            $this->metric('kpi.metric.mrr', $mrr, $previousMrr, 'currency', true, tooltip: 'kpi.description.mrr'),
            $this->metric('kpi.metric.arr', $mrr * 12, $previousMrr * 12, 'currency', true, tooltip: 'kpi.description.arr'),
            $this->metric('kpi.metric.active_subscriptions', $activeSubscribers, $this->activeSubscriptionsCount(now()->subDays(30)), 'number', true, tooltip: 'kpi.description.active_subscriptions'),
            $this->metric('SaaS Subscription Revenue', $saasRevenue, $previousSaasRevenue, 'currency', true, tooltip: 'Total payments collected for Hasbni subscriptions'),
        ];
    }

    /**
     * ðŸš€ Restored method for KpiHomeMrrTrendChart
     */
    public function homeMrrChart(): array
    {
        return Cache::remember(
            $this->cacheKey('home-mrr-chart', ['month' => now()->format('Y-m')]),
            $this->cacheTtl(),
            fn (): array => [
                'type' => 'line',
                'heading' => 'kpi.chart.mrr_trend',
                'description' => 'kpi.chart.mrr_trend.description',
                'data' => $this->monthlySeries(6, fn (Carbon $date): float => $this->mrrAt($date->copy()->endOfMonth())),
            ],
        );
    }

    /**
     * ðŸš€ Safely saves SaaS KPI snapshot directly into the `kpi_snapshots` table
     */
    public function recordDailySnapshot(string $date, ?string $country = null)
    {
        if (! $this->tableExists('kpi_snapshots')) {
            return null;
        }

        $asOf = Carbon::parse($date);

        $mrr = $this->mrrAt($asOf);
        $arr = $mrr * 12;
        $platformRevenue = $this->saasSubscriptionRevenue($asOf->copy()->startOfDay(), $asOf->copy()->endOfDay());
        $activeSubscribers = $this->activeSubscriptionsCount($asOf);
        $newSubscribers = $this->newPaidSubscriptions($asOf->copy()->startOfDay(), $asOf->copy()->endOfDay());
        $churnedSubscribers = (int) ($this->churnRate($asOf->copy()->startOfDay(), $asOf->copy()->endOfDay()));

        return DB::table('kpi_snapshots')->updateOrInsert(
            [
                'snapshot_date' => $date,
                'country' => $country,
            ],
            [
                'platform_revenue' => $platformRevenue,
                'mrr' => $mrr,
                'arr' => $arr,
                'active_subscriptions' => $activeSubscribers,
                'new_subscriptions' => $newSubscribers,
                'churned_subscriptions' => $churnedSubscribers,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * ðŸš€ SaaS Platform Subscription Revenue (From `payments` table, NOT POS sales)
     */
    private function saasSubscriptionRevenue(Carbon $start, Carbon $end): float
    {
        if (! $this->tableExists('payments')) {
            return 0.0;
        }

        $query = $this->table('payments')
            ->where('status', 'successful')
            ->whereBetween('paid_at', [$start, $end]);

        return (float) $this->applyCountryFilter($query, $this->tableName('payments'))->sum('amount');
    }

    public function countryOptions(): array
    {
        return Cache::remember($this->cacheKey('country-options'), $this->cacheTtl(), function (): array {
            if (! $this->tableExists('users') || ! $this->columnExists('users', 'country')) {
                return [];
            }

            return $this->table('users')
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->distinct()
                ->orderBy('country')
                ->pluck('country', 'country')
                ->all();
        });
    }

    private function dashboardUncached(string $department, array $range): array
    {
        return match ($department) {
            'sales' => $this->saasSalesDashboard($range),
            'saas' => $this->saasAnalyticsDashboard($range),
            default => $this->overviewDashboard($range),
        };
    }

    public function resolveDateRange(array $filters = []): array
    {
        $period = (string) ($filters['period'] ?? 'last_30_days');
        $today = now();

        [$start, $end] = match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'last_7_days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$today->copy()->firstOfQuarter()->startOfDay(), $today->copy()->endOfDay()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            'last_year' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            'custom' => $this->customDateRange($filters),
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $days = max(1, $start->diffInDays($end) + 1);
        $comparison = (string) ($filters['comparison'] ?? 'previous_period');

        if ($comparison === 'previous_year') {
            $previousStart = $start->copy()->subYear();
            $previousEnd = $end->copy()->subYear();
        } else {
            $previousEnd = $start->copy()->subSecond();
            $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();
        }

        return [
            'period' => $period,
            'comparison' => $comparison,
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    private function overviewDashboard(array $range): array
    {
        $mrr = $this->mrrAt($range['end']);
        $previousMrr = $this->mrrAt($range['previous_end']);
        $saasRevenue = $this->saasSubscriptionRevenue($range['start'], $range['end']);
        $previousSaasRevenue = $this->saasSubscriptionRevenue($range['previous_start'], $range['previous_end']);
        $activeSubscribers = $this->activeSubscriptionsCount($range['end']);
        $previousSubscribers = $this->activeSubscriptionsCount($range['previous_end']);

        return $this->makeDashboard('overview', [
            $this->metric('kpi.metric.mrr', $mrr, $previousMrr, 'currency', true, tooltip: 'kpi.description.mrr'),
            $this->metric('kpi.metric.arr', $mrr * 12, $previousMrr * 12, 'currency', true, tooltip: 'kpi.description.arr'),
            $this->metric('SaaS Subscription Revenue', $saasRevenue, $previousSaasRevenue, 'currency', true, tooltip: 'Total payments collected for Hasbni subscriptions'),
            $this->metric('kpi.metric.active_subscriptions', $activeSubscribers, $previousSubscribers, 'number', true, tooltip: 'kpi.description.active_subscriptions'),
        ], [
            $this->lineChart('SaaS Revenue Trend', 'Monthly subscription income saved in DB', $this->monthlySeries(12, fn (Carbon $date): float => $this->mrrAt($date->copy()->endOfMonth()))),
            $this->barChart('Subscription Status', 'Active vs Expired SaaS Accounts', $this->subscriptionStatusMix()),
        ], []);
    }

    private function saasSalesDashboard(array $range): array
    {
        $saasRevenue = $this->saasSubscriptionRevenue($range['start'], $range['end']);
        $previousSaasRevenue = $this->saasSubscriptionRevenue($range['previous_start'], $range['previous_end']);
        $newPaid = $this->newPaidSubscriptions($range['start'], $range['end']);
        $previousNewPaid = $this->newPaidSubscriptions($range['previous_start'], $range['previous_end']);

        return $this->makeDashboard('sales', [
            $this->metric('SaaS Billing Revenue', $saasRevenue, $previousSaasRevenue, 'currency', true, tooltip: 'Subscription sales from Hasbni billing'),
            $this->metric('New Paid Subscribers', $newPaid, $previousNewPaid, 'number', true, tooltip: 'New tenants upgrading to paid plans'),
        ], [
            $this->lineChart('SaaS Billing Trend', 'Subscription revenue over selected period', $this->periodSeries($range, fn (Carbon $start, Carbon $end): float => $this->saasSubscriptionRevenue($start, $end))),
        ], []);
    }

    private function saasAnalyticsDashboard(array $range): array
    {
        return $this->overviewDashboard($range);
    }

    private function mrrAt(Carbon $asOf): float
    {
        if (! $this->tableExists('subscriptions') || ! $this->tableExists('plans')) {
            return 0.0;
        }

        $subscriptionsTable = $this->tableName('subscriptions');
        $plansTable = $this->tableName('plans');

        $query = $this->table('subscriptions')
            ->join($plansTable, "{$plansTable}.id", '=', "{$subscriptionsTable}.plan_id")
            ->where("{$subscriptionsTable}.status", 'active')
            ->where(function ($query) use ($asOf): void {
                $query->whereNull($this->tableName('subscriptions') . '.starts_at')
                    ->orWhere($this->tableName('subscriptions') . '.starts_at', '<=', $asOf);
            })
            ->where(function ($query) use ($asOf): void {
                $query->whereNull($this->tableName('subscriptions') . '.ends_at')
                    ->orWhere($this->tableName('subscriptions') . '.ends_at', '>=', $asOf);
            });

        return (float) $this->applyCountryFilter($query, $subscriptionsTable)
            ->sum(DB::raw("
                CASE
                    WHEN {$subscriptionsTable}.billing_cycle = 'yearly' THEN {$plansTable}.yearly_price / 12
                    WHEN {$subscriptionsTable}.billing_cycle = 'lifetime' THEN 0
                    ELSE {$plansTable}.monthly_price
                END
            "));
    }

    private function activeSubscriptionsCount(?Carbon $asOf = null): int
    {
        if (! $this->tableExists('subscriptions')) {
            return 0;
        }

        $asOf ??= now();

        $query = $this->table('subscriptions')
            ->where('status', 'active')
            ->where(function ($query) use ($asOf): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $asOf);
            })
            ->where(function ($query) use ($asOf): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $asOf);
            });

        return $this->applyCountryFilter($query, $this->tableName('subscriptions'))
            ->distinct('user_id')
            ->count('user_id');
    }

    private function newPaidSubscriptions(Carbon $start, Carbon $end): int
    {
        if (! $this->tableExists('subscriptions')) {
            return 0;
        }

        $query = $this->table('subscriptions')->whereBetween('starts_at', [$start, $end]);

        return $this->applyCountryFilter($query, $this->tableName('subscriptions'))->distinct('user_id')->count('user_id');
    }

    private function churnRate(Carbon $start, Carbon $end): float
    {
        if (! $this->tableExists('subscriptions')) {
            return 0.0;
        }

        $churnedQuery = $this->table('subscriptions')
            ->whereIn('status', ['expired', 'canceled'])
            ->whereBetween('ends_at', [$start, $end]);

        $churned = $this->applyCountryFilter($churnedQuery, $this->tableName('subscriptions'))->count();
        $base = max(1, $this->activeSubscriptionsCount($start));

        return round(($churned / $base) * 100, 2);
    }

    private function subscriptionStatusMix(): array
    {
        if (! $this->tableExists('subscriptions')) {
            return [];
        }

        $statuses = ['active', 'canceled', 'expired'];

        $countsQuery = $this->table('subscriptions')
            ->selectRaw('status, COUNT(*) as aggregate_count')
            ->whereIn('status', $statuses)
            ->groupBy('status');

        $counts = $this->applyCountryFilter($countsQuery, $this->tableName('subscriptions'))
            ->pluck('aggregate_count', 'status')
            ->all();

        return array_map(fn (string $status): array => [
            'label_key' => "kpi.status.{$status}",
            'value' => (int) ($counts[$status] ?? 0),
        ], $statuses);
    }

    private function tableExists(string $table): bool
    {
        $tableName = $this->tableName($table);
        return $this->tableExistsCache[$tableName] ??= Schema::hasTable($tableName);
    }

    private function columnExists(string $table, string $column): bool
    {
        $tableName = $this->tableName($table);
        $key = "{$tableName}.{$column}";
        return $this->columnExistsCache[$key] ??= ($this->tableExists($table) && Schema::hasColumn($tableName, $column));
    }

    private function cacheTtl(): int
    {
        return (int) config('saas.kpi.cache_ttl', 300);
    }

    private function cacheKey(string $name, array $parts = []): string
    {
        return 'saas:kpi:' . $name . ':' . md5(json_encode($parts));
    }

    private function tableName(string $key): string
    {
        return (string) config("saas.tables.{$key}", $key);
    }

    private function table(string $key): \Illuminate\Database\Query\Builder
    {
        return DB::table($this->tableName($key));
    }

    private function normalizeCountryFilter(mixed $country): ?string
    {
        $country = is_string($country) ? trim($country) : null;
        return $country === '' ? null : $country;
    }

    private function shouldFilterByCountry(): bool
    {
        return filled($this->country)
            && $this->tableExists('users')
            && $this->columnExists('users', 'country');
    }

    private function applyCountryFilter(\Illuminate\Database\Query\Builder $query, string $tenantTable, ?string $tenantForeignKey = null): \Illuminate\Database\Query\Builder
    {
        if (! $this->shouldFilterByCountry()) {
            return $query;
        }

        $usersTable = $this->tableName('users');
        $tenantForeignKey ??= 'user_id';

        return $query->whereExists(function ($subQuery) use ($usersTable, $tenantTable, $tenantForeignKey): void {
            $subQuery
                ->selectRaw('1')
                ->from($usersTable)
                ->whereColumn("{$usersTable}.id", "{$tenantTable}.{$tenantForeignKey}")
                ->where("{$usersTable}.country", $this->country);
        });
    }

    private function makeDashboard(string $key, array $metrics, array $charts, array $breakdowns): array
    {
        return [
            'key' => $key,
            'title' => "kpi.page.{$key}.title",
            'description' => "kpi.page.{$key}.description",
            'metrics' => $metrics,
            'charts' => $charts,
            'breakdowns' => $breakdowns,
        ];
    }

    private function customDateRange(array $filters): array
    {
        try {
            $start = ! empty($filters['start_date'])
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : now()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $start = now()->subDays(29)->startOfDay();
        }

        try {
            $end = ! empty($filters['end_date'])
                ? Carbon::parse($filters['end_date'])->endOfDay()
                : now()->endOfDay();
        } catch (\Throwable) {
            $end = now()->endOfDay();
        }

        return [$start, $end];
    }

    private function metric(string $labelKey, float|int $current, float|int $previous, string $format, bool $higherIsBetter, ?array $sparkline = null, ?string $tooltip = null): array
    {
        $change = $previous == 0 ? 0 : round((($current - $previous) / abs($previous)) * 100, 1);
        $direction = abs((float) $current - (float) $previous) < 0.0001 ? 'flat' : ((float) $current > (float) $previous ? 'up' : 'down');
        $improved = $direction === 'flat' || ($higherIsBetter ? $direction === 'up' : $direction === 'down');

        return [
            'label_key' => $labelKey,
            'tooltip_key' => $tooltip ?? $labelKey,
            'value' => $this->formatValue($current, $format),
            'raw' => round((float) $current, 4),
            'previous_raw' => round((float) $previous, 4),
            'change' => $change,
            'change_text' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
            'direction' => $direction,
            'higher_is_better' => $higherIsBetter,
            'color' => $direction === 'flat' ? 'gray' : ($improved ? 'success' : 'danger'),
            'icon' => $direction === 'flat' ? 'heroicon-m-minus' : ($direction === 'up' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
            'sparkline' => $sparkline ?? [],
        ];
    }

    private function formatValue(float|int $value, string $format): string
    {
        return match ($format) {
            'currency' => '$' . number_format((float) $value, 2),
            'percent' => number_format((float) $value, 2) . '%',
            'ratio' => number_format((float) $value, 2) . 'x',
            default => number_format((float) $value),
        };
    }

    private function lineChart(string $heading, string $description, array $series): array
    {
        return ['type' => 'line', 'heading' => $heading, 'description' => $description, 'data' => $series];
    }

    private function barChart(string $heading, string $description, array $items): array
    {
        return ['type' => 'bar', 'heading' => $heading, 'description' => $description, 'items' => $items];
    }

    private function monthlySeries(int $months, callable $resolver): array
    {
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->copy()->startOfMonth()->subMonths($i);
            $labels[] = $date->format('Y-m');
            $values[] = round((float) $resolver($date), 2);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function periodSeries(array $range, callable $resolver): array
    {
        $labels = [];
        $values = [];
        $start = $range['start']->copy()->startOfDay();
        $end = $range['end']->copy()->endOfDay();
        $days = max(1, $start->diffInDays($end) + 1);
        $buckets = min(12, $days);
        $bucketSize = (int) ceil($days / $buckets);

        for ($index = 0; $index < $buckets; $index++) {
            $bucketStart = $start->copy()->addDays($index * $bucketSize)->startOfDay();
            $bucketEnd = $bucketStart->copy()->addDays($bucketSize - 1)->endOfDay();
            if ($bucketStart->greaterThan($end)) break;
            if ($bucketEnd->greaterThan($end)) $bucketEnd = $end->copy();

            $labels[] = $bucketStart->format('Y-m-d');
            $values[] = round((float) $resolver($bucketStart, $bucketEnd), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
