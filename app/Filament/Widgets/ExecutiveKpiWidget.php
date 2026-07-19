<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\UserResource;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Support\ExecutiveDashboardMetrics as Metrics;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ExecutiveKpiWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Executive KPI Snapshot';

    protected ?string $description = 'Rolling 30-day view with previous-period comparison. Revenue and profit are intentionally separated.';

    protected function getStats(): array
    {
        $currentStart = Metrics::currentStart();
        $previousStart = Metrics::previousStart();
        $previousEnd = Metrics::previousEnd();
        $now = now();

        $platformRevenue = Metrics::platformRevenue($currentStart, $now);
        $previousPlatformRevenue = Metrics::platformRevenue($previousStart, $previousEnd);

        $posRevenue = Metrics::posRevenue($currentStart, $now);
        $previousPosRevenue = Metrics::posRevenue($previousStart, $previousEnd);

        $netProfit = Metrics::netProfit($currentStart, $now);
        $previousNetProfit = Metrics::netProfit($previousStart, $previousEnd);

        $newSubscribers = Metrics::newSubscriberCount($currentStart, $now);
        $previousNewSubscribers = Metrics::newSubscriberCount($previousStart, $previousEnd);

        $churnedSubscriptions = Metrics::churnCount($currentStart, $now);
        $previousChurnedSubscriptions = Metrics::churnCount($previousStart, $previousEnd);

        $lowStock = Metrics::lowStockCount();
        $openTickets = SupportTicket::query()->where('status', 'open')->count();
        $attentionScore = $lowStock + $openTickets;

        return [
            $this->stat(
                'Platform Revenue',
                Metrics::money($platformRevenue),
                $platformRevenue,
                $previousPlatformRevenue,
                true,
                Metrics::dailySeries(fn (string $date): float => (float) Payment::query()
                    ->where('status', 'successful')
                    ->whereDate('paid_at', $date)
                    ->sum('amount')),
                PaymentResource::getUrl('index'),
                'Successful subscription payments collected by the SaaS platform. Use this to judge billing growth, not tenant sales performance.'
            ),
            $this->stat(
                'Tenant Sales Revenue',
                Metrics::money($posRevenue),
                $posRevenue,
                $previousPosRevenue,
                true,
                Metrics::dailySeries(fn (string $date): float => (float) Sale::query()
                    ->whereDate('created_at', $date)
                    ->where('payment_status', '!=', 'voided')
                    ->sum(DB::raw('total_price / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END'))),
                UserResource::getUrl('index'),
                'Gross POS revenue generated inside tenant shops. This is business activity across tenants, separate from platform revenue.'
            ),
            $this->stat(
                'Tenant Net Profit',
                Metrics::money($netProfit),
                $netProfit,
                $previousNetProfit,
                true,
                Metrics::dailySeries(function (string $date): float {
                    $dailyGrossProfit = (float) Sale::query()
                        ->whereDate('created_at', $date)
                        ->where('payment_status', '!=', 'voided')
                        ->sum('total_profit');

                    $dailyExpenses = (float) Expense::query()
                        ->whereDate('expense_date', $date)
                        ->sum('amount');

                    return $dailyGrossProfit - $dailyExpenses;
                }),
                UserResource::getUrl('index'),
                'Tenant gross profit minus tenant operating expenses. This is not the SaaS platform profit margin.'
            ),
            $this->stat(
                'New Subscribers',
                number_format($newSubscribers),
                $newSubscribers,
                $previousNewSubscribers,
                true,
                Metrics::dailySeries(fn (string $date): float => (float) Subscription::query()
                    ->whereDate('starts_at', $date)
                    ->distinct('user_id')
                    ->count('user_id')),
                SubscriptionResource::getUrl('index'),
                'New paid or tracked subscriptions started in the current period. This helps management judge acquisition momentum.'
            ),
            $this->stat(
                'Subscription Churn',
                number_format($churnedSubscriptions),
                $churnedSubscriptions,
                $previousChurnedSubscriptions,
                false,
                Metrics::dailySeries(fn (string $date): float => (float) Subscription::query()
                    ->whereIn('status', ['expired', 'canceled'])
                    ->whereDate('ends_at', $date)
                    ->count()),
                SubscriptionResource::getUrl('index'),
                'Subscriptions that expired or were canceled in the period. Lower is better and usually requires retention action.'
            ),
            Stat::make('Immediate Attention', number_format($attentionScore))
                ->description($openTickets . ' open tickets | ' . $lowStock . ' low-stock products | updated ' . now()->format('H:i'))
                ->descriptionIcon($attentionScore > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($attentionScore > 20 ? 'danger' : ($attentionScore > 0 ? 'warning' : 'success'))
                ->chart([$openTickets, $lowStock])
                ->url(SupportTicketResource::getUrl('index'))
                ->extraAttributes([
                    'title' => 'Operational risk count from real support tickets and inventory warnings. Drill into tickets first, then tenant inventory.',
                ]),
        ];
    }

    /**
     * @param array<int, float> $chart
     */
    private function stat(
        string $title,
        string $value,
        float|int $current,
        float|int $previous,
        bool $higherIsBetter,
        array $chart,
        string $url,
        string $tooltip
    ): Stat {
        return Stat::make($title, $value)
            ->description(Metrics::comparison($current, $previous))
            ->descriptionIcon(Metrics::trendIcon($current, $previous, $higherIsBetter))
            ->color(Metrics::trendColor($current, $previous, $higherIsBetter))
            ->chart($chart)
            ->url($url)
            ->extraAttributes(['title' => $tooltip]);
    }
}
