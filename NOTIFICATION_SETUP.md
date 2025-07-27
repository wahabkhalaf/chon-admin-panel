# Laravel + Filament Notification System Setup Guide

## Overview

This notification system integrates with your Express.js API to send real-time notifications to all players via WebSocket.

## Environment Configuration

Add the following to your `.env` file:

```env
# Express.js API Configuration
EXPRESS_API_BASE_URL=http://localhost:3001
EXPRESS_API_TOKEN=your-admin-jwt-token-here
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
-   Express.js API client service
-   Competition observer for automatic notifications
-   Scheduled job for delayed notifications
-   Console command for processing scheduled notifications

### ✅ Real-time Features

-   Immediate notification sending via Express.js API
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

## Express.js API Endpoints Expected

Your Express.js API should have these endpoints:

1. `POST /api/notifications/send-to-all`

    - Sends notification to all connected players
    - Requires Bearer token authentication

2. `POST /api/notifications/send-to-players`

    - Sends notification to specific players
    - Requires player_ids array in payload

3. `GET /api/health`
    - Health check endpoint for connection testing

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
