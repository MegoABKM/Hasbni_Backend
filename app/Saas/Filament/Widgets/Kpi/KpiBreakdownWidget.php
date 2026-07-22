<?php

namespace App\Saas\Filament\Widgets\Kpi;

use App\Saas\Services\KpiService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class KpiBreakdownWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.kpi.kpi-breakdown-widget';

    protected int|string|array $columnSpan = 'full';

    public string $department = 'overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $dashboard = app(KpiService::class)->dashboard($this->department, $this->filters ?? []);

        return [
            'sections' => $dashboard['breakdowns']['sections'] ?? [],
        ];
    }
}
