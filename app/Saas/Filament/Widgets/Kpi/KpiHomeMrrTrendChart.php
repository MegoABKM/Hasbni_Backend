<?php

namespace App\Saas\Filament\Widgets\Kpi;

use App\Models\User;
use App\Saas\Services\KpiService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class KpiHomeMrrTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $color = 'primary';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'finance_admin']);
    }

    public function getHeading(): ?string
    {
        return __('kpi.chart.mrr_trend');
    }

    public function getDescription(): ?string
    {
        return __('kpi.chart.mrr_trend.description');
    }

    protected function getData(): array
    {
        $chart = app(KpiService::class)->homeMrrChart();
        $data = $chart['data'];

        return [
            'datasets' => [
                [
                    'label' => __('kpi.metric.mrr'),
                    'data' => $data['values'],
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.14)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'rtl' => app()->getLocale() === 'ar',
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
            ],
        ];
    }
}
