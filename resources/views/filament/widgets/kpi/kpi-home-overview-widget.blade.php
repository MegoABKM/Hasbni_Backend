<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('kpi.home.heading')"
        :description="__('kpi.home.description')"
        icon="heroicon-o-presentation-chart-line"
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <div
                    class="min-w-0 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                    title="{{ __($metric['tooltip_key']) }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-600 dark:text-gray-300">
                                {{ __($metric['label_key']) }}
                            </p>
                            <p class="mt-2 break-words text-2xl font-semibold text-gray-950 dark:text-white" dir="ltr">
                                {{ $metric['value'] }}
                            </p>
                        </div>

                        <x-filament::icon
                            :icon="$metric['icon']"
                            @class([
                                'h-5 w-5',
                                'text-success-600 dark:text-success-400' => $metric['color'] === 'success',
                                'text-danger-600 dark:text-danger-400' => $metric['color'] === 'danger',
                                'text-gray-500 dark:text-gray-400' => $metric['color'] === 'gray',
                            ])
                        />
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        <span dir="ltr">
                            {{ $metric['change_text'] === 'kpi.change.new_activity' ? __('kpi.change.new_activity') : $metric['change_text'] }}
                        </span>
                        {{ __('kpi.change.vs_comparison') }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <x-filament::button
                tag="a"
                :href="$overviewUrl"
                icon="heroicon-o-arrow-top-right-on-square"
            >
                {{ __('kpi.action.view_all') }}
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
