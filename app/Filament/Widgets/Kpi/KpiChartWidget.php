<?php

namespace App\Filament\Widgets\Kpi;

use App\Services\KpiService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KpiChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 1,
    ];

    protected string $color = 'primary';

    protected ?string $maxHeight = '320px';

    public string $department = 'overview';

    public int $chartIndex = 0;

    public function getHeading(): ?string
    {
        $chart = $this->chart();

        return isset($chart['heading']) ? __($chart['heading']) : null;
    }

    public function getDescription(): ?string
    {
        $chart = $this->chart();

        return isset($chart['description']) ? __($chart['description']) : null;
    }

    protected function getData(): array
    {
        $chart = $this->chart();

        if (($chart['type'] ?? 'line') === 'bar') {
            $labels = [];
            $values = [];

            foreach (($chart['items'] ?? []) as $item) {
                $labels[] = __($item['label_key'], $item['label_params'] ?? []);
                $values[] = round((float) ($item['value'] ?? 0), 2);
            }

            return [
                'datasets' => [
                    [
                        'label' => __('kpi.chart.value'),
                        'data' => $values,
                        'backgroundColor' => ['#0f766e', '#2563eb', '#9333ea', '#ea580c', '#dc2626', '#64748b'],
                        'borderColor' => ['#0f766e', '#2563eb', '#9333ea', '#ea580c', '#dc2626', '#64748b'],
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        $data = $chart['data'] ?? ['labels' => [], 'values' => []];
        $values = array_map(
            fn ($value): float => round((float) $value, 2),
            $data['values'] ?? [],
        );

        return [
            'datasets' => [
                [
                    'label' => __('kpi.chart.value'),
                    'data' => $values,
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.14)',
                    'pointBackgroundColor' => '#0f766e',
                    'pointBorderColor' => '#ffffff',
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $data['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return ($this->chart()['type'] ?? 'line') === 'bar' ? 'bar' : 'line';
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
                    'ticks' => [
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'maxTicksLimit' => 8,
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

    /**
     * @return array<string, mixed>
     */
    private function chart(): array
    {
        $charts = app(KpiService::class)->dashboard($this->department, $this->filters ?? [])['charts'] ?? [];

        return $charts[$this->chartIndex] ?? [];
    }
}
