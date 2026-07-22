<x-filament-panels::page>
    <x-filament::section
        :heading="__('System Logs')"
        :description="__('Recent application logs with sensitive values removed before display.')"
        icon="heroicon-o-command-line"
    >
        <pre class="max-h-[65vh] overflow-auto rounded-lg border border-gray-200 bg-gray-950 p-4 text-left font-mono text-xs leading-6 text-green-200 shadow-inner dark:border-gray-800" dir="ltr">{{ $logContent }}</pre>
    </x-filament::section>
</x-filament-panels::page>
