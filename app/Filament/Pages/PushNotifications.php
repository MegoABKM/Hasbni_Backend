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
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;

class PushNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.push-notifications';
    public static function getNavigationIcon(): string { return 'heroicon-o-bell-alert'; }
    public static function getNavigationGroup(): ?string { return 'SaaS Management'; }
    public function getTitle(): string { return 'Push Notifications (إرسال إشعارات)'; }
    
    public ?array $data = [];

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

        $topic = match($data['target_audience']) {
            'all' => 'all_users',
            'free' => 'free_users',
            'pro' => 'pro_users',
            default => 'all_users',
        };

        $projectId = env('FIREBASE_PROJECT_ID');
        
        $credentialsPath = null;

        $firebaseFileKey = AppConfig::where('key', 'firebase_json')->value('value');

        if ($firebaseFileKey && file_exists(storage_path('app/' . $firebaseFileKey))) {
            $credentialsPath = storage_path('app/' . $firebaseFileKey);
        } 
        elseif (file_exists(storage_path('app/firebase-auth.json'))) {
            $credentialsPath = storage_path('app/firebase-auth.json');
        }

        if (!$credentialsPath) {
            Notification::make()
                ->title('Firebase JSON Missing!')
                ->body('لم يتم العثور على ملف الصلاحيات. يرجى رفعه من الإعدادات أو التأكد من وجود ملف firebase-auth.json في مجلد storage/app.')
                ->danger()
                ->send();
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

            // 🚀 البث المتقدم المتوافق مع بروتوكول FCM V1 لإجبار الهواتف على عرض الإشعار
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
                    // 👈 إرسال إشارات إيقاظ لنظام الأندرويد حتى لو كان الهاتف مقفلاً أو التطبيق بالخلفية
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    // 👈 إرسال إشارات إيقاظ لنظام iOS (Apple) مع تفعيل الصوت والعداد
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
                Notification::make()->title("Notification Broadcasted!")->success()->send();
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
                ->label('Send Notification 🚀')
                ->submit('sendPush')
                ->color('primary')
                ->requiresConfirmation(),
        ];
    }
}