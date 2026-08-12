<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $client;
    protected $projectId;

    public function __construct()
    {
        $this->client = new Client();
        
        // Letakkan auth config file di storage/app/firebase-auth.json
        $credentialsPath = storage_path('app/firebase-auth.json');
        
        if (file_exists($credentialsPath)) {
            $this->client->setAuthConfig($credentialsPath);
            $this->client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            // Baca Project ID dari JSON Auth File
            $json = json_decode(file_get_contents($credentialsPath), true);
            $this->projectId = $json['project_id'] ?? null;
        }
    }

    public function getAccessToken()
    {
        try {
            $this->client->fetchAccessTokenWithAssertion();
            $accessToken = $this->client->getAccessToken();
            return $accessToken['access_token'];
        } catch (\Exception $e) {
            Log::error('Firebase access token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send push notification to a specific token.
     * 
     * @param string|array $tokens Single FCM token or array of tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Optional data payload
     */
    public function sendNotification($tokens, $title, $body, $data = [])
    {
        if (!$this->projectId) {
            Log::error('Firebase project ID is not set. Please check firebase-auth.json.');
            return false;
        }

        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            Log::error('Could not get Firebase Access Token.');
            return false;
        }

        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
        $successCount = 0;

        foreach ($tokens as $token) {
            if (empty($token)) {
                continue;
            }

            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => empty($data) ? (object)[] : $data, // Must be object if empty, but FCM prefers explicit strings for data keys/values
                ]
            ];

            // Ensure data array contains only string values (FCM requirement)
            if (!empty($message['message']['data'])) {
                foreach ($message['message']['data'] as $k => $v) {
                    $message['message']['data'][$k] = (string) $v;
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if ($response->successful()) {
                $successCount++;
            } else {
                Log::error('FCM Send Error: ' . $response->body());
            }
        }

        return $successCount > 0;
    }
}
