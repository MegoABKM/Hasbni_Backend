<?php

namespace App\Saas\Filament\Widgets\Kpi;

use App\Saas\Services\KpiService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MrrMovementChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '340px';

    public string $department = 'saas';

    public function getHeading(): ?string
    {
        return __('kpi.chart.mrr_movements');
    }

    public function getDescription(): ?string
    {
        return __('kpi.chart.mrr_movements.description');
    }

    protected function getData(): array
    {
        $chart = app(KpiService::class)->mrrMovementChart($this->filters ?? []);

        return [
            'datasets' => [
                [
                    'label' => __('kpi.metric.new_mrr'),
                    'data' => $chart['new_mrr'],
                    'backgroundColor' => '#16a34a',
                    'borderColor' => '#15803d',
                    'borderWidth' => 1,
                ],
                [
                    'label' => __('kpi.metric.expansion_mrr'),
                    'data' => $chart['expansion_mrr'],
                    'backgroundColor' => '#2563eb',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 1,
                ],
                [
                    'label' => __('kpi.metric.contraction_mrr'),
                    'data' => $chart['contraction_mrr'],
                    'backgroundColor' => '#f97316',
                    'borderColor' => '#ea580c',
                    'borderWidth' => 1,
                ],
                [
                    'label' => __('kpi.metric.churned_mrr'),
                    'data' => $chart['churned_mrr'],
                    'backgroundColor' => '#dc2626',
                    'borderColor' => '#b91c1c',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'rtl' => app()->getLocale() === 'ar',
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'maxTicksLimit' => 10,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
            ],
        ];
    }
}
