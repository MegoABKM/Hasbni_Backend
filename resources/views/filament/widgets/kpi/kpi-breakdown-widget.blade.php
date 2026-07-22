<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($sections as $section)
            <x-filament::section>
                <x-slot name="heading">
                    {{ __($section['heading_key']) }}
                </x-slot>

                <x-slot name="description">
                    {{ __($section['description_key']) }}
                </x-slot>

                <div class="space-y-3">
                    @foreach ($section['items'] as $item)
                        <div class="flex min-w-0 items-start justify-between gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
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
</x-filament-widgets::widget>
