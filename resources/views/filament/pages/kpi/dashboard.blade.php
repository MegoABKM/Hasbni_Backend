<x-filament-panels::page>
    @php
        $department = $this->getDepartmentKey();
        $filters = $this->getActiveFilters();
    @endphp

    <div class="space-y-6">
        <!-- 🚀 Filter card with z-30 for popovers floating above widgets -->
        <div class="relative z-30 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-2xl shadow-sm p-6">
            <div class="mb-5 flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ __('kpi.filter.heading') }}
                        </h2>
                    </div>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('kpi.filter.description') }}
                    </p>
                </div>
            </div>

            <div class="w-full pt-2">
                {{ $this->filtersForm }}
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200/50 dark:border-gray-800 pt-4">
                <x-filament::badge color="gray">
                    <bdi>{{ $rangeStartLabel }} - {{ $rangeEndLabel }}</bdi>
                </x-filament::badge>
                @if (filled($filters['country'] ?? null))
                    <x-filament::badge color="info">
                        {{ __('kpi.filter.country') }}: {{ $filters['country'] }}
                    </x-filament::badge>
                @endif
                <span>{{ __('kpi.filter.platform_aggregates_only') }}</span>
            </div>
        </div>

        @livewire(
            \App\Filament\Widgets\Kpi\KpiStatsOverview::class,
            ['department' => $department, 'pageFilters' => $filters],
            key("kpi-stats-{$department}-{$filterChecksum}")
        )

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @livewire(
                \App\Filament\Widgets\Kpi\KpiChartWidget::class,
                ['department' => $department, 'chartIndex' => 0, 'pageFilters' => $filters],
                key("kpi-chart-a-{$department}-{$filterChecksum}")
            )

            @livewire(
                \App\Filament\Widgets\Kpi\KpiChartWidget::class,
                ['department' => $department, 'chartIndex' => 1, 'pageFilters' => $filters],
                key("kpi-chart-b-{$department}-{$filterChecksum}")
            )
        </div>

        <!-- 📊 Performance Breakdown Data Table -->
        <div class="relative z-10 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-2xl shadow-sm p-6">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ __('جدول تحليل المؤشرات (Performance Breakdown Table)') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('تفاصيل البيانات المجمعة حسب الدولة والتاريخ') }}
                    </p>
                </div>
                <x-filament::badge color="primary">
                    <bdi>{{ $rangeStartLabel }} → {{ $rangeEndLabel }}</bdi>
                </x-filament::badge>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200/60 dark:border-gray-800">
                <table class="w-full text-start text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50/80 dark:bg-gray-800/50 text-gray-950 dark:text-white font-semibold">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="p-3 text-start">{{ __('الدولة (Country)') }}</th>
                            <th class="p-3 text-start">{{ __('الفترة (Period)') }}</th>
                            <th class="p-3 text-start">{{ __('إجمالي المبيعات (Sales)') }}</th>
                            <th class="p-3 text-start">{{ __('صافي الربح (Net Profit)') }}</th>
                            <th class="p-3 text-start">{{ __('الاشتراكات (Subscribers)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @php
                            $countryFilter = $filters['country'] ?? null;
                            $metrics = $dashboard['metrics'] ?? [];
                            $salesVal = $metrics[0]['value'] ?? '$0.00';
                            $profitVal = $metrics[1]['value'] ?? '$0.00';
                            $subsVal = $metrics[2]['value'] ?? '0';
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            <td class="p-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ $countryFilter ?: __('جميع الدول (All Countries)') }}
                            </td>
                            <td class="p-3">
                                <bdi>{{ $rangeStartLabel }} - {{ $rangeEndLabel }}</bdi>
                            </td>
                            <td class="p-3 font-semibold text-emerald-600 dark:text-emerald-400" dir="ltr">
                                {{ $salesVal }}
                            </td>
                            <td class="p-3 font-semibold text-teal-600 dark:text-teal-400" dir="ltr">
                                {{ $profitVal }}
                            </td>
                            <td class="p-3 font-semibold text-sky-600 dark:text-sky-400" dir="ltr">
                                {{ $subsVal }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @forelse (($dashboard['breakdowns']['sections'] ?? []) as $section)
                <x-filament::section class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-2xl shadow-sm p-6 transition-all duration-300 hover:shadow-md">
                    <x-slot name="heading">
                        {{ __($section['heading_key']) }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __($section['description_key']) }}
                    </x-slot>

                    <div class="space-y-3">
                        @foreach ($section['items'] as $item)
                            <div class="flex min-w-0 items-start justify-between gap-4 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-2xl shadow-sm p-6 transition-all duration-300 hover:shadow-md">
                                <p class="min-w-0 text-sm font-medium leading-6 text-gray-800 dark:text-gray-100">
                                    {{ __($item['label_key'], $item['label_params'] ?? []) }}
                                </p>

                                @if (filled($item['value']))
                                    <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white" dir="ltr">
                                        {{ $item['value'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <x-filament::section>
                    <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-10 w-10 text-gray-400" />
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ __('kpi.empty.no_data') }}
                        </h3>
                        <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                            {{ __('kpi.filter.platform_aggregates_only') }}
                        </p>
                    </div>
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>