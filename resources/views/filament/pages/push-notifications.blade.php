<x-filament-panels::page>
    <x-filament::section
        heading="Push Notifications"
        description="Send a Firebase Cloud Messaging notification to a selected subscriber segment."
        icon="heroicon-o-bell-alert"
    >
        <form wire:submit="sendPush" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane" wire:confirm="Send this notification to the selected audience?">
                    Send Notification
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
