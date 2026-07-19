<x-filament-panels::page>
    <x-filament::section
        heading="Email Campaigns"
        description="Queue a campaign email for the selected user segment."
        icon="heroicon-o-envelope"
    >
        <form wire:submit="sendCampaign" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane" wire:confirm="Queue this email campaign for the selected audience?">
                    Send Campaign
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
