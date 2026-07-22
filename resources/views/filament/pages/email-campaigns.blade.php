<x-filament-panels::page>
    <x-filament::section
        :heading="__('Email Campaigns')"
        :description="__('Queue a campaign email for the selected tenant segment.')"
        icon="heroicon-o-envelope"
    >
        <form wire:submit="sendCampaign" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane" wire:confirm="{{ __('Queue this email campaign for the selected audience?') }}">
                    {{ __('Send Campaign') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
