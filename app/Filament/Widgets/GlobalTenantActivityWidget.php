<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class GlobalTenantActivityWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 🔒 حساب إجمالي المبيعات لكافة المتاجر بشكل مجهول لحماية خصوصية البيانات
        $totalSalesRevenue = (float) Sale::where('payment_status', '!=', 'voided')
            ->sum(DB::raw('total_price / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END'));

        $totalGrossProfit = (float) Sale::where('payment_status', '!=', 'voided')->sum('total_profit');
        $totalExpenses = (float) Expense::sum('amount');
        $totalNetProfit = $totalGrossProfit - $totalExpenses;

        return [
            Stat::make(__('Global Shop Sales'), '$' . number_format($totalSalesRevenue, 2))
                ->description(__('Combined gross revenue of all stores anonymously'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make(__('Global Shop Net Profit'), '$' . number_format($totalNetProfit, 2))
                ->description(__('Combined net profit of all stores anonymously'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}