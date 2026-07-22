<?php

namespace App\Filament\Widgets\Kpi;

use App\Filament\Pages\Kpi\Overview;
use App\Services\KpiService;
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
            'overviewUrl' => Overview::getUrl(),
        ];
    }
}
