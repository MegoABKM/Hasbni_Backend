<?php

namespace App\Saas\Filament\Widgets\Kpi;

use App\Models\User;
use App\Saas\Filament\Pages\Kpi\SaasAnalytics;
use App\Saas\Services\KpiService;
use Filament\Widgets\Widget;

class KpiHomeOverviewWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.kpi.kpi-home-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'finance_admin']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'metrics' => app(KpiService::class)->homeMetrics(),
            'overviewUrl' => SaasAnalytics::getUrl(),
        ];
    }
}
