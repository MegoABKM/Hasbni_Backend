<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{ activeTab: 'guide' }"
            class="space-y-6"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-start">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 ring-1 ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-400/20">
                            <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5" />
                        </span>

                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ __('Business Intelligence & KPI Guide') }}
                        </h2>
                    </div>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                        {{ __('A comprehensive dictionary of Key Performance Indicators used by successful businesses.') }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2 overflow-x-auto border-b border-gray-200 pb-3 dark:border-white/10">
                <button
                    type="button"
                    x-on:click="activeTab = 'guide'"
                    x-bind:class="activeTab === 'guide'
                        ? 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-400/20'
                        : 'text-gray-600 ring-transparent hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white'"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium ring-1 transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:shadow-sm"
                >
                    <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5" />
                    <span>{{ __('KPI Philosophy (Guide)') }}</span>
                </button>

                @foreach ($departments as $key => $dept)
                    @php
                        $accentClasses = match ($key) {
                            'sales' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20',
                            'marketing' => 'bg-pink-50 text-pink-700 ring-pink-600/20 dark:bg-pink-500/10 dark:text-pink-300 dark:ring-pink-400/20',
                            'hr' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-400/20',
                            'operations' => 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-400/20',
                            'finance' => 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-400/20',
                            'supply_chain' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-400/20',
                            'customer_service' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/20',
                            default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10',
                        };
                    @endphp

                    <button
                        type="button"
                        x-on:click="activeTab = @js($key)"
                        x-bind:class="activeTab === @js($key)
                            ? @js($accentClasses)
                            : 'text-gray-600 ring-transparent hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white'"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium ring-1 transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:shadow-sm"
                    >
                        <x-filament::icon :icon="$dept['icon']" class="h-5 w-5" />
                        <span>{{ $dept['title'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="relative">
                <div
                    x-cloak
                    x-show="activeTab === 'guide'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                >
                    <div class="rounded-xl bg-primary-50/70 p-5 ring-1 ring-primary-600/10 transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-lg dark:bg-primary-500/10 dark:ring-primary-400/20 sm:p-6">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('Why do we measure KPIs?') }}
                        </h3>

                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-300">
                            {{ __('If you open the dashboard of any company, the first KPI you look at varies by department, because each department has a different way of measuring success.') }}
                        </p>

                        <blockquote class="mt-4 border-s-4 border-primary-500 ps-4 text-sm font-semibold leading-7 text-gray-900 dark:text-gray-100">
                            {{ __('The beautiful thing is that tools change... but KPIs are the universal language that every business speaks.') }}
                        </blockquote>

                        <p class="mt-4 text-sm leading-7 text-gray-600 dark:text-gray-300">
                            {{ __('If you are a Data Analyst or plan to enter the field, you must not just memorize the KPI... You must understand WHY it is measured, and HOW it helps management make decisions.') }}
                        </p>
                    </div>
                </div>

                @foreach ($departments as $key => $dept)
                    @php
                        $cardAccentClasses = match ($key) {
                            'sales' => 'border-t-emerald-500',
                            'marketing' => 'border-t-pink-500',
                            'hr' => 'border-t-blue-500',
                            'operations' => 'border-t-orange-500',
                            'finance' => 'border-t-teal-500',
                            'supply_chain' => 'border-t-indigo-500',
                            'customer_service' => 'border-t-red-500',
                            default => 'border-t-gray-400',
                        };

                        $labelClasses = match ($key) {
                            'sales' => 'text-emerald-700 dark:text-emerald-300',
                            'marketing' => 'text-pink-700 dark:text-pink-300',
                            'hr' => 'text-blue-700 dark:text-blue-300',
                            'operations' => 'text-orange-700 dark:text-orange-300',
                            'finance' => 'text-teal-700 dark:text-teal-300',
                            'supply_chain' => 'text-indigo-700 dark:text-indigo-300',
                            'customer_service' => 'text-red-700 dark:text-red-300',
                            default => 'text-gray-700 dark:text-gray-300',
                        };
                    @endphp

                    <div
                        x-cloak
                        x-show="activeTab === @js($key)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($dept['kpis'] as $kpi)
                                <article class="group flex min-h-full flex-col rounded-xl border-t-4 {{ $cardAccentClasses }} bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-lg dark:bg-gray-900 dark:ring-white/10">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                                                {{ $kpi['en'] }}
                                            </h3>

                                            <p class="mt-1 text-sm font-semibold {{ $labelClasses }}">
                                                {{ $kpi['ar'] }}
                                            </p>
                                        </div>

                                        <x-filament::icon :icon="$dept['icon']" class="h-5 w-5 shrink-0 text-gray-400 transition-colors duration-300 group-hover:text-gray-700 dark:group-hover:text-gray-200" />
                                    </div>

                                    <p class="mt-4 border-t border-dashed border-gray-200 pt-4 text-sm leading-6 text-gray-500 dark:border-white/10 dark:text-gray-400">
                                        {{ $kpi['desc'] }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
