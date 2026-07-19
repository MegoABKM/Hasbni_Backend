<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\ExecutiveDashboardMetrics as Metrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutiveRevenueTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Revenue, Expense, and Profit Trend';

    protected ?string $description = 'Platform revenue is subscription income. Tenant revenue and profit are operational totals from POS data.';

    protected string $color = 'primary';

    protected function getData(): array
    {
        $labels = [];
        $platformRevenue = [];
        $tenantRevenue = [];
        $tenantExpenses = [];
        $tenantNetProfit = [];

        for ($index = 11; $index >= 0; $index--) {
            $month = now()->startOfMonth()->subMonths($index);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $labels[] = $month->format('M Y');
            $platformRevenue[] = round((float) Payment::query()
                ->where('status', 'successful')
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount'), 2);
            $tenantRevenue[] = round((float) Sale::query()
                ->whereBetween('created_at', [$start, $end])
                ->where('payment_status', '!=', 'voided')
                ->sum(DB::raw('total_price / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END')), 2);
            $tenantExpenses[] = round((float) Expense::query()
                ->whereBetween('expense_date', [$start, $end])
                ->sum('amount'), 2);
            $tenantNetProfit[] = round(Metrics::netProfit($start, $end), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Platform revenue',
                    'data' => $platformRevenue,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Tenant sales revenue',
                    'data' => $tenantRevenue,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Tenant operating expenses',
                    'data' => $tenantExpenses,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Tenant net profit',
                    'data' => $tenantNetProfit,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
