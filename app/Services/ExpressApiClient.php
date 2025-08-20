<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpressApiClient
{
    protected string $baseUrl;
    protected ?string $adminToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.api.base_url', 'http://localhost:8000'), '/');
        $this->adminToken = config('services.api.admin_token');
    }

    /**
     * Targeted send to specific players or broadcast.
     * - If $userIds is non-empty: POST /api/v1/notifications/send-to-player
     * - If $userIds is empty: POST /api/v1/notifications with send_immediately=true
     */
    public function sendNotification(array $notificationData, array $userIds = []): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'API-Version' => 'v1',
            ];

            if (!empty($userIds)) {
                // Targeted send
                $payload = array_merge($notificationData, [
                    'userIds' => array_map(fn($id) => (string) $id, $userIds),
                ]);
                $response = Http::withToken((string) $this->adminToken)
                    ->withHeaders($headers)
                    ->post("{$this->baseUrl}/api/v1/notifications/send-to-player", $payload);
            } else {
                // Broadcast now via create endpoint with send_immediately flag
                $payload = array_merge($notificationData, [
                    'send_immediately' => true,
                ]);
                $response = Http::withToken((string) $this->adminToken)
                    ->withHeaders($headers)
                    ->post("{$this->baseUrl}/api/v1/notifications", $payload);
            }

            if ($response->successful()) {
                Log::info('Notification sent successfully', [
                    'notification' => $payload,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status()
                ];
            }

            Log::error('Failed to send notification', [
                'notification' => $payload,
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

    public function testConnection(): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'API-Version' => 'v1',
            ];

            $response = Http::withToken((string) $this->adminToken)
                ->withHeaders($headers)
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