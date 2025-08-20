<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestNotificationToken extends Command
{
    protected $signature = 'notifications:test-token';
    protected $description = 'Test Node/Express admin token by calling /api/health and a dry-run send endpoint';

    public function handle(): int
    {
        $baseUrl = config('services.api.base_url');
        $token = config('services.api.admin_token');

        $this->info("Base URL: {$baseUrl}");

        if (!$token) {
            $this->error('API_ADMIN_TOKEN is not set.');
            return 1;
        }

        // Health check
        $this->info('1) GET /api/health');
        $health = Http::withToken($token)->get($baseUrl . '/api/health');
        $this->line(json_encode([
            'status' => $health->status(),
            'json' => $health->json(),
        ], JSON_PRETTY_PRINT));

        // Dry-run send (empty userIds allowed by API → broadcast or validation error)
        $this->info('2) Send notification via FCM');
        $payload = [
            'userIds' => ['1'],
            'title' => 'Token Test',
            'message' => 'Testing admin token auth',
            'type' => 'personal',
            'priority' => 'low',
            'data' => ['ping' => true],
        ];

        $resp = Http::withToken($token)
            ->withHeaders(['API-Version' => 'v1'])
            ->post($baseUrl . '/api/notifications/send-to-player', $payload);

        $this->line(json_encode([
            'status' => $resp->status(),
            'json' => $resp->json(),
        ], JSON_PRETTY_PRINT));

        if ($resp->successful()) {
            $this->info('Token works.');
            return 0;
        }

        $this->error('Token invalid or API rejected the request.');
        return 1;
    }
}


