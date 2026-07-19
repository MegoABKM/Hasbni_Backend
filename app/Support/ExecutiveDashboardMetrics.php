<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardMetrics
{
    public const PERIOD_DAYS = 30;

    public static function currentStart(): Carbon
    {
        return now()->subDays(self::PERIOD_DAYS);
    }

    public static function previousStart(): Carbon
    {
        return now()->subDays(self::PERIOD_DAYS * 2);
    }

    public static function previousEnd(): Carbon
    {
        return self::currentStart();
    }

    public static function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    public static function formatPercentChange(?float $change): string
    {
        if ($change === null) {
            return 'new activity';
        }

        if (abs($change) < 0.05) {
            return 'stable';
        }

        return ($change > 0 ? '+' : '') . number_format($change, 1) . '%';
    }

    public static function trendIcon(float|int $current, float|int $previous, bool $higherIsBetter = true): string
    {
        if (abs($current - $previous) < 0.0001) {
            return 'heroicon-m-minus';
        }

        $isUp = $current > $previous;

        return $isUp
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    public static function trendColor(float|int $current, float|int $previous, bool $higherIsBetter = true): string
    {
        if (abs($current - $previous) < 0.0001) {
            return 'gray';
        }

        $improved = $higherIsBetter ? $current > $previous : $current < $previous;

        return $improved ? 'success' : 'danger';
    }

    public static function comparison(float|int $current, float|int $previous, string $previousLabel = 'previous 30 days'): string
    {
        return self::formatPercentChange(self::percentChange($current, $previous)) . ' vs ' . $previousLabel
            . ' | updated ' . now()->format('H:i');
    }

    public static function money(float|int $amount): string
    {
        $amount = (float) $amount;

        if (abs($amount) >= 1000000) {
            return '$' . number_format($amount / 1000000, 2) . 'M';
        }

        if (abs($amount) >= 10000) {
            return '$' . number_format($amount / 1000, 1) . 'K';
        }

        return '$' . number_format($amount, 2);
    }

    public static function successfulPaymentsQuery(Carbon $start, Carbon $end): Builder
    {
        return Payment::query()
            ->where('status', 'successful')
            ->whereBetween('paid_at', [$start, $end]);
    }

    public static function salesQuery(Carbon $start, Carbon $end): Builder
    {
        return Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', '!=', 'voided');
    }

    public static function platformRevenue(Carbon $start, Carbon $end): float
    {
        return (float) self::successfulPaymentsQuery($start, $end)->sum('amount');
    }

    public static function posRevenue(Carbon $start, Carbon $end): float
    {
        return (float) self::salesQuery($start, $end)
            ->sum(DB::raw('total_price / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END'));
    }

    public static function grossProfit(Carbon $start, Carbon $end): float
    {
        return (float) self::salesQuery($start, $end)->sum('total_profit');
    }

    public static function operatingExpenses(Carbon $start, Carbon $end): float
    {
        return (float) Expense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');
    }

    public static function netProfit(Carbon $start, Carbon $end): float
    {
        return self::grossProfit($start, $end) - self::operatingExpenses($start, $end);
    }

    public static function churnCount(Carbon $start, Carbon $end): int
    {
        return Subscription::query()
            ->whereIn('status', ['expired', 'canceled'])
            ->whereBetween('ends_at', [$start, $end])
            ->count();
    }

    public static function newSubscriberCount(Carbon $start, Carbon $end): int
    {
        return Subscription::query()
            ->whereBetween('starts_at', [$start, $end])
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function activeSubscriberCount(): int
    {
        return Subscription::query()
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function lowStockCount(): int
    {
        return Product::query()
            ->whereColumn('quantity', '<=', 'alert_threshold')
            ->count();
    }

    public static function outOfStockCount(): int
    {
        return Product::query()
            ->where('quantity', '<=', 0)
            ->count();
    }

    public static function outstandingCustomerBalance(): float
    {
        return (float) Customer::query()
            ->where('balance', '>', 0)
            ->sum('balance');
    }

    /**
     * @return array<int, float>
     */
    public static function dailySeries(callable $resolver, int $days = 7): array
    {
        $series = [];

        for ($index = $days - 1; $index >= 0; $index--) {
            $date = now()->subDays($index)->toDateString();
            $series[] = round((float) $resolver($date), 2);
        }

        return $series;
    }
}
