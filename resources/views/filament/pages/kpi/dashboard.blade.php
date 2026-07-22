<x-filament-panels::page>
    @php
        $filters = $this->getActiveFilters();
    @endphp

    <div class="space-y-6">
        <section class="relative z-30 rounded-2xl border border-gray-200/50 bg-white/80 p-6 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-900/80">
            <div class="mb-5 flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('Analytics Filters') }}</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Refine universal SaaS metrics by date, comparison period, and country.') }}
                    </p>
                </div>
            </div>

            <div class="w-full overflow-hidden">
                <div class="w-full overflow-x-auto pb-2">
                    <div class="w-full min-w-0 overflow-visible [&_.fi-fo-field-wrp]:min-w-[200px] [&_.fi-input-wrp]:w-full [&_.fi-sc-form]:w-full [&_.fi-select-input]:whitespace-nowrap">
                        {{ $this->filtersForm }}
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-200/50 pt-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                <x-filament::badge color="gray">
                    <bdi>{{ $rangeStartLabel }} - {{ $rangeEndLabel }}</bdi>
                </x-filament::badge>

                @if (filled($filters['country'] ?? null))
                    <x-filament::badge color="info">
                        {{ __('Country') }}: {{ $filters['country'] }}
                    </x-filament::badge>
                @endif

                <span>{{ __('Only SaaS platform billing and subscription data is included.') }}</span>
            </div>
        </section>

        @livewire(
            \App\Saas\Filament\Widgets\Kpi\KpiStatsOverview::class,
            ['department' => 'saas', 'pageFilters' => $filters],
            key("kpi-stats-saas-{$filterChecksum}")
        )

        <div class="grid grid-cols-1 gap-6">
            @livewire(
                \App\Saas\Filament\Widgets\Kpi\KpiChartWidget::class,
                ['department' => 'saas', 'chartIndex' => 0, 'pageFilters' => $filters],
                key("kpi-chart-mrr-{$filterChecksum}")
            )

            @livewire(
                \App\Saas\Filament\Widgets\Kpi\MrrMovementChartWidget::class,
                ['department' => 'saas', 'pageFilters' => $filters],
                key("kpi-chart-mrr-movements-{$filterChecksum}")
            )
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @livewire(
                \App\Saas\Filament\Widgets\Kpi\KpiChartWidget::class,
                ['department' => 'saas', 'chartIndex' => 1, 'pageFilters' => $filters],
                key("kpi-chart-movement-{$filterChecksum}")
            )
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach (($dashboard['breakdowns']['sections'] ?? []) as $section)
                <x-filament::section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-all duration-300 hover:shadow-md dark:bg-gray-900 dark:ring-white/10"
                >
                    <x-slot name="heading">{{ __($section['heading_key']) }}</x-slot>
                    <x-slot name="description">{{ __($section['description_key']) }}</x-slot>

                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($section['items'] as $item)
                            <div class="flex min-w-0 items-center justify-between gap-4 py-3">
                                <p class="min-w-0 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __($item['label_key']) }}
                                </p>
                                <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white" dir="ltr">
                                    {{ $item['value'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
