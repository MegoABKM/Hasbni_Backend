<?php

declare(strict_types=1);

namespace App\Saas\Filament\Pages\Kpi;

use App\Saas\Services\KpiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SaasAnalytics extends BaseKpiPage
{
    protected static ?string $slug = 'kpi/saas';

    protected static string $departmentKey = 'saas';

    protected static string $navigationLabelKey = 'kpi.nav.saas';

    protected static int $navigationOrder = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_metrics')
                ->label(__('Sync Metrics'))
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (): void {
                    app(KpiService::class)->recordDailyMetrics();

                    Notification::make()
                        ->title(__('Metrics have been recalculated'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
