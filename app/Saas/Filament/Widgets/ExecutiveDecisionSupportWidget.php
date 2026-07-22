<?php

namespace App\Saas\Filament\Widgets;

use App\Models\User;
use App\Saas\Filament\Resources\PaymentResource;
use App\Saas\Filament\Resources\SubscriptionResource;
use App\Saas\Filament\Resources\SupportTicketResource;
use App\Saas\Filament\Resources\UserResource;
use App\Saas\Models\Payment;
use App\Saas\Models\Subscription;
use App\Saas\Models\SupportTicket;
use App\Saas\Services\ExecutiveDashboardMetrics;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExecutiveDecisionSupportWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.executive-decision-support';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("saas:decision-support:v2:{$locale}", 300, function (): array {
            $failedPayments = Payment::query()
                ->failed()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
            $openTickets = SupportTicket::query()->where('status', 'open')->count();
            $expiringSubscriptions = Subscription::query()
                ->active()
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count();
            $metrics = app(ExecutiveDashboardMetrics::class)->snapshot();
            $churnRate = (float) $metrics['churn_rate'];

            return [
                'alerts' => [
                    [
                        'label' => __('Open Support Tickets'),
                        'value' => number_format($openTickets),
                        'severity' => $openTickets > 10 ? 'danger' : ($openTickets > 0 ? 'warning' : 'success'),
                        'why' => __('Unanswered tickets can reduce retention and renewal confidence.'),
                        'decision' => $openTickets > 0
                            ? __('Assign support capacity to the open queue.')
                            : __('No immediate support action is required.'),
                        'url' => SupportTicketResource::getUrl('index'),
                    ],
                    [
                        'label' => __('Failed Payments'),
                        'value' => number_format($failedPayments),
                        'severity' => $failedPayments > 5 ? 'danger' : ($failedPayments > 0 ? 'warning' : 'success'),
                        'why' => __('Payment failures reduce revenue and can cause involuntary churn.'),
                        'decision' => $failedPayments > 0
                            ? __('Review gateway errors and start payment recovery.')
                            : __('No payment recovery queue exists for the last 7 days.'),
                        'url' => PaymentResource::getUrl('index'),
                    ],
                    [
                        'label' => __('Expiring Subscriptions'),
                        'value' => number_format($expiringSubscriptions),
                        'severity' => $expiringSubscriptions > 10 ? 'warning' : 'gray',
                        'why' => __('Upcoming expirations form the near-term renewal pipeline.'),
                        'decision' => $expiringSubscriptions > 0
                            ? __('Contact tenants whose subscriptions expire within 7 days.')
                            : __('Renewal pressure is low this week.'),
                        'url' => SubscriptionResource::getUrl('index'),
                    ],
                    [
                        'label' => __('Churn Rate'),
                        'value' => number_format($churnRate, 2).'%',
                        'severity' => $churnRate > 10 ? 'danger' : ($churnRate > 5 ? 'warning' : 'success'),
                        'why' => __('Churn measures subscription losses against the opening active base.'),
                        'decision' => $churnRate > 5
                            ? __('Review cancellation reasons and retention outreach.')
                            : __('Subscription retention is within the target range.'),
                        'url' => SubscriptionResource::getUrl('index'),
                    ],
                ],
                'plans' => $this->planPerformance(),
                'recentTenants' => User::query()
                    ->tenants()
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'name', 'country', 'created_at'])
                    ->map(fn (User $user): array => [
                        'name' => $user->name,
                        'country' => $user->country ?: __('Unspecified'),
                        'registered_at' => $user->created_at->diffForHumans(),
                        'url' => UserResource::getUrl('edit', ['record' => $user]),
                    ])
                    ->all(),
                'updatedAt' => now()->translatedFormat('M j, Y H:i'),
            ];
        });
    }

    /**
     * @return array<int, array{name: string, active_subscriptions: int, mrr: float}>
     */
    private function planPerformance(): array
    {
        return DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', 'active')
            ->where(function ($query): void {
                $query->whereNull('subscriptions.starts_at')
                    ->orWhere('subscriptions.starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>=', now());
            })
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('active_subscriptions')
            ->limit(5)
            ->get([
                'plans.name',
                DB::raw('COUNT(subscriptions.id) as active_subscriptions'),
                DB::raw("SUM(CASE
                    WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12
                    WHEN subscriptions.billing_cycle = 'lifetime' THEN 0
                    ELSE plans.monthly_price
                END) as mrr"),
            ])
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'active_subscriptions' => (int) $row->active_subscriptions,
                'mrr' => round((float) $row->mrr, 2),
            ])
            ->all();
    }
}
