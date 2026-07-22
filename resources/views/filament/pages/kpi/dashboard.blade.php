<x-filament-panels::page>
    @php
        $filters = $this->getActiveFilters();
    @endphp

    <div
        x-data="{ activeTab: 'financials' }"
        class="space-y-6"
    >
        <section class="relative z-30 rounded-2xl border border-gray-200/50 bg-white/80 p-6 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-gray-900/80">
            <div class="mb-5 flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('Analytics Filters') }}</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Refine universal SaaS metrics by date and country.') }}
                    </p>
                </div>
            </div>

            <div class="w-full">
                {{ $this->filtersForm }}
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

        <nav class="overflow-x-auto border-b border-gray-200 dark:border-white/10" aria-label="{{ __('Analytics views') }}">
            <div class="-mb-px flex min-w-max gap-6" role="tablist">
                <button
                    type="button"
                    id="financials-tab"
                    role="tab"
                    aria-controls="financials-panel"
                    :aria-selected="activeTab === 'financials'"
                    @click="activeTab = 'financials'"
                    :class="activeTab === 'financials'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/20 dark:hover:text-gray-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition-colors duration-200"
                >
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5" />
                    {{ __('Financials & MRR') }}
                </button>

                <button
                    type="button"
                    id="subscriptions-tab"
                    role="tab"
                    aria-controls="subscriptions-panel"
                    :aria-selected="activeTab === 'subscriptions'"
                    @click="activeTab = 'subscriptions'"
                    :class="activeTab === 'subscriptions'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/20 dark:hover:text-gray-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition-colors duration-200"
                >
                    <x-filament::icon icon="heroicon-o-arrow-trending-down" class="h-5 w-5" />
                    {{ __('Subscriptions & Churn') }}
                </button>

                <button
                    type="button"
                    id="executive-tab"
                    role="tab"
                    aria-controls="executive-panel"
                    :aria-selected="activeTab === 'executive'"
                    @click="activeTab = 'executive'"
                    :class="activeTab === 'executive'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/20 dark:hover:text-gray-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition-colors duration-200"
                >
                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5" />
                    {{ __('Executive Insights & Plans') }}
                </button>

                <button
                    type="button"
                    id="geography-tab"
                    role="tab"
                    aria-controls="geography-panel"
                    :aria-selected="activeTab === 'geography'"
                    @click="activeTab = 'geography'"
                    :class="activeTab === 'geography'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-white/20 dark:hover:text-gray-200'"
                    class="inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition-colors duration-200"
                >
                    <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
                    {{ __('Demographics & Geography') }}
                </button>
            </div>
        </nav>

        <section
            id="financials-panel"
            role="tabpanel"
            x-cloak
            x-show="activeTab === 'financials'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            aria-labelledby="financials-tab"
            class="space-y-6"
        >
            @livewire(
                \App\Saas\Filament\Widgets\Kpi\KpiStatsOverview::class,
                ['department' => 'saas', 'pageFilters' => $filters],
                key("kpi-stats-saas-{$filterChecksum}")
            )

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
        </section>

        <section
            id="subscriptions-panel"
            role="tabpanel"
            x-cloak
            x-show="activeTab === 'subscriptions'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            aria-labelledby="subscriptions-tab"
            class="space-y-6"
        >
            @livewire(
                \App\Saas\Filament\Widgets\Kpi\KpiChartWidget::class,
                ['department' => 'saas', 'chartIndex' => 1, 'pageFilters' => $filters],
                key("kpi-chart-movement-{$filterChecksum}")
            )

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
        </section>

        <section
            id="executive-panel"
            role="tabpanel"
            x-cloak
            x-show="activeTab === 'executive'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            aria-labelledby="executive-tab"
            class="space-y-6"
        >
            @livewire(
                \App\Saas\Filament\Widgets\ExecutiveDecisionSupportWidget::class,
                [],
                key("executive-decision-support-{$filterChecksum}")
            )
        </section>

        <section
            id="geography-panel"
            role="tabpanel"
            x-cloak
            x-show="activeTab === 'geography'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            aria-labelledby="geography-tab"
            class="space-y-6"
        >
            @livewire(
                \App\Saas\Filament\Widgets\SaaSCountryAnalyticsWidget::class,
                [],
                key("saas-country-analytics-{$filterChecksum}")
            )
        </section>
    </div>
</x-filament-panels::page>
