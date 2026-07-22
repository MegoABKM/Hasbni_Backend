<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Saas\Models\AppConfig;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private function getCredentialsPath()
    {
        $firebaseFileKey = AppConfig::where('key', 'firebase_json')->value('value');
        if ($firebaseFileKey && file_exists(storage_path('app/'.$firebaseFileKey))) {
            return storage_path('app/'.$firebaseFileKey);
        }

        return storage_path('app/firebase-auth.json');
    }

    public function sendSilentUpdate($userId, $senderDeviceId = null)
    {
        $query = FcmToken::where('user_id', $userId);
        if ($senderDeviceId) {
            $query->where('device_id', '!=', $senderDeviceId);
        }

        $tokens = $query->pluck('token')->toArray();
        if (empty($tokens)) {
            return;
        }

        $credentialsPath = $this->getCredentialsPath();

        if (! file_exists($credentialsPath)) {
            Log::error('FCM Silent Push Failed: Firebase credentials JSON not found.');

            return;
        }

        try {
            $json = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $json['project_id'] ?? null;

            if (! $projectId) {
                Log::error('FCM Silent Push Failed: Missing project_id in JSON.');

                return;
            }

            $accessToken = $this->getAccessToken($credentialsPath);
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            foreach ($tokens as $token) {
                Http::withToken($accessToken)->post($url, [
                    'message' => [
                        'token' => $token,
                        'data' => [
                            'type' => 'data_updated',
                            'sender_device_id' => $senderDeviceId ?? 'server',
                        ],
                        'android' => ['priority' => 'high'],
                        'apns' => [
                            'payload' => ['aps' => ['content-available' => 1]],
                            'headers' => ['apns-priority' => '5'],
                        ],
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM Silent Push Failed: '.$e->getMessage());
        }
    }

    private function getAccessToken($credentialsPath)
    {
        return Cache::remember('fcm_access_token', 3300, function () use ($credentialsPath) {
            $client = new GoogleClient;
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();

            return $client->getAccessToken()['access_token'];
        });
    }
}
