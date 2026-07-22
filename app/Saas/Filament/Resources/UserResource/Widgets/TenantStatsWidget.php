<?php

namespace App\Saas\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantStatsWidget extends BaseWidget
{
    public ?User $record = null;

    protected function getStats(): array
    {
        if ($this->record === null) {
            return [];
        }

        $subscription = $this->record->subscription()->with('plan')->first();
        $paymentStats = $this->record->payments()
            ->where('status', 'successful')
            ->selectRaw('COUNT(*) as payments_count, COALESCE(SUM(amount), 0) as revenue')
            ->first();
        $activeSessions = $this->record->tokens()->count();
        $auditEvents = $this->record->auditLogs()->count();

        return [
            Stat::make(__('Current Plan'), $subscription?->plan?->name ?? __('Free'))
                ->description($subscription === null ? __('No active subscription') : __(ucfirst($subscription->status)))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($subscription?->status === 'active' ? 'success' : 'gray'),
            Stat::make(__('Total Revenue'), '$'.number_format((float) ($paymentStats?->revenue ?? 0), 2))
                ->description(number_format((int) ($paymentStats?->payments_count ?? 0)).' '.__('Successful Payments'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make(__('Active Sessions'), number_format($activeSessions))
                ->description(__('Connected tenant devices'))
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($activeSessions > 0 ? 'info' : 'gray'),
            Stat::make(__('Audit Events'), number_format($auditEvents))
                ->description(__('Recorded account activity'))
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning'),
        ];
    }
}
