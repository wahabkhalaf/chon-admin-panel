# Laravel Admin Notification System

## Overview
This document describes how to send notifications from the Laravel Admin Panel to your Express.js backend server. The system supports both immediate and scheduled notifications, with bilingual support (English and Kurdish).

## Table of Contents
- [System Architecture](#system-architecture)
- [Configuration](#configuration)
- [Sending Notifications](#sending-notifications)
- [Scheduled Notifications](#scheduled-notifications)
- [API Endpoints](#api-endpoints)
- [Error Handling](#error-handling)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## System Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Laravel Admin │───▶│  Queue System    │───▶│ ExpressApiClient │
│     Panel       │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
                       ┌──────────────────┐    ┌─────────────────┐
                       │  Notification    │    │  Express.js     │
                       │     Model        │    │    Backend      │
                       └──────────────────┘    └─────────────────┘
```

## Configuration

### Environment Variables
Add these to your `.env` file:

```env
# Express.js Backend Configuration
API_BASE_URL=http://your-express-server.com:3001
API_ADMIN_TOKEN=your_secure_admin_token_here

# Queue Configuration (for scheduled notifications)
QUEUE_CONNECTION=database
```

### Services Configuration
The system is configured in `config/services.php`:

```php
'api' => [
    'base_url' => env('API_BASE_URL', 'http://localhost:3001'),
    'admin_token' => env('API_ADMIN_TOKEN'),
],
```

## Sending Notifications

### 1. Immediate Notifications

#### Using ExpressApiClient Service
```php
use App\Services\ExpressApiClient;

class NotificationController extends Controller
{
    public function sendImmediate(ExpressApiClient $apiClient)
    {
        $notificationData = [
            'title' => 'Welcome Message',
            'title_kurdish' => 'پەیامی بەخێربێیت',
            'message' => 'Welcome to our platform!',
            'message_kurdish' => 'بەخێربێیت بۆ پلاتفۆرمەکەمان!',
            'type' => 'welcome',
            'priority' => 'normal',
            'data' => [
                'screen' => 'home',
                'action' => 'navigate'
            ]
        ];

        // Send to specific players
        $result = $apiClient->sendNotification($notificationData, [1, 2, 3]);
        
        // Or broadcast to all players
        $result = $apiClient->sendNotification($notificationData);
        
        return response()->json($result);
    }
}
```

#### Using Artisan Command
```bash
# Send test notification
php artisan notifications:test

# Send scheduled notifications
php artisan notifications:process-scheduled
```

### 2. Scheduled Notifications

#### Create and Schedule
```php
use App\Models\Notification;
use App\Jobs\SendScheduledNotification;

// Create notification record
$notification = Notification::create([
    'title' => 'Daily Reminder',
    'title_kurdish' => 'ئەبەینی ڕۆژانە',
    'message' => 'Don\'t forget to check your account!',
    'message_kurdish' => 'پشتت لە هەژمارەکەت نەبێت!',
    'type' => 'reminder',
    'priority' => 'medium',
    'scheduled_at' => now()->addHours(24),
    'status' => 'pending'
]);

// Dispatch job to send at scheduled time
SendScheduledNotification::dispatch($notification);
```

#### Bulk Scheduling
```php
use App\Models\Player;

// Get all active players
$players = Player::where('is_active', true)->get();

foreach ($players as $player) {
    $notification = Notification::create([
        'title' => 'Personal Update',
        'title_kurdish' => 'نوێکردنەوەی کەسی',
        'message' => "Hello {$player->name}, here's your update!",
        'message_kurdish' => "سڵاو {$player->name}، ئەمە نوێکردنەوەکەتە!",
        'type' => 'personal',
        'priority' => 'high',
        'scheduled_at' => now()->addMinutes(30),
        'status' => 'pending',
        'target_player_id' => $player->id
    ]);
    
    SendScheduledNotification::dispatch($notification);
}
```

## API Endpoints

### Express.js Backend Requirements

#### Health Check
```
GET /api/health
Headers: 
  - Authorization: Bearer {admin_token}
  - API-Version: v1
```

#### Send to Specific Players
```
POST /api/v1/notifications/send-to-player
Headers: 
  - Authorization: Bearer {admin_token}
  - API-Version: v1
  - Content-Type: application/json

Body:
{
  "title": "Notification Title",
  "title_kurdish": "ناونیشانی نوتیفیکەیشن",
  "message": "Notification message content",
  "message_kurdish": "ناوەڕۆکی پەیامی نوتیفیکەیشن",
  "type": "info|warning|error|success|reminder|personal",
  "priority": "low|normal|high|urgent",
  "data": {
    "screen": "home",
    "action": "navigate",
    "custom_key": "custom_value"
  },
  "userIds": ["1", "2", "3"]
}
```

#### Create & Send Immediately
```
POST /api/v1/notifications
Headers: 
  - Authorization: Bearer {admin_token}
  - API-Version: v1
  - Content-Type: application/json

Body:
{
  "title": "Notification Title",
  "title_kurdish": "ناونیشانی نوتیفیکەیشن",
  "message": "Notification message content",
  "message_kurdish": "ناوەڕۆکی پەیامی نوتیفیکەیشن",
  "type": "info",
  "priority": "normal",
  "data": {},
  "send_immediately": true
}
```

## Notification Types and Priorities

### Types
- `info` - General information
- `warning` - Important warnings
- `error` - Error notifications
- `success` - Success messages
- `reminder` - Reminder notifications
- `personal` - Personal messages
- `welcome` - Welcome messages

### Priorities
- `low` - Low priority notifications
- `normal` - Standard priority
- `high` - High priority
- `urgent` - Urgent notifications

## Error Handling

### Response Format
```php
// Success Response
[
    'success' => true,
    'data' => [...],
    'status_code' => 200
]

// Error Response
[
    'success' => false,
    'error' => 'Error message',
    'status_code' => 400
]
```

### Logging
All notification attempts are logged:
- **Success**: Info level with response data
- **Failure**: Error level with error details
- **Exceptions**: Error level with stack trace

### Status Tracking
Notifications are tracked with statuses:
- `pending` - Waiting to be sent
- `sent` - Successfully sent
- `failed` - Failed to send

## Examples

### 1. Welcome Notification for New Users
```php
public function sendWelcomeNotification($playerId)
{
    $notificationData = [
        'title' => 'Welcome to Chon!',
        'title_kurdish' => 'بەخێربێیت بۆ چۆن!',
        'message' => 'We\'re excited to have you on board!',
        'message_kurdish' => 'ئێمە دڵخۆشین کە لەگەڵمان دەبیت!',
        'type' => 'welcome',
        'priority' => 'high',
        'data' => [
            'screen' => 'onboarding',
            'action' => 'complete_profile'
        ]
    ];

    $apiClient = new ExpressApiClient();
    return $apiClient->sendNotification($notificationData, [$playerId]);
}
```

### 2. Competition Reminder
```php
public function sendCompetitionReminder($competitionId)
{
    $competition = Competition::find($competitionId);
    $players = $competition->registrations()->pluck('player_id')->toArray();

    $notificationData = [
        'title' => 'Competition Starting Soon!',
        'title_kurdish' => 'پێشبڕکێکە بەم زووانە دەست پێدەکات!',
        'message' => "The competition '{$competition->name}' starts in 1 hour!",
        'message_kurdish' => "پێشبڕکێی '{$competition->name}' لە ماوەی ١ کاتژمێردا دەست پێدەکات!",
        'type' => 'reminder',
        'priority' => 'high',
        'data' => [
            'screen' => 'competition',
            'competition_id' => $competitionId,
            'action' => 'join_now'
        ]
    ];

    $apiClient = new ExpressApiClient();
    return $apiClient->sendNotification($notificationData, $players);
}
```

### 3. System Maintenance Alert
```php
public function sendMaintenanceAlert()
{
    $notificationData = [
        'title' => 'System Maintenance',
        'title_kurdish' => 'چاککردنەوەی سیستەم',
        'message' => 'System will be down for maintenance from 2:00 AM to 4:00 AM.',
        'message_kurdish' => 'سیستەمەکە لە ٢:٠٠ بەیانی تا ٤:٠٠ بەیانی بۆ چاککردنەوە دەبێت.',
        'type' => 'warning',
        'priority' => 'high',
        'data' => [
            'maintenance_start' => '2025-08-16T02:00:00Z',
            'maintenance_end' => '2025-08-16T04:00:00Z'
        ]
    ];

    $apiClient = new ExpressApiClient();
    return $apiClient->sendNotification($notificationData); // Broadcast to all
}
```

## Testing

### Test Connection
```php
use App\Services\ExpressApiClient;

$apiClient = new ExpressApiClient();
$result = $apiClient->testConnection();

if ($result['success']) {
    echo "✅ Connected to Express.js backend!";
} else {
    echo "❌ Connection failed: " . $result['error'];
}
```

### Test Notification
```bash
# Test the notification system
php artisan notifications:test

# Test with specific data
php artisan notifications:test --title="Test Title" --message="Test Message"
```

## Queue Management

### Start Queue Worker
```bash
# Start queue worker for processing scheduled notifications
php artisan queue:work

# Or run in background
php artisan queue:work --daemon
```

### Monitor Queue
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Troubleshooting

### Common Issues

#### 1. Connection Failed
- Check `API_BASE_URL` in `.env`
- Verify Express.js server is running
- Check firewall settings

#### 2. Authentication Failed
- Verify `API_ADMIN_TOKEN` in `.env`
- Check token format and validity
- Ensure token is not expired

#### 3. Notifications Not Sending
- Check queue worker is running
- Verify database connection
- Check notification status in database

#### 4. Permission Denied
```bash
# Fix storage permissions
sudo chown -R ubuntu:www-data storage/
sudo chmod -R 775 storage/
```

### Debug Mode
Enable debug logging in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Logs
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View queue logs
tail -f storage/logs/laravel-queue.log
```

## Best Practices

1. **Always handle errors** and log failures
2. **Use appropriate priorities** for different notification types
3. **Test notifications** in development before production
4. **Monitor queue performance** and failed jobs
5. **Use scheduled notifications** for non-urgent messages
6. **Keep notification messages concise** and actionable
7. **Use bilingual support** for better user experience
8. **Monitor API response times** and optimize if needed

## Security Considerations

1. **Secure admin token** - Use strong, unique tokens
2. **HTTPS only** - Always use HTTPS in production
3. **Rate limiting** - Implement rate limiting on Express.js side
4. **Input validation** - Validate all notification data
5. **Access control** - Restrict who can send notifications

---

**Last Updated**: August 16, 2025  
**Version**: 1.0  
**Maintainer**: Chon Admin Team
