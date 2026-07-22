<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class KpiService
{
    public const ALL_COUNTRIES = '__all__';

    public const UNKNOWN_COUNTRY = '__unknown__';

    private ?bool $dailyMetricsTableExists = null;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(string $dashboard = 'saas', array $filters = []): array
    {
        $range = $this->resolveDateRange($filters);
        $country = $this->normalizeCountry($filters['country'] ?? null);

        return Cache::remember(
            $this->cacheKey('dashboard', [
                'dashboard' => $dashboard,
                'country' => $country,
                'start' => $range['start']->toDateString(),
                'end' => $range['end']->toDateString(),
                'previous_start' => $range['previous_start']->toDateString(),
                'previous_end' => $range['previous_end']->toDateString(),
            ]),
            $this->cacheTtl(),
            function () use ($range, $country): array {
                $current = $this->summaryForRange($range['start'], $range['end'], $country);
                $previous = $this->summaryForRange($range['previous_start'], $range['previous_end'], $country);

                return [
                    'key' => 'saas',
                    'title' => 'kpi.page.saas.title',
                    'description' => 'kpi.page.saas.description',
                    'metrics' => $this->metricCards($current, $previous),
                    'charts' => [
                        [
                            'type' => 'line',
                            'heading' => 'kpi.chart.mrr_trend',
                            'description' => 'kpi.chart.mrr_trend.description',
                            'data' => $this->dailyMrrSeries($range['start'], $range['end'], $country),
                        ],
                        [
                            'type' => 'bar',
                            'heading' => 'kpi.chart.subscription_movement',
                            'description' => 'kpi.chart.subscription_movement.description',
                            'items' => [
                                [
                                    'label_key' => 'kpi.metric.new_signups',
                                    'value' => $current['new_signups'],
                                ],
                                [
                                    'label_key' => 'kpi.metric.churned_subscriptions',
                                    'value' => $current['churned_subscriptions'],
                                ],
                            ],
                        ],
                    ],
                    'breakdowns' => [
                        'sections' => [
                            [
                                'heading_key' => 'kpi.section.subscription_health.heading',
                                'description_key' => 'kpi.section.subscription_health.description',
                                'items' => [
                                    [
                                        'label_key' => 'kpi.metric.average_revenue_per_account',
                                        'value' => $this->formatValue($current['arpu'], 'currency'),
                                    ],
                                    [
                                        'label_key' => 'kpi.metric.new_signups',
                                        'value' => $this->formatValue($current['new_signups'], 'number'),
                                    ],
                                    [
                                        'label_key' => 'kpi.metric.churned_subscriptions',
                                        'value' => $this->formatValue($current['churned_subscriptions'], 'number'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            },
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function homeMetrics(): array
    {
        return Cache::remember(
            $this->cacheKey('home-metrics'),
            $this->cacheTtl(),
            function (): array {
                $range = $this->resolveDateRange(['period' => 'last_30_days']);
                $current = $this->summaryForRange($range['start'], $range['end']);
                $previous = $this->summaryForRange($range['previous_start'], $range['previous_end']);

                return $this->metricCards($current, $previous);
            },
        );
    }

    /**
     * @return array{type: string, heading: string, description: string, data: array{labels: array<int, string>, values: array<int, float>}}
     */
    public function homeMrrChart(): array
    {
        return Cache::remember(
            $this->cacheKey('home-mrr-chart'),
            $this->cacheTtl(),
            fn (): array => [
                'type' => 'line',
                'heading' => 'kpi.chart.mrr_trend',
                'description' => 'kpi.chart.mrr_trend.description',
                'data' => $this->monthlyMrrSeries(6),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summary(array $filters = []): array
    {
        $range = $this->resolveDateRange($filters);

        return $this->summaryForRange(
            $range['start'],
            $range['end'],
            $this->normalizeCountry($filters['country'] ?? null),
        );
    }

    /**
     * Rebuild one complete daily snapshot. This is the only routine that performs
     * the subscription-to-plan MRR aggregation used by historical dashboards.
     */
    public function recordDailyMetrics(CarbonInterface|string|null $date = null): int
    {
        if (! $this->hasDailyMetricsTable()) {
            return 0;
        }

        $metricDate = $date instanceof CarbonInterface
            ? Carbon::instance($date)->startOfDay()
            : Carbon::parse($date ?? now()->subDay()->toDateString())->startOfDay();
        $dayStart = $metricDate->copy()->startOfDay();
        $dayEnd = $metricDate->copy()->endOfDay();

        $subscriptions = $this->activeSubscriptionAggregates($dayEnd);
        $signups = $this->signupAggregates($dayStart, $dayEnd);
        $churn = $this->churnAggregates($dayStart, $dayEnd);
        $countries = array_values(array_unique([
            ...array_keys($subscriptions),
            ...array_keys($signups),
            ...array_keys($churn),
        ]));

        sort($countries);

        $timestamp = now();
        $records = [];

        foreach ($countries as $country) {
            $records[] = [
                'date' => $metricDate->toDateString(),
                'country' => $country,
                'mrr' => round((float) ($subscriptions[$country]['mrr'] ?? 0), 2),
                'active_subscriptions' => (int) ($subscriptions[$country]['active_subscriptions'] ?? 0),
                'new_signups' => (int) ($signups[$country] ?? 0),
                'churned_subscriptions' => (int) ($churn[$country] ?? 0),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $records[] = [
            'date' => $metricDate->toDateString(),
            'country' => self::ALL_COUNTRIES,
            'mrr' => round(array_sum(array_column($subscriptions, 'mrr')), 2),
            'active_subscriptions' => array_sum(array_column($subscriptions, 'active_subscriptions')),
            'new_signups' => array_sum($signups),
            'churned_subscriptions' => array_sum($churn),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        DB::transaction(function () use ($metricDate, $records): void {
            DB::table('saas_daily_metrics')
                ->whereDate('date', $metricDate->toDateString())
                ->delete();

            DB::table('saas_daily_metrics')->insert($records);
        });

        $this->bumpCacheVersion();
        Cache::forget('saas:country-analytics:v1');
        Cache::forget('saas:decision-support:v2');
        Cache::forget('saas:system-overview:v1');

        return count($records);
    }

    /**
     * @return array<string, string>
     */
    public function countryOptions(): array
    {
        return Cache::remember(
            $this->cacheKey('country-options'),
            3600,
            function (): array {
                $query = DB::table('users')
                    ->where('role', $this->tenantRole())
                    ->whereNotNull('country')
                    ->where('country', '!=', '')
                    ->distinct()
                    ->orderBy('country');

                return $query->pluck('country', 'country')->all();
            },
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{period: string, comparison: string, start: Carbon, end: Carbon, previous_start: Carbon, previous_end: Carbon}
     */
    public function resolveDateRange(array $filters = []): array
    {
        $period = (string) ($filters['period'] ?? 'last_30_days');
        $today = now();

        [$start, $end] = match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'last_7_days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'last_30_days' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
            'last_90_days' => [$today->copy()->subDays(89)->startOfDay(), $today->copy()->endOfDay()],
            'last_6_months' => [$today->copy()->subMonthsNoOverflow(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            'last_year' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            'all_time' => $this->allTimeDateRange($today),
            'custom' => $this->customDateRange($filters),
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $maximumDays = max(1, (int) config('saas.kpi.max_custom_range_days', 366));

        if ($period !== 'all_time' && $start->diffInDays($end) + 1 > $maximumDays) {
            $start = $end->copy()->subDays($maximumDays - 1)->startOfDay();
        }

        $days = max(1, (int) $start->diffInDays($end) + 1);
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

    /**
     * Resolve the complete available SaaS history without calculating historical
     * subscription MRR on demand.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function allTimeDateRange(Carbon $today): array
    {
        $firstMetricDate = $this->hasDailyMetricsTable()
            ? DB::table('saas_daily_metrics')
                ->where('country', self::ALL_COUNTRIES)
                ->min('date')
            : null;

        $firstSignupDate = DB::table('users')
            ->where('role', $this->tenantRole())
            ->min('created_at');

        $firstDate = collect([$firstMetricDate, $firstSignupDate])
            ->filter()
            ->map(fn (string $date): Carbon => Carbon::parse($date))
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp())
            ->first();

        return [
            ($firstDate ?? $today)->copy()->startOfDay(),
            $today->copy()->endOfDay(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function summaryForRange(Carbon $start, Carbon $end, ?string $country = null): array
    {
        $endingSnapshot = $this->snapshotAt($end, $country);
        $openingSnapshot = $this->snapshotAt($start->copy()->subDay(), $country);
        $newSignups = $this->sumDailyMetric('new_signups', $start, $end, $country);
        $churnedSubscriptions = $this->sumDailyMetric('churned_subscriptions', $start, $end, $country);
        $activeAtStart = (int) $openingSnapshot['active_subscriptions'];
        $churnRate = $activeAtStart > 0
            ? round(($churnedSubscriptions / $activeAtStart) * 100, 2)
            : 0.0;
        $mrr = (float) $endingSnapshot['mrr'];
        $activeSubscriptions = (int) $endingSnapshot['active_subscriptions'];
        $arpu = $activeSubscriptions > 0 ? $mrr / $activeSubscriptions : 0.0;
        $ltv = $churnRate > 0 ? $arpu / ($churnRate / 100) : 0.0;

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'active_subscriptions' => $activeSubscriptions,
            'churn_rate' => $churnRate,
            'ltv' => round($ltv, 2),
            'arpu' => round($arpu, 2),
            'platform_revenue' => round($this->platformRevenue($start, $end, $country), 2),
            'new_signups' => $newSignups,
            'churned_subscriptions' => $churnedSubscriptions,
        ];
    }

    /**
     * @param  array<string, float|int>  $current
     * @param  array<string, float|int>  $previous
     * @return array<int, array<string, mixed>>
     */
    private function metricCards(array $current, array $previous): array
    {
        return [
            $this->metric('kpi.metric.mrr', $current['mrr'], $previous['mrr'], 'currency', true, 'kpi.description.mrr'),
            $this->metric('kpi.metric.arr', $current['arr'], $previous['arr'], 'currency', true, 'kpi.description.arr'),
            $this->metric('kpi.metric.active_subscriptions', $current['active_subscriptions'], $previous['active_subscriptions'], 'number', true, 'kpi.description.active_subscriptions'),
            $this->metric('kpi.metric.churn_rate', $current['churn_rate'], $previous['churn_rate'], 'percentage', false, 'kpi.description.churn_rate'),
            $this->metric('kpi.metric.ltv', $current['ltv'], $previous['ltv'], 'currency', true, 'kpi.description.ltv'),
            $this->metric('kpi.metric.platform_revenue', $current['platform_revenue'], $previous['platform_revenue'], 'currency', true, 'kpi.description.platform_revenue'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metric(
        string $labelKey,
        float|int $current,
        float|int $previous,
        string $format,
        bool $higherIsBetter,
        string $tooltipKey,
    ): array {
        $difference = (float) $current - (float) $previous;
        $direction = abs($difference) < 0.0001 ? 'flat' : ($difference > 0 ? 'up' : 'down');
        $change = (float) $previous === 0.0
            ? 0.0
            : round(($difference / abs((float) $previous)) * 100, 1);
        $improved = $direction === 'flat' || ($higherIsBetter ? $direction === 'up' : $direction === 'down');

        return [
            'label_key' => $labelKey,
            'tooltip_key' => $tooltipKey,
            'value' => $this->formatValue($current, $format),
            'raw' => round((float) $current, 4),
            'previous_raw' => round((float) $previous, 4),
            'change' => $change,
            'change_text' => (float) $previous === 0.0 && (float) $current > 0.0
                ? 'kpi.change.new_activity'
                : ($change >= 0 ? '+' : '').number_format($change, 1).'%',
            'direction' => $direction,
            'color' => $direction === 'flat' ? 'gray' : ($improved ? 'success' : 'danger'),
            'icon' => match ($direction) {
                'up' => 'heroicon-m-arrow-trending-up',
                'down' => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-m-minus',
            },
            'sparkline' => [],
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyMrrSeries(Carbon $start, Carbon $end, ?string $country = null): array
    {
        $labels = [];
        $values = [];
        $rows = $this->hasDailyMetricsTable()
            ? $this->dailyMetricQuery($country)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('date')
                ->pluck('mrr', 'date')
            : collect();
        $carry = (float) $this->snapshotAt($start->copy()->subDay(), $country)['mrr'];
        $cursor = $start->copy()->startOfDay();
        $lastDate = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($lastDate)) {
            $date = $cursor->toDateString();

            if ($rows->has($date)) {
                $carry = (float) $rows->get($date);
            }

            $labels[] = $cursor->translatedFormat('M j');
            $values[] = round($carry, 2);
            $cursor->addDay();
        }

        return compact('labels', 'values');
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function monthlyMrrSeries(int $months): array
    {
        $labels = [];
        $values = [];

        for ($index = $months - 1; $index >= 0; $index--) {
            $month = now()->startOfMonth()->subMonths($index);
            $labels[] = $month->translatedFormat('M Y');
            $values[] = round((float) $this->snapshotAt($month->copy()->endOfMonth())['mrr'], 2);
        }

        return compact('labels', 'values');
    }

    /**
     * @return array{mrr: float, active_subscriptions: int}
     */
    private function snapshotAt(Carbon $date, ?string $country = null): array
    {
        if ($this->hasDailyMetricsTable()) {
            $row = $this->dailyMetricQuery($country)
                ->whereDate('date', '<=', $date->toDateString())
                ->orderByDesc('date')
                ->first(['mrr', 'active_subscriptions']);

            if ($row !== null) {
                return [
                    'mrr' => (float) $row->mrr,
                    'active_subscriptions' => (int) $row->active_subscriptions,
                ];
            }
        }

        if ($date->isToday() || $date->isFuture()) {
            return $this->liveSnapshot($country, $date);
        }

        return ['mrr' => 0.0, 'active_subscriptions' => 0];
    }

    /**
     * @return array{mrr: float, active_subscriptions: int}
     */
    private function liveSnapshot(?string $country, Carbon $asOf): array
    {
        $aggregates = $this->activeSubscriptionAggregates($asOf);

        if ($country !== null) {
            $countryKey = $this->countryKey($country);

            return [
                'mrr' => (float) ($aggregates[$countryKey]['mrr'] ?? 0),
                'active_subscriptions' => (int) ($aggregates[$countryKey]['active_subscriptions'] ?? 0),
            ];
        }

        return [
            'mrr' => (float) array_sum(array_column($aggregates, 'mrr')),
            'active_subscriptions' => (int) array_sum(array_column($aggregates, 'active_subscriptions')),
        ];
    }

    private function sumDailyMetric(string $column, Carbon $start, Carbon $end, ?string $country = null): int
    {
        if (! $this->hasDailyMetricsTable()) {
            return 0;
        }

        return (int) $this->dailyMetricQuery($country)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum($column);
    }

    private function platformRevenue(Carbon $start, Carbon $end, ?string $country = null): float
    {
        $query = DB::table('payments')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->where('users.role', $this->tenantRole())
            ->where('payments.status', 'successful')
            ->whereBetween('payments.paid_at', [$start, $end]);

        if ($country !== null) {
            $this->whereCountry($query, $country, 'users');
        }

        return (float) $query->sum('payments.amount');
    }

    /**
     * @return array<string, array{mrr: float, active_subscriptions: int}>
     */
    private function activeSubscriptionAggregates(Carbon $asOf): array
    {
        $countryExpression = $this->countryExpression('users');
        $rows = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('users.role', $this->tenantRole())
            ->where('subscriptions.status', 'active')
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.starts_at')
                    ->orWhere('subscriptions.starts_at', '<=', $asOf);
            })
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>=', $asOf);
            })
            ->selectRaw("{$countryExpression} as country")
            ->selectRaw('COUNT(subscriptions.id) as active_subscriptions')
            ->selectRaw("SUM(CASE
                WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12
                WHEN subscriptions.billing_cycle = 'lifetime' THEN 0
                ELSE plans.monthly_price
            END) as mrr")
            ->groupBy('users.country')
            ->get();

        $aggregates = [];

        foreach ($rows as $row) {
            $country = (string) $row->country;
            $aggregates[$country] ??= ['mrr' => 0.0, 'active_subscriptions' => 0];
            $aggregates[$country]['mrr'] += (float) $row->mrr;
            $aggregates[$country]['active_subscriptions'] += (int) $row->active_subscriptions;
        }

        return $aggregates;
    }

    /**
     * @return array<string, int>
     */
    private function signupAggregates(Carbon $start, Carbon $end): array
    {
        $countryExpression = $this->countryExpression('users');

        $rows = DB::table('users')
            ->where('role', $this->tenantRole())
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$countryExpression} as country, COUNT(id) as aggregate")
            ->groupBy('users.country')
            ->get();

        $aggregates = [];

        foreach ($rows as $row) {
            $country = (string) $row->country;
            $aggregates[$country] = ($aggregates[$country] ?? 0) + (int) $row->aggregate;
        }

        return $aggregates;
    }

    /**
     * @return array<string, int>
     */
    private function churnAggregates(Carbon $start, Carbon $end): array
    {
        $countryExpression = $this->countryExpression('users');

        $rows = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('users.role', $this->tenantRole())
            ->whereIn('subscriptions.status', ['expired', 'canceled'])
            ->whereBetween('subscriptions.ends_at', [$start, $end])
            ->selectRaw("{$countryExpression} as country, COUNT(subscriptions.id) as aggregate")
            ->groupBy('users.country')
            ->get();

        $aggregates = [];

        foreach ($rows as $row) {
            $country = (string) $row->country;
            $aggregates[$country] = ($aggregates[$country] ?? 0) + (int) $row->aggregate;
        }

        return $aggregates;
    }

    private function dailyMetricQuery(?string $country = null): Builder
    {
        return DB::table('saas_daily_metrics')
            ->where('country', $country === null ? self::ALL_COUNTRIES : $this->countryKey($country));
    }

    private function whereCountry(Builder $query, string $country, string $table): void
    {
        $query->whereRaw($this->countryExpression($table).' = ?', [$this->countryKey($country)]);
    }

    private function countryExpression(string $table): string
    {
        return "COALESCE(NULLIF(TRIM({$table}.country), ''), '".self::UNKNOWN_COUNTRY."')";
    }

    private function countryKey(string $country): string
    {
        return trim($country) === '' ? self::UNKNOWN_COUNTRY : trim($country);
    }

    private function normalizeCountry(mixed $country): ?string
    {
        if (! is_string($country)) {
            return null;
        }

        $country = trim($country);

        return $country === '' ? null : $country;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    private function customDateRange(array $filters): array
    {
        try {
            $start = filled($filters['start_date'] ?? null)
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : now()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $start = now()->subDays(29)->startOfDay();
        }

        try {
            $end = filled($filters['end_date'] ?? null)
                ? Carbon::parse($filters['end_date'])->endOfDay()
                : now()->endOfDay();
        } catch (\Throwable) {
            $end = now()->endOfDay();
        }

        return [$start, $end];
    }

    private function formatValue(float|int $value, string $format): string
    {
        return match ($format) {
            'currency' => '$'.number_format((float) $value, 2),
            'percentage' => number_format((float) $value, 2).'%',
            default => number_format((float) $value, 0),
        };
    }

    private function hasDailyMetricsTable(): bool
    {
        return $this->dailyMetricsTableExists ??= Schema::hasTable('saas_daily_metrics');
    }

    private function tenantRole(): string
    {
        return (string) config('saas.tenant.owner_role', 'tenant');
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('saas.kpi.cache_ttl', 300));
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function cacheKey(string $name, array $parts = []): string
    {
        $version = (int) Cache::get('saas:kpi:version', 1);

        return 'saas:kpi:'.$version.':'.$name.':'.md5((string) json_encode($parts));
    }

    private function bumpCacheVersion(): void
    {
        Cache::forever('saas:kpi:version', (int) Cache::get('saas:kpi:version', 1) + 1);
    }
}
