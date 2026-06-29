<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="sendCampaign">
            {{ $this->form }}
            
            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane">
                    {{ __('Send Campaign') }} 🚀
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>