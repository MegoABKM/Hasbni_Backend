<?php

declare(strict_types=1);

namespace App\Saas\Filament\Pages;

use App\Models\User;
use App\Saas\Models\AppConfig;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Throwable;

class PushNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.push-notifications';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->can('View:PushNotifications');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bell-alert';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Push Notifications');
    }

    public function getTitle(): string
    {
        return __('Push Notifications');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('target_audience')
                    ->label(__('Target Audience'))
                    ->options([
                        'all' => __('All Tenants'),
                        'free' => __('Free Plan Tenants'),
                        'paid' => __('Paid Tenants'),
                    ])
                    ->required(),
                TextInput::make('title')->label(__('Notification Title'))->required(),
                Textarea::make('body')->label(__('Notification Body'))->required(),
            ])
            ->statePath('data');
    }

    public function sendPush(): void
    {
        $data = $this->form->getState();
        $topic = match ($data['target_audience']) {
            'free' => 'free_tenants',
            'paid' => 'paid_tenants',
            default => 'all_tenants',
        };
        $configuredFile = AppConfig::query()->where('key', 'firebase_json')->value('value');
        $configuredPath = filled($configuredFile) ? storage_path('app/'.$configuredFile) : null;
        $defaultPath = storage_path('app/firebase-auth.json');
        $credentialsPath = $configuredPath && file_exists($configuredPath)
            ? $configuredPath
            : (file_exists($defaultPath) ? $defaultPath : null);

        if ($credentialsPath === null) {
            Notification::make()
                ->title(__('Notification Credentials Missing'))
                ->body(__('Upload the notification credentials file in system settings.'))
                ->danger()
                ->send();

            return;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true);
        $projectId = $credentials['project_id'] ?? null;

        if (blank($projectId)) {
            Notification::make()
                ->title(__('Invalid Notification Credentials'))
                ->body(__('The notification project identifier is missing.'))
                ->danger()
                ->send();

            return;
        }

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($credentialsPath);
            $client->addScope([
                'https://www.googleapis.com/auth/firebase.messaging',
                'https://www.googleapis.com/auth/cloud-platform',
            ]);
            $client->fetchAccessTokenWithAssertion();
            $accessToken = $client->getAccessToken()['access_token'];
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'topic' => $topic,
                        'notification' => [
                            'title' => $data['title'],
                            'body' => $data['body'],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Notification::make()->title(__('Notification Broadcasted'))->success()->send();
                $this->form->fill();

                return;
            }

            Notification::make()
                ->title(__('Notification Broadcast Failed'))
                ->body($response->json('error.message') ?? __('Unknown Error'))
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()->title(__('Server Error'))->body($exception->getMessage())->danger()->send();
        }
    }
}
