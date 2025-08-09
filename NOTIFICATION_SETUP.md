# Laravel + Filament Notification System Setup Guide

## Overview

This notification system integrates with your Node/Express API via REST using an admin JWT. The Express API emits WebSocket events to players and stores history.

## Environment Configuration

Add the following to your `.env` file:

```env
# Node/Express API Configuration
API_BASE_URL=http://localhost:3001
API_ADMIN_TOKEN=your-admin-jwt-token-here
```

## Features Implemented

### ✅ Filament Admin Panel

-   Complete CRUD for notifications
-   Real-time notification sending
-   Test notification functionality
-   Notification history and management
-   Resend failed notifications

### ✅ Laravel Backend

-   Notification model with scopes and methods
-   Express REST API client service
-   Competition observer for automatic notifications
-   Scheduled job for delayed notifications
-   Console command for processing scheduled notifications

### ✅ Real-time Features

-   Immediate notification sending via Express REST API (admin JWT)
-   Express API emits WebSocket `notification` event to users
-   Scheduled notifications using Laravel's task scheduling
-   Automatic notifications on competition events
-   Comprehensive error handling and logging

## Usage

### 1. Access the Admin Panel

Navigate to `/admin/notifications` to manage notifications.

### 2. Create a Notification

-   Fill in title, message, type, and priority
-   Choose to send immediately or schedule for later
-   Use the "Send Test Notification" button to test

### 3. Automatic Notifications

The system automatically sends notifications when:

-   New competitions are created
-   Competition registration opens
-   Competition starts
-   5-minute reminders before competition start

### 4. Scheduled Notifications

-   Create notifications with future scheduled times
-   The system processes them every minute
-   Failed notifications can be resent

## Testing

### Run the Tests

```bash
./vendor/bin/sail artisan test tests/Feature/NotificationSystemTest.php
```

### Test the Command

```bash
./vendor/bin/sail artisan notifications:process-scheduled
```

### Test API Connection

```bash
./vendor/bin/sail artisan tinker
```

Then run:

```php
app(App\Services\ExpressApiClient::class)->testConnection();
```

## Express.js API Endpoints Used

-   `POST /api/v1/notifications/send-to-player`
    -   Headers: `Authorization: Bearer {ADMIN_JWT}`, `API-Version: v1`
    -   Body: `{ userIds: string[], title, title_kurdish?, message, message_kurdish?, type, priority, data? }`
-   `POST /api/v1/notifications`
    -   Create a notification (optionally scheduled). Add `send_immediately: true` to broadcast now.
-   `POST /api/v1/notifications/:id/send`
    -   Send an existing notification immediately
-   `POST /api/v1/notifications/bulk-send`
    -   Broadcast to all players (no `userIds`). Body supports bilingual fields
-   `GET /api/health` for health check

## Notification Types

-   **general**: General announcements
-   **competition**: Competition-related notifications
-   **announcement**: Important announcements
-   **maintenance**: System maintenance notifications
-   **update**: App updates and changes

## Priority Levels

-   **low**: Non-urgent notifications
-   **normal**: Standard notifications
-   **high**: Urgent notifications

## Troubleshooting

### Icon Issues

If you see "Svg by name not found" errors, the Heroicons have been updated. The system uses:

-   `heroicon-o-bell` for navigation
-   `heroicon-o-paper-airplane-tray` for send test
-   `heroicon-o-arrow-path` for resend

### API Connection Issues

1. Check your `.env` configuration
2. Verify the Express.js API is running
3. Test the connection using the test method
4. Check logs in `storage/logs/laravel.log`

### Migration Issues

If migrations fail, run:

```bash
./vendor/bin/sail artisan migrate:fresh
```

## Queue Processing

For production, make sure to run the queue worker:

```bash
./vendor/bin/sail artisan queue:work
```

The scheduled notifications are processed every minute via the console command.
