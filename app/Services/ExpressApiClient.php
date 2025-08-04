<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpressApiClient
{
    protected string $baseUrl;
    protected ?string $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('services.express.base_url', 'http://api.chonapp.net');
        $this->apiToken = config('services.express.api_token');
    }

    public function sendNotificationToAllPlayers(array $notificationData): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($this->apiToken) {
                $headers['Authorization'] = "Bearer {$this->apiToken}";
            }

            $response = Http::withHeaders($headers)
                ->post("{$this->baseUrl}/api/notifications/send-to-all", $notificationData);

            if ($response->successful()) {
                Log::info('Notification sent successfully', [
                    'notification' => $notificationData,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status()
                ];
            }

            Log::error('Failed to send notification', [
                'notification' => $notificationData,
                'response' => $response->json(),
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception while sending notification', [
                'notification' => $notificationData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function sendNotificationToSpecificPlayers(array $notificationData, array $playerIds): array
    {
        try {
            $payload = array_merge($notificationData, ['player_ids' => $playerIds]);

            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($this->apiToken) {
                $headers['Authorization'] = "Bearer {$this->apiToken}";
            }

            $response = Http::withHeaders($headers)
                ->post("{$this->baseUrl}/api/notifications/send-to-players", $payload);

            if ($response->successful()) {
                Log::info('Notification sent to specific players successfully', [
                    'notification' => $notificationData,
                    'player_ids' => $playerIds,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status()
                ];
            }

            Log::error('Failed to send notification to specific players', [
                'notification' => $notificationData,
                'player_ids' => $playerIds,
                'response' => $response->json(),
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception while sending notification to specific players', [
                'notification' => $notificationData,
                'player_ids' => $playerIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function testConnection(): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($this->apiToken) {
                $headers['Authorization'] = "Bearer {$this->apiToken}";
            }

            $response = Http::withHeaders($headers)
                ->get("{$this->baseUrl}/api/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'status_code' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => 'API endpoint not responding',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}