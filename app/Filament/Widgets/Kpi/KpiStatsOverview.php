<?php

namespace App\Filament\Widgets\Kpi;

use App\Services\KpiService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class KpiStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public string $department = 'overview';

    protected function getStats(): array
    {
        $metrics = app(KpiService::class)->dashboard($this->department, $this->filters ?? [])['metrics'] ?? [];
        $stats = [];

        foreach ($metrics as $metric) {
            $stats[] = $this->makeStat($metric);
        }

        return $stats;
    }

    protected function getColumns(): int|array|null
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
            '2xl' => 4,
        ];
    }

    /**
     * @param  array<string, mixed>  $metric
     */
    private function makeStat(array $metric): Stat
    {
        $changeText = $metric['change_text'] === 'kpi.change.new_activity'
            ? __('kpi.change.new_activity')
            : $metric['change_text'];

        return Stat::make(
            __($metric['label_key']),
            new HtmlString('<span dir="ltr">'.e($metric['value']).'</span>')
        )
            ->description(new HtmlString('<span dir="ltr">'.e($changeText).'</span> '.e(__('kpi.change.vs_comparison'))))
            ->descriptionIcon($metric['icon'])
            ->color($metric['color'])
            ->chart($metric['sparkline'])
            ->extraAttributes([
                'aria-label' => __($metric['tooltip_key']),
                'title' => __($metric['tooltip_key']),
                'class' => 'transition-all duration-300 hover:shadow-md',
            ]);
    }
}
