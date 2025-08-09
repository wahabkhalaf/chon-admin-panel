# Player Notification API Documentation

## Overview

This API allows players to view their notification history, manage read status, and get notification counts. All endpoints require player authentication via WhatsApp number.

## Base URL

```
https://your-domain.com/api/player-notifications
```

## Authentication

All requests require the player's WhatsApp number as a parameter or in the request body.

## Endpoints

### 1. Get Notification History

**GET** `/api/player-notifications`

Retrieve a paginated list of notifications for a player.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `whatsapp_number` | string | Yes | Player's WhatsApp number |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (1-50, default: 15) |
| `filter` | string | No | Filter type: `all`, `unread`, `read`, `recent` (default: `all`) |

#### Example Request

```bash
curl -X GET "https://your-domain.com/api/player-notifications?whatsapp_number=+1234567890&page=1&per_page=10&filter=unread"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "notification_id": 5,
        "title": "New Competition Available",
        "message": "A new competition has started! Join now to win prizes.",
        "type": "competition",
        "priority": "high",
        "data": {
          "competition_id": 123,
          "prize_pool": 1000
        },
        "received_at": "2025-01-15T10:30:00.000000Z",
        "read_at": null,
        "is_read": false,
        "delivery_data": {
          "delivered": true,
          "timestamp": "2025-01-15T10:30:00.000000Z",
          "channel": "websocket"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 10,
      "total": 25,
      "from": 1,
      "to": 10
    },
    "summary": {
      "total_notifications": 25,
      "unread_count": 8,
      "read_count": 17
    }
  }
}
```

### 2. Get Unread Count

**GET** `/api/player-notifications/unread-count`

Get the count of unread notifications for a player.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `whatsapp_number` | string | Yes | Player's WhatsApp number |

#### Example Request

```bash
curl -X GET "https://your-domain.com/api/player-notifications/unread-count?whatsapp_number=+1234567890"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "unread_count": 8,
    "total_notifications": 25
  }
}
```

### 3. Mark Notification as Read

**POST** `/api/player-notifications/mark-as-read`

Mark a specific notification as read.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `whatsapp_number` | string | Yes | Player's WhatsApp number |
| `notification_id` | integer | Yes | ID of the notification to mark as read |

#### Example Request

```bash
curl -X POST "https://your-domain.com/api/player-notifications/mark-as-read" \
  -H "Content-Type: application/json" \
  -d '{
    "whatsapp_number": "+1234567890",
    "notification_id": 5
  }'
```

#### Example Response

```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "unread_count": 7
  }
}
```

### 4. Mark All Notifications as Read

**POST** `/api/player-notifications/mark-all-as-read`

Mark all unread notifications as read for a player.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `whatsapp_number` | string | Yes | Player's WhatsApp number |

#### Example Request

```bash
curl -X POST "https://your-domain.com/api/player-notifications/mark-all-as-read" \
  -H "Content-Type: application/json" \
  -d '{
    "whatsapp_number": "+1234567890"
  }'
```

#### Example Response

```json
{
  "success": true,
  "message": "All notifications marked as read",
  "data": {
    "unread_count": 0
  }
}
```

### 5. Get Notification Details

**GET** `/api/player-notifications/{notificationId}`

Get detailed information about a specific notification. Automatically marks the notification as read when viewed.

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `whatsapp_number` | string | Yes | Player's WhatsApp number |
| `notificationId` | integer | Yes | ID of the notification (in URL path) |

#### Example Request

```bash
curl -X GET "https://your-domain.com/api/player-notifications/5?whatsapp_number=+1234567890"
```

#### Example Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "notification_id": 5,
    "title": "New Competition Available",
    "message": "A new competition has started! Join now to win prizes.",
    "type": "competition",
    "priority": "high",
    "data": {
      "competition_id": 123,
      "prize_pool": 1000
    },
    "received_at": "2025-01-15T10:30:00.000000Z",
    "read_at": "2025-01-15T11:00:00.000000Z",
    "is_read": true,
    "delivery_data": {
      "delivered": true,
      "timestamp": "2025-01-15T10:30:00.000000Z",
      "channel": "websocket"
    }
  }
}
```

## Error Responses

### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "whatsapp_number": ["The whatsapp number field is required."]
  }
}
```

### Player Not Found (404)

```json
{
  "success": false,
  "message": "Player not found"
}
```

### Notification Not Found (404)

```json
{
  "success": false,
  "message": "Notification not found"
}
```

## Notification Types

- `general` - General announcements
- `competition` - Competition-related notifications
- `announcement` - Important announcements
- `maintenance` - System maintenance notifications
- `update` - App updates and changes

## Priority Levels

- `low` - Non-urgent notifications
- `normal` - Standard notifications
- `high` - Urgent notifications

## Filter Options

- `all` - All notifications (default)
- `unread` - Only unread notifications
- `read` - Only read notifications
- `recent` - Notifications from the last 30 days

## Integration with Express.js API

This Laravel API works in conjunction with your Express.js API:

1. **Real-time Delivery**: Notifications are sent via WebSocket through Express.js API
2. **History Storage**: Laravel stores notification history for each player
3. **Read Status**: Laravel tracks which notifications each player has read
4. **Analytics**: Provides read rates and delivery statistics

## Usage Examples

### Mobile App Integration

```javascript
// Get unread count for badge
const response = await fetch('/api/player-notifications/unread-count?whatsapp_number=' + playerWhatsApp);
const data = await response.json();
const unreadCount = data.data.unread_count;

// Get notification list
const notifications = await fetch('/api/player-notifications?whatsapp_number=' + playerWhatsApp + '&filter=unread');
const notificationList = await notifications.json();

// Mark as read when user taps notification
await fetch('/api/player-notifications/mark-as-read', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    whatsapp_number: playerWhatsApp,
    notification_id: notificationId
  })
});
```

### Web App Integration

```javascript
// Poll for new notifications
setInterval(async () => {
  const response = await fetch('/api/player-notifications/unread-count?whatsapp_number=' + playerWhatsApp);
  const data = await response.json();
  
  if (data.data.unread_count > 0) {
    // Show notification badge
    updateNotificationBadge(data.data.unread_count);
  }
}, 30000); // Check every 30 seconds
```
