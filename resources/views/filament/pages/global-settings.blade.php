<x-filament-panels::page>
    <x-filament::section
        :heading="__('Global Settings')"
        :description="__('Manage application availability, updates, support contact, and notification credentials.')"
        icon="heroicon-o-cog-8-tooth"
    >
        <form wire:submit="saveSettings" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check-circle">
                    {{ __('Save Settings') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
