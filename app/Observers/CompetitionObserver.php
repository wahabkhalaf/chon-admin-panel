<?php

namespace App\Observers;

use App\Models\Competition;
use App\Models\Notification;
use App\Models\Player;
use App\Services\FcmNotificationService;

class CompetitionObserver
{
    protected FcmNotificationService $fcmService;

    public function __construct(FcmNotificationService $fcmService)
    {
        $this->fcmService = $fcmService;
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
            'message' => "New competition \"{$competition->name}\" is now available.",
            'message_kurdish' => "پێشبڕکێی \"" . ($competition->name_kurdish ?: $competition->name) . "\" ئێستا بەردەستە.",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'competitionNameKurdish' => $competition->name_kurdish,
                'description' => $competition->description,
                'descriptionKurdish' => $competition->description_kurdish,
                'entryFee' => $competition->entry_fee,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
            ]
        ];

        // Use FCM service directly for broadcast
        $result = $this->fcmService->sendBroadcastNotification($notificationData);

        Notification::create([
            'title' => $notificationData['title'],
            'title_kurdish' => $notificationData['title_kurdish'],
            'message' => $notificationData['message'],
            'message_kurdish' => $notificationData['message_kurdish'],
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
        // Schedule 15-minute reminder notification
        $fifteenMinReminderTime = \Carbon\Carbon::parse($competition->start_time)->subMinutes(15);
        if ($fifteenMinReminderTime->isFuture()) {
            $this->createScheduledNotification($competition, $fifteenMinReminderTime, 15);
        }

        // Schedule 5-minute reminder notification
        $fiveMinReminderTime = \Carbon\Carbon::parse($competition->start_time)->subMinutes(5);
        if ($fiveMinReminderTime->isFuture()) {
            $this->createScheduledNotification($competition, $fiveMinReminderTime, 5);
        }

        // Schedule 1-minute reminder notification
        $oneMinReminderTime = \Carbon\Carbon::parse($competition->start_time)->subMinute();
        if ($oneMinReminderTime->isFuture()) {
            $this->createScheduledNotification($competition, $oneMinReminderTime, 1);
        }
    }

    protected function createScheduledNotification(Competition $competition, \Carbon\Carbon $scheduledTime, int $minutesBefore)
    {
        $title = match($minutesBefore) {
            15 => 'Competition Starting in 15 Minutes! 🎯',
            5 => 'Competition Starting Soon! ⏰',
            1 => 'Competition Starting in 1 Minute! 🚨',
            default => "Competition Starting in {$minutesBefore} Minutes! ⏰"
        };
        
        $titleKurdish = match($minutesBefore) {
            15 => 'پێشبڕکێ لە ١٥ خولەکدا دەستپێدەکات! 🎯',
            5 => 'بەم دوایە پێشبڕکێ دەستپێدەکات! ⏰',
            1 => 'پێشبڕکێ لە ١ خولەکدا دەستپێدەکات! 🚨',
            default => "پێشبڕکێ لە {$minutesBefore} خولەکدا دەستپێدەکات! ⏰"
        };
        
        $message = match($minutesBefore) {
            15 => "\"{$competition->name}\" starts in 15 minutes! Don't miss out!",
            5 => "\"{$competition->name}\" starts in 5 minutes! Join now!",
            1 => "\"{$competition->name}\" starts in 1 minute! Get ready!",
            default => "\"{$competition->name}\" starts in {$minutesBefore} minutes!"
        };
        
        $messageKurdish = match($minutesBefore) {
            15 => "\"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ١٥ خولەکدا دەستپێدەکات! لەدەست مەدە!",
            5 => "\"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ٥ خولەکدا دەستپێدەکات! ئێستا بەشدار ببە!",
            1 => "\"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ١ خولەکدا دەستپێدەکات! ئامادە بە!",
            default => "\"" . ($competition->name_kurdish ?: $competition->name) . "\" لە {$minutesBefore} خولەکدا دەستپێدەکات!"
        };

        $notificationData = [
            'title' => $title,
            'title_kurdish' => $titleKurdish,
            'message' => $message,
            'message_kurdish' => $messageKurdish,
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'competitionNameKurdish' => $competition->name_kurdish,
                'description' => $competition->description,
                'descriptionKurdish' => $competition->description_kurdish,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
            ]
        ];

        // Create notification record with pending status
        Notification::create([
            'title' => $notificationData['title'],
            'title_kurdish' => $notificationData['title_kurdish'],
            'message' => $notificationData['message'],
            'message_kurdish' => $notificationData['message_kurdish'],
            'type' => $notificationData['type'],
            'priority' => $notificationData['priority'],
            'data' => $notificationData['data'],
            'scheduled_at' => $scheduledTime,
            'status' => 'pending',
        ]);
    }

    protected function sendCompetitionOpenNotification(Competition $competition)
    {
        $notificationData = [
            'title' => 'Competition Registration Open! 🎯',
            'title_kurdish' => 'خۆت تۆمار بکە بۆ پێشبڕکێ! 🎯',
            'message' => "\"{$competition->name}\" is now open for registration! Join now!",
            'message_kurdish' => "خۆت تۆمار بکە بۆ \"" . ($competition->name_kurdish ?: $competition->name) . "\"! ئێستا دەستپێکرد!",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'entryFee' => $competition->entry_fee,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
            ]
        ];

        $result = $this->fcmService->sendBroadcastNotification($notificationData);

        Notification::create([
            'title' => $notificationData['title'],
            'title_kurdish' => $notificationData['title_kurdish'],
            'message' => $notificationData['message'],
            'message_kurdish' => $notificationData['message_kurdish'],
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
            'message_kurdish' => "\"" . ($competition->name_kurdish ?: $competition->name) . "\" دەستپێکرد! سەردەمی باش بۆ هەموو بەشداربووان!",
            'type' => 'competition',
            'priority' => 'normal',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'competitionNameKurdish' => $competition->name_kurdish,
                'description' => $competition->description,
                'descriptionKurdish' => $competition->description_kurdish,
                'startTime' => $competition->start_time,
                'gameType' => $competition->game_type,
            ]
        ];

        $summary = $this->sendToAllPlayersInBatches($notificationData);

        Notification::create([
            'title' => $notificationData['title'],
            'title_kurdish' => $notificationData['title_kurdish'],
            'message' => $notificationData['message'],
            'message_kurdish' => $notificationData['message_kurdish'],
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

                // For specific users, we need to get their FCM tokens and send individually
                // Since we don't have FCM tokens in this context, we'll broadcast to topic
                $result = $this->fcmService->sendBroadcastNotification($notificationData);
                $responses[] = $result;
                if (!empty($result['success']) && $result['success'] === true) {
                    $successful += count($userIds);
                } else {
                    $failed += count($userIds);
                }
            });

        // If there are no players yet, broadcast using the topic
        if ($total === 0) {
            $result = $this->fcmService->sendBroadcastNotification($notificationData);
            $responses[] = $result;
            return [
                'success' => (bool) ($result['success'] ?? false),
                'total_users' => 0,
                'successful' => (int) (($result['success'] ?? false) ? 1 : 0),
                'failed' => (int) (($result['success'] ?? false) ? 0 : 1),
                'batch_responses' => $responses,
                'note' => 'No players found; broadcasted via topic',
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
