<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\AppConfig;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;

class PushNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.push-notifications';
    
    public static function getNavigationIcon(): string { return 'heroicon-o-bell-alert'; }
    public static function getNavigationGroup(): ?string { return __('SaaS Management'); }
    public static function getNavigationLabel(): string { return __('Push Notifications'); }
    public function getTitle(): string { return __('Push Notifications'); }
    
    public ?array $data = [];

    public function mount(): void {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('target_audience')
                ->label(__('Target Audience'))
                ->options([
                    'all' => __('All Users'),
                    'free' => __('Free Plan Users'),
                    'pro' => __('Paid Subscribers'),
                ])->required(),

            TextInput::make('title')->label(__('Notification Title'))->required(),
            Textarea::make('body')->label(__('Notification Body'))->required(),
        ])->statePath('data');
    }

    public function sendPush()
    {
        $data = $this->form->getState();

        $topic = match($data['target_audience']) {
            'all' => 'all_users',
            'free' => 'free_users',
            'pro' => 'pro_users',
            default => 'all_users',
        };

        $credentialsPath = null;
        $firebaseFileKey = AppConfig::where('key', 'firebase_json')->value('value');

        // Locate the JSON file
        if ($firebaseFileKey && file_exists(storage_path('app/' . $firebaseFileKey))) {
            $credentialsPath = storage_path('app/' . $firebaseFileKey);
        } 
        elseif (file_exists(storage_path('app/firebase-auth.json'))) {
            $credentialsPath = storage_path('app/firebase-auth.json');
        }

        // Check if file was found BEFORE reading it
        if (!$credentialsPath) {
            Notification::make()
                ->title('Firebase JSON Missing!')
                ->body('لم يتم العثور على ملف الصلاحيات. يرجى رفعه من الإعدادات.')
                ->danger()
                ->send();
            return;
        }

        // 🚀 Fix: Now we safely read the file to get the project ID dynamically
        $jsonContent = json_decode(file_get_contents($credentialsPath), true);
        $projectId = $jsonContent['project_id'] ?? null;

        if (!$projectId) {
            Notification::make()->title('Invalid Firebase JSON')->body('Missing project_id in JSON')->danger()->send();
            return;
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->addScope([
                'https://www.googleapis.com/auth/firebase.messaging',
                'https://www.googleapis.com/auth/cloud-platform'
            ]);
            $client->fetchAccessTokenWithAssertion();     
            $token = $client->getAccessToken();
            $accessToken = $token['access_token'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $data['title'],
                        'body' => $data['body'],
                    ],
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ]
            ]);

            if ($response->successful()) {
                Notification::make()->title(__("Notification Broadcasted!"))->success()->send();
                $this->form->fill();
            } else {
                Notification::make()->title('Failed to broadcast.')->body($response->json('error.message') ?? 'Unknown error')->danger()->send();
            }

        } catch (\Exception $e) {
            Notification::make()->title('Server Error')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label(__('Send Notification'))
                ->submit('sendPush')
                ->color('primary')
                ->requiresConfirmation(),
        ];
    }
}