<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('Country Analytics')"
        :description="__('Current tenant distribution and recurring revenue by country.')"
        icon="heroicon-o-globe-alt"
    >
        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('Country') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('Registered Users') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('Active Subscriptions') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('Monthly Recurring Revenue') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                    @forelse ($countries as $country)
                        <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                {{ $country['country'] === \App\Services\KpiService::UNKNOWN_COUNTRY ? __('Unspecified') : $country['country'] }}
                            </td>
                            <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-200">
                                <bdi>{{ number_format($country['registered_users']) }}</bdi>
                            </td>
                            <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-200">
                                <bdi>{{ number_format($country['active_subscriptions']) }}</bdi>
                            </td>
                            <td class="px-4 py-3 text-end font-semibold text-primary-700 dark:text-primary-300">
                                <bdi>${{ number_format($country['mrr'], 2) }}</bdi>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No country analytics are available yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
