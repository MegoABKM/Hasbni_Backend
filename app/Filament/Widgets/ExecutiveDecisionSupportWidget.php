<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\UserResource;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SaleItem;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Support\ExecutiveDashboardMetrics as Metrics;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ExecutiveDecisionSupportWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.executive-decision-support';

    protected function getViewData(): array
    {
        $failedPayments = Payment::query()
            ->where('status', 'failed')
            ->where('paid_at', '>=', now()->subDays(7))
            ->count();

        $openTickets = SupportTicket::query()->where('status', 'open')->count();
        $lowStock = Metrics::lowStockCount();
        $outOfStock = Metrics::outOfStockCount();
        $expiringSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();
        $outstandingBalance = Metrics::outstandingCustomerBalance();

        return [
            'alerts' => [
                [
                    'label' => 'Open support tickets',
                    'value' => number_format($openTickets),
                    'severity' => $openTickets > 10 ? 'danger' : ($openTickets > 0 ? 'warning' : 'success'),
                    'why' => 'Unanswered tickets affect retention and renewal confidence.',
                    'decision' => $openTickets > 0 ? 'Assign support capacity before chasing new acquisition.' : 'No immediate support action required.',
                    'url' => SupportTicketResource::getUrl('index'),
                ],
                [
                    'label' => 'Inventory warnings',
                    'value' => number_format($lowStock) . ' low / ' . number_format($outOfStock) . ' out',
                    'severity' => $outOfStock > 0 ? 'danger' : ($lowStock > 0 ? 'warning' : 'success'),
                    'why' => 'Stockouts block tenant sales and can hide demand from reports.',
                    'decision' => $lowStock > 0 ? 'Review tenants with low-stock SKUs and supplier coverage.' : 'Inventory risk is currently controlled.',
                    'url' => UserResource::getUrl('index'),
                ],
                [
                    'label' => 'Failed payments',
                    'value' => number_format($failedPayments),
                    'severity' => $failedPayments > 5 ? 'danger' : ($failedPayments > 0 ? 'warning' : 'success'),
                    'why' => 'Payment failures reduce platform revenue and may create involuntary churn.',
                    'decision' => $failedPayments > 0 ? 'Prioritize payment retry outreach and gateway error review.' : 'No payment recovery queue for the last 7 days.',
                    'url' => PaymentResource::getUrl('index'),
                ],
                [
                    'label' => 'Expiring subscriptions',
                    'value' => number_format($expiringSubscriptions),
                    'severity' => $expiringSubscriptions > 10 ? 'warning' : 'gray',
                    'why' => 'Upcoming expirations are the near-term renewal pipeline.',
                    'decision' => $expiringSubscriptions > 0 ? 'Start renewal contact for accounts expiring within 7 days.' : 'Renewal pressure is low this week.',
                    'url' => SubscriptionResource::getUrl('index'),
                ],
                [
                    'label' => 'Customer receivables',
                    'value' => Metrics::money($outstandingBalance),
                    'severity' => $outstandingBalance > 0 ? 'warning' : 'success',
                    'why' => 'Tenant customer balances show cash tied up outside immediate collections.',
                    'decision' => $outstandingBalance > 0 ? 'Review high-balance tenants before extending operational credit.' : 'No outstanding tenant customer balances recorded.',
                    'url' => UserResource::getUrl('index'),
                ],
            ],
            'topProducts' => $this->topProducts(),
            'topCustomers' => $this->topCustomers(),
            'updatedAt' => now()->format('M j, Y H:i'),
        ];
    }

    /**
     * @return array<int, array{name: string, revenue: string, quantity: int}>
     */
    private function topProducts(): array
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.payment_status', '!=', 'voided')
            ->where('sales.created_at', '>=', Metrics::currentStart())
            ->groupBy('sale_items.product_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get([
                'sale_items.product_name as name',
                DB::raw('SUM((sale_items.quantity_sold - sale_items.returned_quantity) * sale_items.price_at_sale / CASE WHEN sales.rate_to_usd_at_sale > 0 THEN sales.rate_to_usd_at_sale ELSE 1 END) as revenue'),
                DB::raw('SUM(sale_items.quantity_sold - sale_items.returned_quantity) as quantity'),
            ])
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'revenue' => Metrics::money((float) $row->revenue),
                'quantity' => (int) $row->quantity,
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, balance: string}>
     */
    private function topCustomers(): array
    {
        return Customer::query()
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->limit(5)
            ->get(['name', 'balance'])
            ->map(fn (Customer $customer): array => [
                'name' => $customer->name,
                'balance' => Metrics::money((float) $customer->balance),
            ])
            ->all();
    }
}
