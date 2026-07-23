<x-filament-panels::page>
    <div wire:poll.30s class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->checks() as $check)
            @php
                $color = match ($check['status']) {
                    'healthy' => 'text-success-600 bg-success-50 ring-success-600/20 dark:bg-success-500/10',
                    'warning' => 'text-warning-600 bg-warning-50 ring-warning-600/20 dark:bg-warning-500/10',
                    default => 'text-danger-600 bg-danger-50 ring-danger-600/20 dark:bg-danger-500/10',
                };
            @endphp

            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $check['label'] }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $check['detail'] }}</p>
                    </div>
                    <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $color }}">
                        {{ match ($check['status']) {
                            'healthy' => __('Healthy'),
                            'warning' => __('Warning'),
                            default => __('Failed'),
                        } }}
                    </span>
                </div>
                <div class="mt-6 text-3xl font-semibold text-gray-950 dark:text-white"><bdi>{{ $check['value'] }}</bdi></div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
