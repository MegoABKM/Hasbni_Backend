<x-filament-panels::page>
    <form wire:submit="sendPush">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane">
                Broadcast Notification
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>