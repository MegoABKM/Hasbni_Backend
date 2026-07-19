<x-filament-widgets::widget>
    <x-filament::section
        heading="KPI Backend Requirements"
        description="These placeholders are intentionally not rendered as numbers because the current schema cannot calculate them reliably."
        icon="heroicon-o-circle-stack"
        collapsible
        collapsed
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($requirements as $requirement)
                <section class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $requirement['domain'] }}</h3>
                        <x-filament::badge color="gray">Backend required</x-filament::badge>
                    </div>

                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-medium text-gray-900 dark:text-gray-100">Missing:</span>
                        {{ $requirement['missing'] }}
                    </p>

                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-medium text-gray-900 dark:text-gray-100">Required support:</span>
                        {{ $requirement['backend'] }}
                    </p>
                </section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
