<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;

class PushNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.push-notifications';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static UnitEnum|string|null $navigationGroup = 'SaaS Management';
    
    public ?array $data = [];

    public function getTitle(): string { return 'Push Notifications (إرسال إشعارات)'; }

    public function mount(): void {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('target_audience')
                ->label('Target Audience')
                ->options([
                    'all' => 'All Users (جميع المستخدمين)',
                    'free' => 'Free Plan Users (الباقة المجانية)',
                    'pro' => 'Paid Subscribers (المشتركين)',
                ])->required(),

            TextInput::make('title')->label('Notification Title (عنوان الإشعار)')->required(),
            Textarea::make('body')->label('Notification Body (نص الإشعار)')->required(),
        ])->statePath('data');
    }

    public function sendPush()
    {
        $data = $this->form->getState();

        // 1. Map dropdown to Firebase Topic
        $topic = match($data['target_audience']) {
            'all' => 'all_users',
            'free' => 'free_users',
            'pro' => 'pro_users',
            default => 'all_users',
        };

        $projectId = env('FIREBASE_PROJECT_ID');
        $credentialsPath = storage_path('app/firebase-auth.json'); // 👈 Path to the JSON file

        if (!file_exists($credentialsPath)) {
            Notification::make()->title('Firebase JSON file is missing!')->danger()->send();
            return;
        }

        try {
            // 2. Generate secure OAuth2 Token using Google Client
              $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            // 🚨 Fix: Added the 'cloud-platform' scope which is sometimes required by strict projects
            $client->addScope([
                'https://www.googleapis.com/auth/firebase.messaging',
                'https://www.googleapis.com/auth/cloud-platform'
            ]);
            $client->fetchAccessTokenWithAssertion();     $token = $client->getAccessToken();

            $accessToken = $token['access_token'];

            // 3. Send Request using Firebase V1 API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $data['title'],
                        'body' => $data['body'],
                    ]
                ]
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title("Notification Broadcasted!")
                    ->body("Message sent to topic: {$topic}")
                    ->success()
                    ->send();
                
                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Failed to broadcast.')
                    ->body($response->json('error.message') ?? 'Unknown error')
                    ->danger()
                    ->send();
                Log::error('FCM V1 Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Server Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            Log::error('FCM Exception: ' . $e->getMessage());
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Notification 🚀')
                ->submit('sendPush')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send Push Notification')
                ->modalDescription('Are you sure you want to broadcast this notification to the selected devices?'),
        ];
    }
}