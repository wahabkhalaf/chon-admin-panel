# FCM Multi-Language Notification System Documentation

## Overview

This documentation describes the complete multi-language notification system for the CHON app, supporting English, Kurdish (Sorani), and Arabic languages through Firebase Cloud Messaging (FCM).

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [FCM Message Structure](#fcm-message-structure)
3. [Language Detection System](#language-detection-system)
4. [Language Selection Logic](#language-selection-logic)
5. [Storage Structure](#storage-structure)
6. [Display Logic](#display-logic)
7. [Implementation Details](#implementation-details)
8. [API Integration](#api-integration)
9. [Testing Guide](#testing-guide)
10. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

The multi-language notification system operates through several layers:

```
📡 FCM Server Message
    ↓
🔍 Language Detection (SharedPreferences)
    ↓
🌍 Language Selection Logic
    ↓
📱 System Notification Display (Localized)
    ↓
💾 Local Storage (All Languages Preserved)
    ↓
📱 App Notification Screen (Language-Aware)
```

### Key Components

- **FCM Service**: Handles incoming notifications and language selection
- **Notification Provider**: Manages notification data and language preferences
- **Notification Screen**: Displays localized content based on user language
- **Local Storage**: Preserves all language variants for offline access

---

## FCM Message Structure

### Server-Side Message Format

The server must send FCM messages with the following structure:

```json
{
  "notification": {
    "title": "New Competition",           // English (default/fallback)
    "body": "Join the competition now!"  // English (default/fallback)
  },
  "data": {
    "title_kurdish": "پێشبڕکێی نوێ",     // Kurdish title
    "message_kurdish": "ئێستا بەشداری بکە!", // Kurdish message
    "title_arabic": "مسابقة جديدة",        // Arabic title  
    "message_arabic": "انضم الآن!",       // Arabic message
    "type": "competition_start",          // Notification type
    "priority": "high",                   // Notification priority
    "screen": "home"                      // Navigation target (optional)
  }
}
```

### Required Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `notification.title` | String | ✅ | English title (fallback) |
| `notification.body` | String | ✅ | English message (fallback) |
| `data.title_kurdish` | String | ❌ | Kurdish title |
| `data.message_kurdish` | String | ❌ | Kurdish message |
| `data.title_arabic` | String | ❌ | Arabic title |
| `data.message_arabic` | String | ❌ | Arabic message |
| `data.type` | String | ✅ | Notification type |
| `data.priority` | String | ❌ | Notification priority |

---

## Language Detection System

### Language Storage

User language preferences are stored in SharedPreferences with the key `'selected_language'`:

```dart
// Language codes
'en' - English (default)
'kr' - Kurdish (Sorani)
'ar' - Arabic
```

### Detection Implementation

```dart
// Helper method to get current language from SharedPreferences
static Future<String> _getCurrentLanguage() async {
  try {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('selected_language') ?? 'en';
  } catch (e) {
    return 'en'; // Default to English on error
  }
}
```

---

## Language Selection Logic

### Priority Hierarchy

The system follows a specific fallback hierarchy for each language:

#### Kurdish (kr)
```dart
title = message.data['title_kurdish'] ?? 
        message.notification?.title ?? 
        'New Notification'

message = message.data['message_kurdish'] ?? 
          message.notification?.body ?? 
          'You have a new message'
```

#### Arabic (ar)
```dart
title = message.data['title_arabic'] ?? 
        message.notification?.title ?? 
        'New Notification'

message = message.data['message_arabic'] ?? 
          message.notification?.body ?? 
          'You have a new message'
```

#### English (en)
```dart
title = message.notification?.title ?? 
        'New Notification'

message = message.notification?.body ?? 
          'You have a new message'
```

### Selection Implementation

```dart
static Future<Map<String, String>> _getLocalizedNotificationContent(
    RemoteMessage message) async {
  final currentLanguage = await _getCurrentLanguage();
  String title;
  String messageBody;

  switch (currentLanguage) {
    case 'kr':
      title = message.data['title_kurdish'] ?? 
             message.notification?.title ?? 'New Notification';
      messageBody = message.data['message_kurdish'] ?? 
                   message.notification?.body ?? 'You have a new message';
      break;
    case 'ar':
      title = message.data['title_arabic'] ?? 
             message.notification?.title ?? 'New Notification';
      messageBody = message.data['message_arabic'] ?? 
                   message.notification?.body ?? 'You have a new message';
      break;
    default: // English
      title = message.notification?.title ?? 'New Notification';
      messageBody = message.notification?.body ?? 'You have a new message';
      break;
  }

  return {'title': title, 'message': messageBody};
}
```

---

## Storage Structure

### Local Storage Format

All language variants are preserved in local storage:

```json
{
  "id": "1234567890",
  "title": "New Competition",                    // English
  "title_kurdish": "پێشبڕکێی نوێ",             // Kurdish
  "title_arabic": "مسابقة جديدة",               // Arabic
  "message": "Join the competition now!",       // English
  "message_kurdish": "ئێستا بەشداری بکە!",      // Kurdish
  "message_arabic": "انضم الآن!",               // Arabic
  "type": "competition_start",
  "priority": "high",
  "data": {
    "title_kurdish": "پێشبڕکێی نوێ",
    "message_kurdish": "ئێستا بەشداری بکە!",
    "title_arabic": "مسابقة جديدة",
    "message_arabic": "انضم الآن!",
    "screen": "home"
  },
  "fcm_message_id": "fcm_1234567890",
  "timestamp": "2024-01-01T12:00:00Z",
  "is_read": false
}
```

### Storage Implementation

```dart
// Store notification locally with all language variants
await localStorage.storeNotification({
  'title': message.notification?.title ?? 'New Notification',
  'title_kurdish': message.data['title_kurdish'] ?? '',
  'title_arabic': message.data['title_arabic'] ?? '',
  'message': message.notification?.body ?? 'You have a new message',
  'message_kurdish': message.data['message_kurdish'] ?? '',
  'message_arabic': message.data['message_arabic'] ?? '',
  'type': message.data['type'] ?? 'info',
  'priority': message.data['priority'] ?? 'normal',
  'data': message.data,
  'fcm_message_id': message.messageId,
});
```

---

## Display Logic

### System Notification Display

When the app is closed or in background, the system notification popup shows localized content:

```dart
// Get localized content for the notification
final localizedContent = await FcmService._getLocalizedNotificationContent(message);

await flutterLocalNotificationsPlugin.show(
  message.hashCode,
  localizedContent['title']!,      // Language-specific title
  localizedContent['message']!,    // Language-specific message
  platformChannelSpecifics,
  payload: jsonEncode(message.data),
);
```

### App Notification Screen Display

In the app's notification screen, content is displayed based on current language:

```dart
// Get current language code
String _getCurrentLanguageCode(BuildContext context) {
  final locale = Localizations.localeOf(context);
  return locale.languageCode;
}

// Display localized content
Text(
  notification.getTitleInLanguage(_getCurrentLanguageCode(context)),
  style: TextStyle(
    color: Colors.white,
    fontSize: 16,
    fontWeight: FontWeight.w600,
  ),
)

Text(
  notification.getMessageInLanguage(_getCurrentLanguageCode(context)),
  style: TextStyle(
    color: Colors.white.withOpacity(0.8),
    fontSize: 14,
  ),
)
```

---

## Implementation Details

### File Structure

```
lib/
├── services/
│   ├── fcm_service.dart              # FCM handling and language selection
│   └── local_notification_storage.dart # Local storage management
├── models/
│   └── notification_data.dart        # Notification data model
├── providers/
│   └── notification_provider.dart    # Notification state management
└── screens/
    └── notifications_screen.dart     # Notification display UI
```

### Key Methods

#### FCM Service Methods

- `_getCurrentLanguage()` - Gets user's language preference
- `_getLocalizedNotificationContent()` - Selects appropriate language content
- `_showLocalNotification()` - Displays localized system notification
- `_firebaseMessagingBackgroundHandler()` - Handles notifications when app is closed

#### Notification Data Methods

- `fromJsonWithLanguage()` - Creates notification with language preference
- `getTitleInLanguage()` - Gets title in specific language
- `getMessageInLanguage()` - Gets message in specific language

#### Notification Provider Methods

- `refreshNotificationsWithLanguage()` - Refreshes notifications with current language
- `loadNotifications()` - Loads notifications from API with language support

---

## API Integration

### Backend Requirements

The backend API must include language-specific fields in notification responses:

```json
{
  "id": "123",
  "title": "English Title",
  "title_kurdish": "Kurdish Title", 
  "title_arabic": "Arabic Title",
  "message": "English Message",
  "message_kurdish": "Kurdish Message",
  "message_arabic": "Arabic Message",
  "type": "notification_type",
  "created_at": "2024-01-01T00:00:00Z",
  "read": false
}
```

### FCM Token Registration

The app registers FCM tokens with the server to enable targeted notifications:

```dart
await _sendTokenToServer(token);

// Server endpoint: POST /api/v1/player/fcm-token
// Body: {
//   "fcm_token": "device_token_here",
//   "device_type": "android" | "ios"
// }
```

---

## Testing Guide

### Test Scenarios

#### 1. Language Selection Testing

1. **English Language:**
   - Set language to English
   - Send FCM with all language variants
   - Verify English content is displayed

2. **Kurdish Language:**
   - Set language to Kurdish
   - Send FCM with Kurdish content
   - Verify Kurdish content is displayed
   - Test fallback to English if Kurdish missing

3. **Arabic Language:**
   - Set language to Arabic
   - Send FCM with Arabic content
   - Verify Arabic content is displayed
   - Test fallback to English if Arabic missing

#### 2. App State Testing

1. **App Closed:**
   - Close the app completely
   - Send FCM notification
   - Verify system notification shows correct language
   - Open app and verify notification appears in list

2. **App in Background:**
   - Minimize the app
   - Send FCM notification
   - Verify system notification shows correct language

3. **App in Foreground:**
   - Keep app open
   - Send FCM notification
   - Verify in-app notification shows correct language

#### 3. Fallback Testing

1. **Missing Language Content:**
   - Send FCM with only English content
   - Test with all language settings
   - Verify fallback to English works

2. **Empty Content:**
   - Send FCM with empty language fields
   - Verify default messages are used

### Test Data

```json
// Complete test notification
{
  "notification": {
    "title": "Test Competition",
    "body": "This is a test notification"
  },
  "data": {
    "title_kurdish": "تاقیکردنەوەی پێشبڕکێ",
    "message_kurdish": "ئەمە تاقیکردنەوەیەکە",
    "title_arabic": "اختبار المنافسة",
    "message_arabic": "هذا إشعار اختبار",
    "type": "test",
    "priority": "normal"
  }
}
```

---

## Troubleshooting

### Common Issues

#### 1. Notifications Show English Instead of Selected Language

**Symptoms:**
- User has Kurdish/Arabic selected
- Notifications always show English content

**Solutions:**
- Check if `title_kurdish`/`title_arabic` fields are included in FCM data
- Verify language preference is saved in SharedPreferences
- Check language detection logic in `_getCurrentLanguage()`

#### 2. System Notifications Not Localized

**Symptoms:**
- App notifications show correct language
- System notification popup shows English

**Solutions:**
- Verify `_getLocalizedNotificationContent()` is called in `_showLocalNotification()`
- Check if language detection works in background handler
- Ensure FCM data includes language-specific fields

#### 3. Language Changes Don't Update Notifications

**Symptoms:**
- User changes language
- Existing notifications still show old language

**Solutions:**
- Call `refreshNotificationsWithLanguage()` when language changes
- Verify notification provider listens to language changes
- Check if notification data is properly recreated with new language

### Debug Commands

```dart
// Debug current language
print('Current language: ${await _getCurrentLanguage()}');

// Debug notification content
print('Notification data: ${message.data}');
print('Selected title: ${localizedContent['title']}');
print('Selected message: ${localizedContent['message']}');

// Debug stored notifications
final notifications = await _localStorage.getNotifications();
print('Stored notifications: $notifications');
```

### Logging

Enable detailed logging to track language selection:

```dart
print('FCM Language Selection:');
print('  Current language: $currentLanguage');
print('  Available Kurdish: ${message.data['title_kurdish'] != null}');
print('  Available Arabic: ${message.data['title_arabic'] != null}');
print('  Selected title: $title');
print('  Selected message: $messageBody');
```

---

## Best Practices

### Server-Side

1. **Always include English content** in `notification.title` and `notification.body`
2. **Include all language variants** in `data` fields when possible
3. **Test with missing language content** to ensure fallbacks work
4. **Use consistent field names** across all notifications

### Client-Side

1. **Handle missing language content gracefully** with fallbacks
2. **Store all language variants** for offline access
3. **Refresh notifications** when language changes
4. **Test all app states** (closed, background, foreground)

### Performance

1. **Cache language preference** to avoid repeated SharedPreferences calls
2. **Limit processed message IDs** to prevent memory issues
3. **Clean up old notifications** to prevent storage bloat

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-01-01 | Initial multi-language support |
| 1.1.0 | 2024-01-15 | Added Arabic language support |
| 1.2.0 | 2024-02-01 | Improved fallback logic and error handling |

---

## Support

For technical support or questions about the multi-language notification system:

1. Check this documentation first
2. Review the troubleshooting section
3. Enable debug logging
4. Contact the development team with specific error details

---

*Last updated: January 2024*
