# Notifications & FCM integration (developer guide)

This document describes how the Chon app integrates with Firebase Cloud Messaging (FCM), how the client obtains and sends the FCM token to the backend (`/api/v1/player/fcm-token`), and how notification messages are handled in the app.

This guide purposely excludes server-side delete/read notification endpoints — it focuses on token registration, delivery, and client-side handling.

Contents
- Overview
# Notifications & FCM integration — developer reference

This document explains how to call notifications from the mobile app, how the client obtains and registers the FCM token with the backend, and the exact calls you should use in the Flutter code.

Primary files

- `lib/services/fcm_service.dart` — main implementation (singleton `FcmService`, final `fcmService` instance).
- `lib/services/local_notification_storage.dart` — where notifications are persisted locally for the app's notifications list.

Quick summary

- After the user authenticates (or at app startup if you prefer), call:

```dart
fcmService.setUserToken(<BEARER_JWT>);
await fcmService.initialize();
```

- `initialize()` obtains the device FCM token, registers it with the backend (`POST /api/v1/player/fcm-token`) and sets up all message handlers.

How to call — exact code you can copy

1) Typical flow (after login)

```dart
import 'package:your_app/services/fcm_service.dart';

// after login
final jwt = authService.token; // your auth provider
fcmService.setUserToken(jwt);
await fcmService.initialize();

// optional: in-app callback for immediate UI updates
fcmService.setNotificationCallback((notificationData) {
  // e.g. refresh notifications list or increase badge count
});
```

2) Inspect current state (debug)

```dart
debugPrint('FCM initialized: ${fcmService.isInitialized}');
debugPrint('FCM token: ${fcmService.currentToken}');
```

3) Force re-send token after account switch

```dart
// setUserToken resets the sent-flag; if a token is present the service will POST it again
fcmService.setUserToken(newJwt);
```

What the service does (concise)

- Requests notification permissions
- Creates the Android notification channel `chon_notifications`
- Calls `FirebaseMessaging.instance.getToken()` and stores the token in `_currentFcmToken`
- Calls `_sendTokenToServer(token)` which posts the token to `/api/v1/player/fcm-token`
- Registers handlers for:
  - `FirebaseMessaging.onMessage` (foreground)
  - `FirebaseMessaging.onBackgroundMessage` (background; top-level handler)
  - `FirebaseMessaging.onMessageOpenedApp` (tap)
  - `FirebaseMessaging.onTokenRefresh` (re-send token on refresh)

Server API (exact)

- Endpoint (client POST):

  `POST <ADMIN_URL or https://chonapp.net>/api/v1/player/fcm-token`

- Headers:

  - `Content-Type: application/json`
  - `Authorization: Bearer <user_jwt>` (required by the client implementation)
  - `Accept: application/json`

- Body (JSON):

```json
{
  "fcm_token": "<token_string>",
  "device_type": "android" | "ios"
}
```

Behavior notes

- `_sendTokenToServer` will not attempt a POST until `setUserToken()` has provided a bearer token.
- The service uses an internal `_isTokenSent` flag to avoid redundant requests. The flag is reset when the user changes or the FCM token refreshes.

Message handling summary

- Foreground: `_handleForegroundMessage` — stores the message locally, notifies the app via callback, and displays a local system notification so a sound is played.
- Background: `_firebaseMessagingBackgroundHandler` (top-level, annotated with `@pragma('vm:entry-point')`) — stores and displays the notification.
- Terminated: `getInitialMessage()` is checked during `initialize()` to handle the case where the app was launched by a notification tap.
- Taps: `onDidReceiveNotificationResponse` -> `_onNotificationTapped` -> `_handleNotificationNavigation` (basic screen routing by `data['screen']`).

Local persistence & deduplication

- Messages are saved to `LocalNotificationStorage` with localized title/message and the raw `data` map.
- Duplicate messages are guarded by `_processedMessageIds` (keeps a sliding set of recent message IDs to avoid processing the same message multiple times).

How to verify FCM and token registration

1. During debugging, print the token and initialized state:

```dart
debugPrint('FCM initialized: ${fcmService.isInitialized}');
debugPrint('FCM token: ${fcmService.currentToken}');
```

2. Confirm the server receives the POST to `/api/v1/player/fcm-token` from the device and returns 200.

3. Send a test push from Firebase Console to the printed token.

Troubleshooting checklist

- No token printed: ensure `initialize()` was called and the Google services files (`google-services.json` / `GoogleService-Info.plist`) are present.
- Token not sent: ensure `setUserToken(jwt)` was called (the client requires a JWT to POST the token).
- No sound/notification on Android: check that notification channel `chon_notifications` was created successfully and that the app has permission.

Suggested coding improvements (I can implement these):

- Add a public `bool get isTokenRegistered => _isTokenSent;` to `FcmService` so the UI or tests can know if the token was registered successfully.
- Add richer debug logs when `_sendTokenToServer` fails, including response body to help backend debugging.

If you want, I will implement the improvements above and update this document with the new getter and log examples.

```

- Verify server received token by querying the player record or watching backend logs for POSTs to `/api/v1/player/fcm-token`.

- You can emulate a message by sending an FCM downstream message from Firebase console to the token and checking that the app receives it.

## Troubleshooting

- Token not sent: ensure `setUserToken()` is called with a valid bearer token before `initialize()` or ensure `setUserToken(...)` is called and that `_currentFcmToken` is available — the service will attempt to re-send the token when the token or user changes.
- No notifications on Android: confirm the notification channel exists (`chon_notifications`) and that `flutter_local_notifications` initialization succeeded.
- No background handler: make sure the background handler `_firebaseMessagingBackgroundHandler` remains a top-level function and is not removed by obfuscation. The function is annotated with `@pragma('vm:entry-point')` to ensure it is preserved.

## Code references

- Main implementation: `lib/services/fcm_service.dart` (singleton `FcmService`)
- Background handler: top of `lib/services/fcm_service.dart` — `_firebaseMessagingBackgroundHandler(RemoteMessage message)`
- Local notification storage helper: `lib/services/local_notification_storage.dart`

## Example usage (summary)

1. After login:

```dart
fcmService.setUserToken(authService.token);
await fcmService.initialize();
fcmService.setNotificationCallback((n) => updateNotificationsList(n));
```

2. To inspect during debug:

```dart
print('FCM initialized: ${fcmService.isInitialized}');
print('FCM token: ${fcmService.currentToken}');
```

3. To trigger a manual send of the current token again (for example after switching account):

```dart
// setUserToken resets _isTokenSent and will re-send if currentToken is present
fcmService.setUserToken(newAuthToken);
```

---

If you'd like, I can:

- Add a small public getter to `FcmService` to expose whether the token has been successfully registered (`isTokenRegistered`).
- Add logs that output the POST response body when `_sendTokenToServer` fails (currently it silently sets `_isTokenSent = false`).

Tell me which additions you want and I will update the code and documentation accordingly.
