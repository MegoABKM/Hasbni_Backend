<x-filament-panels::page>
    <x-filament::section
        :heading="__('Push Notifications')"
        :description="__('Send a push notification to a selected tenant segment.')"
        icon="heroicon-o-bell-alert"
    >
        <form wire:submit="sendPush" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane" wire:confirm="{{ __('Send this notification to the selected audience?') }}">
                    {{ __('Send Notification') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
