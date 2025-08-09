<?php

namespace App\Observers;

use App\Models\Competition;
use App\Models\Notification;
use App\Models\Player;
use App\Services\ExpressApiClient;

class CompetitionObserver
{
    protected ExpressApiClient $apiClient;

    public function __construct(ExpressApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function created(Competition $competition)
    {
        // Send immediate notification about new competition
        $this->sendNewCompetitionNotification($competition);

        // Schedule 5-minute reminder notification
        $this->scheduleCompetitionReminder($competition);
    }

    public function updated(Competition $competition)
    {
        // Check if the competition status changed to 'open'
        if ($competition->wasChanged('open_time') && $competition->isOpen()) {
            $this->sendCompetitionOpenNotification($competition);
        }

        // Check if the competition status changed to 'active'
        if ($competition->wasChanged('start_time') && $competition->isActive()) {
            $this->sendCompetitionStartedNotification($competition);
        }
    }

    protected function sendNewCompetitionNotification(Competition $competition)
    {
        $notificationData = [
            'title' => 'New Competition Available! 🏆',
            'title_kurdish' => 'پێشبڕکێکی نوێ! 🏆',
            'message' => "\"{$competition->name}\" created by Admin - {$competition->description}",
            'message_kurdish' => "\"{$competition->name}\" لەلایەن ئەدمین دروست کرا - {$competition->description}",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'entryFee' => $competition->entry_fee,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
                'creator' => [
                    'id' => 'admin',
                    'nickname' => 'Admin'
                ]
            ]
        ];

        // Use broadcast-now endpoint to avoid per-player route mismatch
        $result = $this->apiClient->sendNotification($notificationData, []);

        Notification::create([
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'type' => $notificationData['type'],
            'priority' => $notificationData['priority'],
            'data' => $notificationData['data'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'api_response' => $result,
            'sent_at' => now(),
        ]);
    }

    protected function scheduleCompetitionReminder(Competition $competition)
    {
        $reminderTime = \Carbon\Carbon::parse($competition->start_time)->subMinutes(5);

        if ($reminderTime->isFuture()) {
            Notification::create([
                'title' => 'Competition Starting Soon! ⏰',
                'message' => "\"{$competition->name}\" starts in 5 minutes! Join now!",
                'type' => 'competition',
                'priority' => 'high',
                'data' => [
                    'competitionId' => $competition->id,
                    'competitionName' => $competition->name,
                    'startTime' => $competition->start_time,
                    'creator' => [
                        'id' => 'admin',
                        'nickname' => 'Admin'
                    ]
                ],
                'scheduled_at' => $reminderTime,
                'status' => 'pending',
            ]);
        }
    }

    protected function sendCompetitionOpenNotification(Competition $competition)
    {
        $notificationData = [
            'title' => 'Competition Registration Open! 🎯',
            'title_kurdish' => 'خۆت تۆمار بکە بۆ پێشبڕکێ! 🎯',
            'message' => "\"{$competition->name}\" is now open for registration! Join now!",
            'message_kurdish' => "خۆت تۆمار بکە بۆ \"{$competition->name}\"! ئێستا دەستپێکرد!",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'entryFee' => $competition->entry_fee,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
                'creator' => [
                    'id' => 'admin',
                    'nickname' => 'Admin'
                ]
            ]
        ];

        $result = $this->apiClient->sendNotification($notificationData, []);

        Notification::create([
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'type' => $notificationData['type'],
            'priority' => $notificationData['priority'],
            'data' => $notificationData['data'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'api_response' => $result,
            'sent_at' => now(),
        ]);
    }

    protected function sendCompetitionStartedNotification(Competition $competition)
    {
        $notificationData = [
            'title' => 'Competition Started! 🚀',
            'title_kurdish' => 'پێشبڕکێ دەستپێکرد! 🚀',
            'message' => "\"{$competition->name}\" has started! Good luck to all participants!",
            'message_kurdish' => "\"{$competition->name}\" دەستپێکرد! سەردەمی باش بۆ هەموو بەشداربووان!",
            'type' => 'competition',
            'priority' => 'normal',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
                'creator' => [
                    'id' => 'admin',
                    'nickname' => 'Admin'
                ]
            ]
        ];

        $summary = $this->sendToAllPlayersInBatches($notificationData);

        Notification::create([
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'type' => $notificationData['type'],
            'priority' => $notificationData['priority'],
            'data' => $notificationData['data'],
            'status' => $summary['failed'] === 0 ? 'sent' : 'failed',
            'api_response' => $summary,
            'sent_at' => now(),
        ]);

    }

    /**
     * Send notification to all players in batches to satisfy REST API requirements.
     */
    protected function sendToAllPlayersInBatches(array $notificationData, int $batchSize = 1000): array
    {
        $total = 0;
        $successful = 0;
        $failed = 0;
        $responses = [];

        Player::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById($batchSize, function ($players) use (&$total, &$successful, &$failed, &$responses, $notificationData) {
                $userIds = $players->pluck('id')->map(fn($id) => (string) $id)->values()->all();
                $total += count($userIds);

                $result = $this->apiClient->sendNotification($notificationData, $userIds);
                $responses[] = $result;
                if (!empty($result['success']) && $result['success'] === true) {
                    $successful += count($userIds);
                } else {
                    $failed += count($userIds);
                }
            });

        // If there are no players yet, broadcast using the API's create-now endpoint
        if ($total === 0) {
            $result = $this->apiClient->sendNotification($notificationData, []);
            $responses[] = $result;
            return [
                'success' => (bool) ($result['success'] ?? false),
                'total_users' => 0,
                'successful' => (int) (($result['success'] ?? false) ? 1 : 0),
                'failed' => (int) (($result['success'] ?? false) ? 0 : 1),
                'batch_responses' => $responses,
                'note' => 'No players found; broadcasted via send_immediately',
            ];
        }

        return [
            'success' => $failed === 0,
            'total_users' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'batch_responses' => $responses,
        ];
    }
}
