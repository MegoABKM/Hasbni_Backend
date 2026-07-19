<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;

class FcmService
{
    public function sendSilentUpdate($userId, $senderDeviceId = null)
    {
        $query = FcmToken::where('user_id', $userId);
        if ($senderDeviceId) {
            $query->where('device_id', '!=', $senderDeviceId);
        }
        
        $tokens = $query->pluck('token')->toArray();
        if (empty($tokens)) return;

        try {
            $accessToken = $this->getAccessToken();
            $url = 'https://fcm.googleapis.com/v1/projects/hasbni-pos/messages:send';

            foreach ($tokens as $token) {
                Http::withToken($accessToken)->post($url, [
                    'message' => [
                        'token' => $token,
                        'data' => [
                            'type' => 'data_updated',
                            'sender_device_id' => $senderDeviceId ?? 'server'
                        ],
                        'android' => ['priority' => 'high'],
                        'apns' => [
                            'payload' => ['aps' => ['content-available' => 1]],
                            'headers' => ['apns-priority' => '5']
                        ]
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM Silent Push Failed: ' . $e->getMessage());
        }
    }

    private function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/firebase-credentials.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();
            return $client->getAccessToken()['access_token'];
        });
    }
}