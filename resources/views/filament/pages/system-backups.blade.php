<x-filament-panels::page>
    <x-filament::section
        :heading="__('System Backups')"
        :description="__('Generate and manage database backups stored in private application storage.')"
        icon="heroicon-o-circle-stack"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                {{ __('Backups can contain sensitive tenant and billing data. Store downloaded files securely.') }}
            </div>

            <x-filament::button wire:click="generateBackup" color="success" icon="heroicon-o-plus-circle">
                {{ __('Create Backup') }}
            </x-filament::button>
        </div>

        @if (empty($backupFiles))
            <div class="mt-6 rounded-lg border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                <x-filament::icon icon="heroicon-o-circle-stack" class="mx-auto h-10 w-10 text-gray-400" />
                <h3 class="mt-3 text-base font-semibold text-gray-950 dark:text-white">{{ __('No Backups Available') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create a private database backup when needed.') }}</p>
            </div>
        @else
            <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="hidden grid-cols-[1fr_2fr_1fr_1fr] gap-4 border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 md:grid">
                    <div>{{ __('Created') }}</div>
                    <div>{{ __('File') }}</div>
                    <div>{{ __('Size') }}</div>
                    <div class="text-center">{{ __('Actions') }}</div>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($backupFiles as $file)
                        <div class="grid gap-3 px-4 py-4 md:grid-cols-[1fr_2fr_1fr_1fr] md:items-center">
                            <div>
                                <div class="text-xs font-medium uppercase text-gray-500 md:hidden">{{ __('Created') }}</div>
                                <div class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $file['created_at'] }}</div>
                            </div>

                            <div class="min-w-0">
                                <div class="text-xs font-medium uppercase text-gray-500 md:hidden">{{ __('File') }}</div>
                                <div class="truncate font-mono text-sm font-semibold text-gray-950 dark:text-white" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-medium uppercase text-gray-500 md:hidden">{{ __('Size') }}</div>
                                <div class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $file['size'] }}</div>
                            </div>

                            <div class="flex flex-wrap gap-2 md:justify-center">
                                <x-filament::button wire:click="downloadBackup('{{ $file['name'] }}')" color="info" size="sm" icon="heroicon-m-arrow-down-tray">
                                    {{ __('Download') }}
                                </x-filament::button>

                                <x-filament::button
                                    wire:click="deleteBackup('{{ $file['name'] }}')"
                                    wire:confirm="{{ __('Delete this backup file? This cannot be undone.') }}"
                                    color="danger"
                                    size="sm"
                                    icon="heroicon-m-trash"
                                >
                                    {{ __('Delete') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
