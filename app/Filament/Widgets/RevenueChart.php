<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    public function getHeading(): string
    {
        return 'Monthly Recurring Revenue (MRR)';
    }

    protected function getData(): array
    {
        $revenueByMonth = Payment::query()
            ->selectRaw('MONTH(paid_at) as month_number, SUM(amount) as revenue')
            ->where('status', 'successful')
            ->whereYear('paid_at', Carbon::now()->year)
            ->whereNotNull('paid_at')
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->pluck('revenue', 'month_number');

        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[] = round((float) ($revenueByMonth[$month] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $monthlyData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.16)',
                    'fill' => true,
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#10b981',
                    'pointBorderColor' => '#ffffff',
                    'tension' => 0.38,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
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
            'animation' => [
                'duration' => 850,
                'easing' => 'easeOutQuart',
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
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