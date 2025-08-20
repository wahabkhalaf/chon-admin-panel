# Flutter FCM Implementation Guide

## Overview
This guide provides step-by-step instructions to implement Firebase Cloud Messaging (FCM) in your Flutter app to receive notifications from the Laravel Admin panel.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Step 1: Flutter Package Installation](#step-1-flutter-package-installation)
- [Step 2: Firebase Project Setup](#step-2-firebase-project-setup)
- [Step 3: Flutter Firebase Configuration](#step-3-flutter-firebase-configuration)
- [Step 4: FCM Service Implementation](#step-4-fcm-service-implementation)
- [Step 5: Main App Integration](#step-5-main-app-integration)
- [Step 6: Laravel API Endpoint](#step-6-laravel-api-endpoint)
- [Step 7: Testing](#step-7-testing)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### Required Tools
- Flutter SDK (latest stable version)
- Android Studio / VS Code
- Firebase project (same as Laravel backend)
- Physical device for testing (emulators may not work properly)

### Firebase Project Requirements
- Your Laravel backend uses project: `chon-1114a`
- Service account credentials are configured
- FCM API is enabled

## Step 1: Flutter Package Installation

### 1.1 Update pubspec.yaml
Add these dependencies to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # Firebase packages
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
  
  # Local notifications
  flutter_local_notifications: ^16.3.0
  
  # HTTP requests
  http: ^1.1.0
  
  # Platform detection
  dart:io
  
  # JSON handling
  dart:convert

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.0
```

### 1.2 Install Packages
Run in your Flutter project directory:
```bash
flutter pub get
```

## Step 2: Firebase Project Setup

### 2.1 Download google-services.json (Android)
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: `chon-1114a`
3. Click on Android icon (🤖)
4. Register app with package name (e.g., `com.yourcompany.chonapp`)
5. Download `google-services.json`
6. Place in: `android/app/google-services.json`

### 2.2 Download GoogleService-Info.plist (iOS)
1. In Firebase Console, click on iOS icon (🍎)
2. Register app with Bundle ID (e.g., `com.yourcompany.chonapp`)
3. Download `GoogleService-Info.plist`
4. Place in: `ios/Runner/GoogleService-Info.plist`

### 2.3 Update Android Configuration

#### android/app/build.gradle
```gradle
android {
    defaultConfig {
        applicationId "com.yourcompany.chonapp"
        minSdkVersion 21  // Required for FCM
        targetSdkVersion 33
        versionCode 1
        versionName "1.0"
    }
}

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

#### android/build.gradle
```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

#### android/app/build.gradle (bottom)
```gradle
apply plugin: 'com.google.gms.google-services'
```

### 2.4 Update iOS Configuration

#### ios/Runner/Info.plist
Add these permissions:
```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
```

## Step 3: Flutter Firebase Configuration

### 3.1 Create firebase_options.dart
Run this command in your Flutter project:
```bash
flutterfire configure
```

This will create `lib/firebase_options.dart` automatically.

### 3.2 Alternative: Manual firebase_options.dart
If `flutterfire configure` doesn't work, create manually:

```dart
// lib/firebase_options.dart
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'your-android-api-key',
    appId: 'your-android-app-id',
    messagingSenderId: 'your-sender-id',
    projectId: 'chon-1114a',
    storageBucket: 'chon-1114a.appspot.com',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'your-ios-api-key',
    appId: 'your-ios-app-id',
    messagingSenderId: 'your-sender-id',
    projectId: 'chon-1114a',
    storageBucket: 'chon-1114a.appspot.com',
    iosClientId: 'your-ios-client-id',
    iosBundleId: 'com.yourcompany.chonapp',
  );

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'your-web-api-key',
    appId: 'your-web-app-id',
    messagingSenderId: 'your-sender-id',
    projectId: 'chon-1114a',
    storageBucket: 'chon-1114a.appspot.com',
  );
}
```

## Step 4: FCM Service Implementation

### 4.1 Create FCM Service Class
Create file: `lib/services/fcm_service.dart`

```dart
import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;

class FcmService {
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();
  
  // Your Laravel backend URL
  static const String _baseUrl = 'http://chonapp.net';
  static String? _userToken; // Store user authentication token
  
  // Initialize FCM service
  Future<void> initialize() async {
    await _initializeLocalNotifications();
    await _requestPermissions();
    await _getAndSendToken();
    await _setupMessageHandlers();
    await _setupTokenRefreshListener();
  }
  
  // Set user authentication token
  void setUserToken(String token) {
    _userToken = token;
  }
  
  // Initialize local notifications
  Future<void> _initializeLocalNotifications() async {
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
        
    const DarwinInitializationSettings initializationSettingsIOS =
        DarwinInitializationSettings();
        
    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsIOS,
    );
    
    await _localNotifications.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: _onNotificationTapped,
    );
  }
  
  // Request notification permissions
  Future<void> _requestPermissions() async {
    NotificationSettings settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );
    
    print('User granted permission: ${settings.authorizationStatus}');
  }
  
  // Get FCM token and send to Laravel backend
  Future<void> _getAndSendToken() async {
    try {
      String? token = await _messaging.getToken();
      
      if (token != null) {
        print('FCM Token: $token');
        await _sendTokenToServer(token);
      } else {
        print('Failed to get FCM token');
      }
    } catch (e) {
      print('Error getting FCM token: $e');
    }
  }
  
  // Send FCM token to Laravel backend
  Future<void> _sendTokenToServer(String token) async {
    if (_userToken == null) {
      print('User token not set, skipping FCM token sync');
      return;
    }
    
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/player/fcm-token'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $_userToken',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'fcm_token': token,
          'device_type': Platform.isAndroid ? 'android' : 'ios',
          'app_version': '1.0.0', // Optional: track app version
        }),
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('FCM token saved to server: ${data['message']}');
      } else {
        print('Failed to save FCM token: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      print('Error sending FCM token to server: $e');
    }
  }
  
  // Setup message handlers
  Future<void> _setupMessageHandlers() async {
    // Handle foreground messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    
    // Handle background messages
    FirebaseMessaging.onMessageOpenedApp.listen(_handleBackgroundMessage);
    
    // Handle initial message (app opened from notification)
    RemoteMessage? initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleInitialMessage(initialMessage);
    }
  }
  
  // Handle foreground messages
  void _handleForegroundMessage(RemoteMessage message) {
    print('Got a message whilst in the foreground!');
    print('Message data: ${message.data}');
    
    if (message.notification != null) {
      print('Message also contained a notification: ${message.notification}');
      _showLocalNotification(message);
    }
  }
  
  // Handle background messages
  void _handleBackgroundMessage(RemoteMessage message) {
    print('App opened from notification: ${message.data}');
    _handleNotificationNavigation(message.data);
  }
  
  // Handle initial message
  void _handleInitialMessage(RemoteMessage message) {
    print('App opened from initial notification: ${message.data}');
    _handleNotificationNavigation(message.data);
  }
  
  // Show local notification
  void _showLocalNotification(RemoteMessage message) {
    const AndroidNotificationDetails androidPlatformChannelSpecifics =
        AndroidNotificationDetails(
      'chon_notifications',
      'Chon Notifications',
      channelDescription: 'Notifications from Chon app',
      importance: Importance.max,
      priority: Priority.high,
      showWhen: true,
    );
    
    const NotificationDetails platformChannelSpecifics =
        NotificationDetails(android: androidPlatformChannelSpecifics);
    
    _localNotifications.show(
      message.hashCode,
      message.notification?.title ?? 'New Notification',
      message.notification?.body ?? 'You have a new message',
      platformChannelSpecifics,
      payload: jsonEncode(message.data),
    );
  }
  
  // Handle notification navigation
  void _handleNotificationNavigation(Map<String, dynamic> data) {
    // Navigate to specific screen based on notification data
    if (data['screen'] != null) {
      switch (data['screen']) {
        case 'competition':
          // Navigate to competition screen
          print('Navigate to competition: ${data['competition_id']}');
          break;
        case 'home':
          // Navigate to home screen
          print('Navigate to home screen');
          break;
        case 'profile':
          // Navigate to profile screen
          print('Navigate to profile screen');
          break;
        default:
          print('Unknown screen: ${data['screen']}');
      }
    }
    
    // Handle specific actions
    if (data['action'] != null) {
      print('Perform action: ${data['action']}');
    }
  }
  
  // Setup token refresh listener
  Future<void> _setupTokenRefreshListener() async {
    _messaging.onTokenRefresh.listen((newToken) {
      print('FCM Token refreshed: $newToken');
      _sendTokenToServer(newToken);
    });
  }
  
  // Subscribe to topic
  Future<void> subscribeToTopic(String topic) async {
    await _messaging.subscribeToTopic(topic);
    print('Subscribed to topic: $topic');
  }
  
  // Unsubscribe from topic
  Future<void> unsubscribeFromTopic(String topic) async {
    await _messaging.unsubscribeFromTopic(topic);
    print('Unsubscribed from topic: $topic');
  }
  
  // Get current FCM token
  Future<String?> getCurrentToken() async {
    return await _messaging.getToken();
  }
  
  // Handle notification tap
  void _onNotificationTapped(NotificationResponse response) {
    if (response.payload != null) {
      final data = jsonDecode(response.payload!);
      _handleNotificationNavigation(data);
    }
  }
}

// Global instance
final fcmService = FcmService();
```

## Step 5: Main App Integration

### 5.1 Update main.dart
```dart
// lib/main.dart
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'firebase_options.dart';
import 'services/fcm_service.dart';

// Background message handler (must be top-level function)
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  print('Handling a background message: ${message.messageId}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  
  // Set background message handler
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  
  // Initialize FCM service
  await fcmService.initialize();
  
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Chon App',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: HomeScreen(),
    );
  }
}
```

### 5.2 Update HomeScreen or Login Screen
```dart
// lib/screens/home_screen.dart or lib/screens/login_screen.dart
import 'package:flutter/material.dart';
import '../services/fcm_service.dart';

class HomeScreen extends StatefulWidget {
  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  @override
  void initState() {
    super.initState();
    
    // Set user token after successful login
    _setUserToken();
    
    // Subscribe to general notifications
    fcmService.subscribeToTopic('all_users');
  }
  
  void _setUserToken() {
    // Get your user authentication token from storage/shared preferences
    String? userToken = 'your_user_auth_token_here';
    
    if (userToken != null) {
      fcmService.setUserToken(userToken);
      print('User token set for FCM service');
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Chon App'),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Welcome to Chon!'),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: () async {
                // Test FCM token
                String? token = await fcmService.getCurrentToken();
                if (token != null) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('FCM Token: ${token.substring(0, 20)}...')),
                  );
                }
              },
              child: Text('Show FCM Token'),
            ),
          ],
        ),
      ),
    );
  }
}
```

## Step 6: Laravel API Endpoint

### 6.1 Create API Route
Add to `routes/web.php`:
```php
// FCM token management
Route::post('/api/player/fcm-token', [PlayerController::class, 'updateFcmToken']);
```

### 6.2 Create PlayerController Method
```php
// app/Http/Controllers/PlayerController.php
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string|max:1000',
        'device_type' => 'required|in:android,ios,web',
        'app_version' => 'nullable|string|max:20'
    ]);
    
    try {
        // Get authenticated player (adjust based on your auth system)
        $player = auth()->user(); // or however you get current player
        
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not authenticated'
            ], 401);
        }
        
        // Update FCM token
        $player->update([
            'fcm_token' => $request->fcm_token
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
            'data' => [
                'player_id' => $player->id,
                'fcm_token' => $request->fcm_token,
                'device_type' => $request->device_type
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update FCM token',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

## Step 7: Testing

### 7.1 Test FCM Connection
```bash
# In Laravel project directory
php artisan fcm:test --title="Test Notification" --message="Hello from FCM!"
```

### 7.2 Test from Flutter
1. Run your Flutter app on a physical device
2. Check console logs for FCM token
3. Verify token is sent to Laravel backend
4. Send test notification from Laravel admin

### 7.3 Test Notification Flow
1. **Foreground**: App open, notification should appear
2. **Background**: App closed, notification should appear in system tray
3. **Tapped**: Tapping notification should open app and navigate

## Troubleshooting

### Common Issues

#### 1. FCM Token Not Generated
- Check Firebase configuration files
- Verify package dependencies
- Ensure device has Google Play Services (Android)

#### 2. Notifications Not Received
- Check FCM token is saved in Laravel database
- Verify Firebase project configuration
- Check device internet connection

#### 3. Permission Denied
- Request notification permissions on app start
- Check device notification settings
- Verify app notification permissions

#### 4. Background Notifications Not Working
- Ensure background message handler is registered
- Check iOS background modes configuration
- Verify FCM service is properly initialized

### Debug Steps
1. **Check FCM Token**: Print token in console
2. **Verify Server Communication**: Check Laravel logs
3. **Test with Firebase Console**: Send test message directly
4. **Check Device Settings**: Ensure notifications are enabled

### Logs to Monitor
- Flutter console: FCM token generation
- Laravel logs: FCM token storage
- Firebase console: Message delivery status
- Device notification history

## Summary

After completing these steps, your Flutter app will:
- ✅ **Generate FCM tokens** automatically
- ✅ **Send tokens to Laravel** backend
- ✅ **Receive notifications** from Laravel admin
- ✅ **Handle foreground/background** messages
- ✅ **Navigate based on** notification data
- ✅ **Support all platforms** (Android, iOS, Web)

Your notification system is now fully integrated with direct FCM delivery!

---

**Last Updated**: August 20, 2025  
**Version**: 1.0  
**Maintainer**: Chon Development Team
