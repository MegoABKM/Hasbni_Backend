<?php

namespace App\Saas\Services;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

final class ExecutiveDashboardMetrics
{
    public function __construct(private readonly KpiService $kpis) {}

    /**
     * @return array<string, float|int>
     */
    public function snapshot(): array
    {
        return $this->kpis->summary(['period' => 'last_30_days']);
    }

    public function money(float|int $amount): string
    {
        $amount = (float) $amount;

        return match (true) {
            abs($amount) >= 1_000_000 => '$'.number_format($amount / 1_000_000, 2).'M',
            abs($amount) >= 10_000 => '$'.number_format($amount / 1_000, 1).'K',
            default => '$'.number_format($amount, 2),
        };
    }

    public function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    public function comparison(float|int $current, float|int $previous): Htmlable
    {
        $change = $this->percentChange($current, $previous);
        $formatted = match (true) {
            $change === null => __('kpi.change.new_activity'),
            abs($change) < 0.05 => __('kpi.change.stable'),
            default => ($change > 0 ? '+' : '').number_format($change, 1).'%',
        };

        return new HtmlString('<span dir="ltr">'.e($formatted).'</span> '.e(__('kpi.change.vs_comparison')));
    }

    public function trendIcon(float|int $current, float|int $previous): string
    {
        if (abs($current - $previous) < 0.0001) {
            return 'heroicon-m-minus';
        }

        return $current > $previous
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    public function trendColor(float|int $current, float|int $previous, bool $higherIsBetter = true): string
    {
        if (abs($current - $previous) < 0.0001) {
            return 'gray';
        }

        $improved = $higherIsBetter ? $current > $previous : $current < $previous;

        return $improved ? 'success' : 'danger';
    }
}
