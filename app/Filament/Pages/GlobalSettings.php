<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use App\Models\AppConfig;

class GlobalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.global-settings';

    public static function getNavigationIcon(): string { return 'heroicon-o-cog-8-tooth'; }
    public static function getNavigationGroup(): ?string { return __('System Settings'); }
    public static function getNavigationLabel(): string { return __('Global Settings'); }
    public function getTitle(): string { return __('Global Settings'); }

    public ?array $data = [];

    public function mount(): void
    {
        $configs = AppConfig::pluck('value', 'key')->toArray();

        $this->form->fill([
            'min_version' => $configs['min_version'] ?? '1.0.0',
            'update_url' => $configs['update_url'] ?? 'https://bhasbni.com',
            'whatsapp_number' => $configs['whatsapp_number'] ?? '',
            'is_disabled' => isset($configs['is_disabled']) ? ($configs['is_disabled'] === 'true' || $configs['is_disabled'] == 1) : false,
            'firebase_json' => $configs['firebase_json'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('firebase_json')
                    ->label(__('Firebase Credentials File'))
                    ->disk('local')
                    ->directory('firebase_keys')
                    ->acceptedFileTypes(['application/json'])
                    ->helperText(__('Optional: If firebase-auth.json is already uploaded in storage/app, leave this empty.')),

                TextInput::make('whatsapp_number')
                    ->label(__('WhatsApp Support Number'))
                    ->helperText(__('Enter number with country code without + or 00. Example: 966500000000'))
                    ->numeric()->required(),

                TextInput::make('min_version')
                    ->label(__('Minimum App Version'))
                    ->required(),

                TextInput::make('update_url')
                    ->label(__('Update URL'))
                    ->url()->required(),

                Toggle::make('is_disabled')
                    ->label(__('Maintenance Mode'))
                    ->onColor('danger')->offColor('success'),
            ])
            ->statePath('data');
    }

    public function saveSettings()
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            if ($key === 'is_disabled') {
                $value = $value ? 'true' : 'false';
            }

            if ($key === 'firebase_json') {
                if (!empty($value)) {
                    $tempPath = storage_path('app/' . $value);
                    $targetPath = storage_path('app/firebase-auth.json');
                    
                    if (file_exists($tempPath)) {
                        if (file_exists($targetPath)) {
                            unlink($targetPath); 
                        }
                        rename($tempPath, $targetPath); 
                    }
                    $value = 'firebase-auth.json'; 
                } else {
                    continue;
                }
            }

            AppConfig::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Notification::make()
            ->title(__('Settings Saved Successfully!'))
            ->success()
            ->send();
    }
}