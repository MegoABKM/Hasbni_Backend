<?php
namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TenantStatsWidget extends BaseWidget
{
    public ?User $record = null;

    protected function getStats(): array
    {
        if (!$this->record) return [];

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // 1. حساب المبيعات (Sales)
        $salesToday = $this->record->sales()->where('created_at', '>=', $today)->where('payment_status', '!=', 'voided')->get();
        $salesMonth = $this->record->sales()->where('created_at', '>=', $thisMonth)->where('payment_status', '!=', 'voided')->get();
        $salesYear = $this->record->sales()->where('created_at', '>=', $thisYear)->where('payment_status', '!=', 'voided')->get();

        // 2. حساب المقبوضات (Receipts / Cash Injections)
        $receiptsToday = $this->record->cashTransactions()->where('transaction_type', 'like', '%_in')->where('created_at', '>=', $today)->get();
        $receiptsMonth = $this->record->cashTransactions()->where('transaction_type', 'like', '%_in')->where('created_at', '>=', $thisMonth)->get();
        $receiptsYear = $this->record->cashTransactions()->where('transaction_type', 'like', '%_in')->where('created_at', '>=', $thisYear)->get();

        return [
            Stat::make('Today', 'Sales: ' . $salesToday->count() . ' | Receipts: ' . $receiptsToday->count())
                ->description('Rev: $' . number_format($salesToday->sum('total_price'), 2) . ' | Cash In: $' . number_format($receiptsToday->sum('amount'), 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('This Month', 'Sales: ' . $salesMonth->count() . ' | Receipts: ' . $receiptsMonth->count())
                ->description('Rev: $' . number_format($salesMonth->sum('total_price'), 2) . ' | Cash In: $' . number_format($receiptsMonth->sum('amount'), 2))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('This Year', 'Sales: ' . $salesYear->count() . ' | Receipts: ' . $receiptsYear->count())
                ->description('Rev: $' . number_format($salesYear->sum('total_price'), 2) . ' | Cash In: $' . number_format($receiptsYear->sum('amount'), 2))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }
}