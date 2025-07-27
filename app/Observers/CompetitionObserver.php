<?php

namespace App\Observers;

use App\Models\Competition;
use App\Models\Notification;
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
            'message' => "\"{$competition->name}\" created by Admin - {$competition->description}",
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

        $result = $this->apiClient->sendNotificationToAllPlayers($notificationData);

        // Store notification record
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
        $reminderTime = $competition->start_time->subMinutes(5);

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
            'message' => "\"{$competition->name}\" is now open for registration! Join now!",
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

        $result = $this->apiClient->sendNotificationToAllPlayers($notificationData);

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
            'message' => "\"{$competition->name}\" has started! Good luck to all participants!",
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

        $result = $this->apiClient->sendNotificationToAllPlayers($notificationData);

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
}
