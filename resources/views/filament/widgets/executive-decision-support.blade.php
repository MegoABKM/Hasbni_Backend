<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('Decision Support')"
        :description="__('Subscription health and the next management actions. Updated at :time.', ['time' => $updatedAt])"
        icon="heroicon-o-light-bulb"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($alerts as $alert)
                <a
                    href="{{ $alert['url'] }}"
                    @class([
                        'rounded-xl border p-4 transition-all duration-300 hover:shadow-md',
                        'border-danger-200 bg-danger-50/70 dark:border-danger-800 dark:bg-danger-950/20' => $alert['severity'] === 'danger',
                        'border-warning-200 bg-warning-50/70 dark:border-warning-800 dark:bg-warning-950/20' => $alert['severity'] === 'warning',
                        'border-success-200 bg-success-50/70 dark:border-success-800 dark:bg-success-950/20' => $alert['severity'] === 'success',
                        'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' => $alert['severity'] === 'gray',
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $alert['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $alert['value'] }}</p>
                        </div>
                        <x-filament::badge :color="$alert['severity'] === 'gray' ? 'gray' : $alert['severity']">
                            {{ __($alert['severity'] === 'gray' ? 'Information' : ucfirst($alert['severity'])) }}
                        </x-filament::badge>
                    </div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">{{ $alert['why'] }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $alert['decision'] }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Plan Performance') }}</h3>
                    <x-filament::badge color="primary">{{ __('MRR') }}</x-filament::badge>
                </div>
                <div class="divide-y divide-gray-200 border-y border-gray-200 dark:divide-white/10 dark:border-white/10">
                    @forelse ($plans as $plan)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $plan['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($plan['active_subscriptions']) }} {{ __('Active Subscriptions') }}
                                </p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white" dir="ltr">
                                ${{ number_format($plan['mrr'], 2) }}
                            </p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No active plan data is available.') }}</p>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('Recent Tenants') }}</h3>
                    <x-filament::badge color="info">{{ __('New') }}</x-filament::badge>
                </div>
                <div class="divide-y divide-gray-200 border-y border-gray-200 dark:divide-white/10 dark:border-white/10">
                    @forelse ($recentTenants as $tenant)
                        <a href="{{ $tenant['url'] }}" class="flex items-center justify-between gap-4 py-3 hover:text-primary-600 dark:hover:text-primary-400">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $tenant['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tenant['country'] }}</p>
                            </div>
                            <p class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $tenant['registered_at'] }}</p>
                        </a>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No tenants have registered yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
