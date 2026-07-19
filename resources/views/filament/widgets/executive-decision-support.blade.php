<x-filament-widgets::widget>
    <x-filament::section
        heading="Decision Support"
        description="What changed, why it matters, and the next management action. Last updated {{ $updatedAt }}."
        icon="heroicon-o-light-bulb"
    >
        <div class="grid gap-4 lg:grid-cols-5">
            @foreach ($alerts as $alert)
                <a
                    href="{{ $alert['url'] }}"
                    class="@class([
                        'rounded-lg border p-4 transition hover:shadow-sm',
                        'border-danger-200 bg-danger-50/70 dark:border-danger-800 dark:bg-danger-950/20' => $alert['severity'] === 'danger',
                        'border-warning-200 bg-warning-50/70 dark:border-warning-800 dark:bg-warning-950/20' => $alert['severity'] === 'warning',
                        'border-success-200 bg-success-50/70 dark:border-success-800 dark:bg-success-950/20' => $alert['severity'] === 'success',
                        'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' => $alert['severity'] === 'gray',
                    ])"
                    title="{{ $alert['why'] }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-600 dark:text-gray-300">
                                {{ $alert['label'] }}
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                                {{ $alert['value'] }}
                            </p>
                        </div>

                        <x-filament::badge :color="$alert['severity'] === 'gray' ? 'gray' : $alert['severity']">
                            {{ ucfirst($alert['severity']) }}
                        </x-filament::badge>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        {{ $alert['why'] }}
                    </p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $alert['decision'] }}
                    </p>
                </a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Best Selling Products</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ranked by tenant sales revenue in the last 30 days.</p>
                    </div>
                    <x-filament::badge color="info">Sales</x-filament::badge>
                </div>

                @if (count($topProducts))
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($topProducts as $product)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $product['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($product['quantity']) }} units sold</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">{{ $product['revenue'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No sales data exists for the current 30-day period.
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Top Customer Balances</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Highest tenant customer receivables currently recorded.</p>
                    </div>
                    <x-filament::badge color="warning">Cash</x-filament::badge>
                </div>

                @if (count($topCustomers))
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($topCustomers as $customer)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <p class="min-w-0 truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $customer['name'] }}</p>
                                <p class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">{{ $customer['balance'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No outstanding customer balances are currently recorded.
                    </div>
                @endif
            </section>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
