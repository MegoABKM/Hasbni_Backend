<x-filament-widgets::widget>
    <x-filament::section
        heading="KPI Backend Requirements"
        description="These placeholders are intentionally not rendered as numbers because the current schema cannot calculate them reliably."
        icon="heroicon-o-circle-stack"
        collapsible
        collapsed
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($requirements as $requirement)
                <article class="group flex min-h-full flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-lg dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                                {{ $requirement['domain'] }}
                            </h3>

                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ __('Backend coverage gap') }}
                            </p>
                        </div>

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-600/10 transition-colors duration-300 group-hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20 dark:group-hover:bg-amber-500/20">
                            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-5 w-5" />
                        </span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-amber-500" />
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('Missing') }}
                                </p>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                {{ $requirement['missing'] }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-circle-stack" class="h-4 w-4 text-primary-500" />
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('Required support') }}
                                </p>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                {{ $requirement['backend'] }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
