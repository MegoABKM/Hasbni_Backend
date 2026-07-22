<?php

namespace App\Saas\Filament\Widgets\Kpi;

use App\Saas\Filament\Pages\Kpi\SaasAnalytics;
use App\Saas\Services\KpiService;
use Filament\Widgets\Widget;

class KpiHomeOverviewWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.kpi.kpi-home-overview-widget';

    protected int|string|array $columnSpan = 'full';

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
