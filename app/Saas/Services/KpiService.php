<?php

namespace App\Saas\Services;

use App\Models\User;
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
                $planDistribution = $this->planDistribution($range['end'], $country);
                $breakdownSections = [
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
                            [
                                'label_key' => 'kpi.metric.net_revenue_retention',
                                'value' => $this->formatValue($current['nrr'], 'percentage'),
                            ],
                            [
                                'label_key' => 'kpi.metric.expansion_revenue',
                                'value' => $this->formatValue($current['expansion_revenue'], 'currency'),
                            ],
                            [
                                'label_key' => 'kpi.metric.contraction_revenue',
                                'value' => $this->formatValue($current['contraction_revenue'], 'currency'),
                            ],
                        ],
                    ],
                ];

                if ($planDistribution !== []) {
                    $breakdownSections[] = [
                        'heading_key' => 'kpi.section.plan_distribution.heading',
                        'description_key' => 'kpi.section.plan_distribution.description',
                        'items' => array_map(
                            fn (array $plan): array => [
                                'label_key' => $plan['name'],
                                'value' => $this->formatValue($plan['active_subscriptions'], 'number'),
                            ],
                            $planDistribution,
                        ),
                    ];
                }

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
                        'sections' => $breakdownSections,
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
     * @return array{labels: array<int, string>, new_mrr: array<int, float>, expansion_mrr: array<int, float>, contraction_mrr: array<int, float>, churned_mrr: array<int, float>}
     */
    public function mrrMovementChart(array $filters = []): array
    {
        $range = $this->resolveDateRange($filters);
        $country = $this->normalizeCountry($filters['country'] ?? null);
        $start = $range['start']->copy()->startOfDay();
        $end = $range['end']->copy()->startOfDay();

        return Cache::remember(
            $this->cacheKey('mrr-movement-chart', [
                'country' => $country,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'locale' => app()->getLocale(),
            ]),
            $this->cacheTtl(),
            function () use ($country, $start, $end): array {
                $monthly = $start->diffInDays($end) + 1 > 120;
                $rows = $this->hasDailyMetricsTable()
                    ? $this->dailyMetricQuery($country)
                        ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                        ->orderBy('date')
                        ->get([
                            'date',
                            'new_mrr',
                            'expansion_mrr',
                            'contraction_mrr',
                            'churned_mrr',
                        ])
                    : collect();
                $buckets = [];

                foreach ($rows as $row) {
                    $date = Carbon::parse((string) $row->date);
                    $key = $monthly ? $date->format('Y-m') : $date->toDateString();
                    $buckets[$key] ??= [
                        'new_mrr' => 0.0,
                        'expansion_mrr' => 0.0,
                        'contraction_mrr' => 0.0,
                        'churned_mrr' => 0.0,
                    ];

                    foreach (array_keys($buckets[$key]) as $metric) {
                        $buckets[$key][$metric] += (float) ($row->{$metric} ?? 0);
                    }
                }

                $labels = [];
                $series = [
                    'new_mrr' => [],
                    'expansion_mrr' => [],
                    'contraction_mrr' => [],
                    'churned_mrr' => [],
                ];
                $cursor = $monthly ? $start->copy()->startOfMonth() : $start->copy();
                $last = $monthly ? $end->copy()->startOfMonth() : $end->copy();

                while ($cursor->lessThanOrEqualTo($last)) {
                    $key = $monthly ? $cursor->format('Y-m') : $cursor->toDateString();
                    $labels[] = $monthly
                        ? $cursor->translatedFormat('M Y')
                        : $cursor->translatedFormat('M j');

                    foreach (array_keys($series) as $metric) {
                        $series[$metric][] = round((float) ($buckets[$key][$metric] ?? 0), 2);
                    }

                    if ($monthly) {
                        $cursor->addMonthNoOverflow();
                    } else {
                        $cursor->addDay();
                    }
                }

                return ['labels' => $labels, ...$series];
            },
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
        $movements = $this->mrrMovementAggregates(
            $dayEnd,
            $dayStart->copy()->subDay()->endOfDay(),
        );
        $countries = array_values(array_unique([
            ...array_keys($subscriptions),
            ...array_keys($signups),
            ...array_keys($churn),
            ...array_keys($movements),
        ]));

        sort($countries);

        $timestamp = now();
        $records = [];

        foreach ($countries as $country) {
            $records[] = [
                'date' => $metricDate->toDateString(),
                'country' => $country,
                'mrr' => round((float) ($subscriptions[$country]['mrr'] ?? 0), 2),
                'new_mrr' => round((float) ($movements[$country]['new_mrr'] ?? 0), 2),
                'expansion_mrr' => round((float) ($movements[$country]['expansion_mrr'] ?? 0), 2),
                'contraction_mrr' => round((float) ($movements[$country]['contraction_mrr'] ?? 0), 2),
                'churned_mrr' => round((float) ($movements[$country]['churned_mrr'] ?? 0), 2),
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
            'new_mrr' => round(array_sum(array_column($movements, 'new_mrr')), 2),
            'expansion_mrr' => round(array_sum(array_column($movements, 'expansion_mrr')), 2),
            'contraction_mrr' => round(array_sum(array_column($movements, 'contraction_mrr')), 2),
            'churned_mrr' => round(array_sum(array_column($movements, 'churned_mrr')), 2),
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
                    ->where('account_type', $this->tenantAccountType())
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
            ->where('account_type', $this->tenantAccountType())
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
        $mrrMovements = $this->mrrMovementSummary($start, $end, $country);
        $nrr = (float) $openingSnapshot['mrr'] > 0
            ? (($openingSnapshot['mrr'] + $mrrMovements['expansion_mrr'] - $mrrMovements['contraction_mrr'] - $mrrMovements['churned_mrr']) / $openingSnapshot['mrr']) * 100
            : 0.0;

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
            'nrr' => round($nrr, 2),
            'expansion_revenue' => $mrrMovements['expansion_mrr'],
            'contraction_revenue' => $mrrMovements['contraction_mrr'],
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

    private function sumDailyMetricValue(string $column, Carbon $start, Carbon $end, ?string $country = null): float
    {
        if (! $this->hasDailyMetricsTable()) {
            return 0.0;
        }

        return round((float) $this->dailyMetricQuery($country)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum($column), 2);
    }

    /**
     * @return array{new_mrr: float, expansion_mrr: float, contraction_mrr: float, churned_mrr: float}
     */
    private function mrrMovementSummary(Carbon $start, Carbon $end, ?string $country = null): array
    {
        return [
            'new_mrr' => $this->sumDailyMetricValue('new_mrr', $start, $end, $country),
            'expansion_mrr' => $this->sumDailyMetricValue('expansion_mrr', $start, $end, $country),
            'contraction_mrr' => $this->sumDailyMetricValue('contraction_mrr', $start, $end, $country),
            'churned_mrr' => $this->sumDailyMetricValue('churned_mrr', $start, $end, $country),
        ];
    }

    private function platformRevenue(Carbon $start, Carbon $end, ?string $country = null): float
    {
        $query = DB::table('payments')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->where('users.account_type', $this->tenantAccountType())
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
            ->where('users.account_type', $this->tenantAccountType())
            ->whereIn('subscriptions.status', $this->subscriptionStatuses())
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
     * Return active subscription counts grouped by plan at a point in time.
     *
     * @return array<int, array{name: string, active_subscriptions: int}>
     */
    private function planDistribution(Carbon $asOf, ?string $country = null): array
    {
        $query = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('users.account_type', $this->tenantAccountType())
            ->where('subscriptions.status', 'active')
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.starts_at')
                    ->orWhere('subscriptions.starts_at', '<=', $asOf);
            })
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>=', $asOf);
            })
            ->select([
                'plans.name',
            ])
            ->selectRaw('COUNT(subscriptions.id) as active_subscriptions')
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('active_subscriptions')
            ->orderBy('plans.name');

        if ($country !== null) {
            $this->whereCountry($query, $country, 'users');
        }

        return $query
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'active_subscriptions' => (int) $row->active_subscriptions,
            ])
            ->all();
    }

    /**
     * @return array<string, array{country: string, mrr: float}>
     */
    private function userMrrAggregates(Carbon $asOf): array
    {
        $users = DB::table('users')
            ->where('account_type', $this->tenantAccountType())
            ->where('created_at', '<=', $asOf)
            ->select(['id', 'country'])
            ->get();
        $aggregates = [];

        foreach ($users as $user) {
            $aggregates[(string) $user->id] = [
                'country' => (string) $this->countryKey((string) $user->country),
                'mrr' => 0.0,
            ];
        }

        if ($users->isEmpty()) {
            return $aggregates;
        }

        $rows = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('users.account_type', $this->tenantAccountType())
            ->where('users.created_at', '<=', $asOf)
            ->whereIn('subscriptions.status', $this->subscriptionStatuses())
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.starts_at')
                    ->orWhere('subscriptions.starts_at', '<=', $asOf);
            })
            ->where(function (Builder $query) use ($asOf): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>=', $asOf);
            })
            ->select('subscriptions.user_id')
            ->selectRaw("SUM(CASE
                WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12
                WHEN subscriptions.billing_cycle = 'lifetime' THEN 0
                ELSE plans.monthly_price
            END) as mrr")
            ->groupBy('subscriptions.user_id')
            ->get();

        foreach ($rows as $row) {
            $userId = (string) $row->user_id;

            if (isset($aggregates[$userId])) {
                $aggregates[$userId]['mrr'] = round((float) $row->mrr, 2);
            }
        }

        return $aggregates;
    }

    /**
     * @return array<string, array{new_mrr: float, expansion_mrr: float, contraction_mrr: float, churned_mrr: float}>
     */
    private function mrrMovementAggregates(Carbon $today, Carbon $yesterday): array
    {
        $todayUsers = $this->userMrrAggregates($today);
        $yesterdayUsers = $this->userMrrAggregates($yesterday);
        $movements = [];

        foreach (array_unique([...array_keys($todayUsers), ...array_keys($yesterdayUsers)]) as $userId) {
            $todayUser = $todayUsers[$userId] ?? null;
            $yesterdayUser = $yesterdayUsers[$userId] ?? null;
            $todayMrr = (float) ($todayUser['mrr'] ?? 0);
            $yesterdayMrr = (float) ($yesterdayUser['mrr'] ?? 0);
            $country = $todayUser['country'] ?? $yesterdayUser['country'] ?? self::UNKNOWN_COUNTRY;
            $movements[$country] ??= [
                'new_mrr' => 0.0,
                'expansion_mrr' => 0.0,
                'contraction_mrr' => 0.0,
                'churned_mrr' => 0.0,
            ];

            if ($todayMrr > 0 && $yesterdayUser === null) {
                $movements[$country]['new_mrr'] += $todayMrr;
            } elseif ($todayMrr > $yesterdayMrr) {
                $movements[$country]['expansion_mrr'] += $todayMrr - $yesterdayMrr;
            } elseif ($todayMrr > 0 && $todayMrr < $yesterdayMrr) {
                $movements[$country]['contraction_mrr'] += $yesterdayMrr - $todayMrr;
            } elseif ($yesterdayMrr > 0 && $todayMrr <= 0) {
                $movements[$country]['churned_mrr'] += $yesterdayMrr;
            }
        }

        foreach ($movements as $country => $movement) {
            foreach ($movement as $metric => $value) {
                $movements[$country][$metric] = round($value, 2);
            }
        }

        return $movements;
    }

    /**
     * @return array<string, int>
     */
    private function signupAggregates(Carbon $start, Carbon $end): array
    {
        $countryExpression = $this->countryExpression('users');

        $rows = DB::table('users')
            ->where('account_type', $this->tenantAccountType())
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
            ->where('users.account_type', $this->tenantAccountType())
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

    private function tenantAccountType(): string
    {
        return User::ACCOUNT_TYPE_TENANT;
    }

    /**
     * @return array<int, string>
     */
    private function subscriptionStatuses(): array
    {
        return array_values(config('saas.statuses.subscription_statuses', [
            'active',
            'canceled',
            'expired',
        ]));
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
