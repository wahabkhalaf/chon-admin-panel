# Flutter Local Notifications with Firebase Guide

## Overview
Complete guide to implement local-only notification system in Flutter using Firebase Cloud Messaging (FCM) for delivery and local storage for management.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Step 1: Flutter Setup](#step-1-flutter-setup)
- [Step 2: Firebase Configuration](#step-2-firebase-configuration)
- [Step 3: Local Storage Implementation](#step-3-local-storage-implementation)
- [Step 4: FCM Service](#step-4-fcm-service)
- [Step 5: Notification UI](#step-5-notification-ui)
- [Step 6: Laravel Integration](#step-6-laravel-integration)
- [Testing](#testing)

## Prerequisites
- Flutter SDK (latest stable)
- Firebase project (chon-1114a)
- Physical device for testing
- Laravel backend with FCM service

## Step 1: Flutter Setup

### 1.1 Add Dependencies
```yaml
# pubspec.yaml
dependencies:
  flutter:
    sdk: flutter
  
  # Firebase
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
  
  # Local notifications
  flutter_local_notifications: ^16.3.0
  
  # Local storage
  shared_preferences: ^2.2.2
  
  # HTTP requests
  http: ^1.1.0
  
  # Platform detection
  dart:io
  
  # JSON handling
  dart:convert
```

### 1.2 Install Packages
```bash
flutter pub get
```

## Step 2: Firebase Configuration

### 2.1 Download Configuration Files
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: `chon-1114a`
3. Add Android app with package: `com.yourcompany.chonapp`
4. Download `google-services.json` → `android/app/`
5. Add iOS app with bundle: `com.yourcompany.chonapp`
6. Download `GoogleService-Info.plist` → `ios/Runner/`

### 2.2 Android Configuration
```gradle
// android/app/build.gradle
android {
    defaultConfig {
        applicationId "com.yourcompany.chonapp"
        minSdkVersion 21
        targetSdkVersion 33
    }
}

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-messaging'
}

apply plugin: 'com.google.gms.google-services'
```

```gradle
// android/build.gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

### 2.3 iOS Configuration
```xml
<!-- ios/Runner/Info.plist -->
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### 2.4 Firebase Options
Create `lib/firebase_options.dart`:
```dart
import 'package:firebase_core/firebase_core.dart';

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    return const FirebaseOptions(
      apiKey: 'YOUR_API_KEY',
      appId: 'YOUR_APP_ID',
      messagingSenderId: 'YOUR_SENDER_ID',
      projectId: 'chon-1114a',
      storageBucket: 'chon-1114a.appspot.com',
    );
  }
}
```

## Step 3: Local Storage Implementation

### 3.1 Create Local Storage Service
```dart
// lib/services/local_notification_storage.dart
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class LocalNotificationStorage {
  static const String _notificationsKey = 'local_notifications';
  static const String _unreadCountKey = 'unread_count';
  
  // Store notification
  Future<void> storeNotification(Map<String, dynamic> notification) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> existing = prefs.getStringList(_notificationsKey) ?? [];
    
    final newNotification = {
      ...notification,
      'id': DateTime.now().millisecondsSinceEpoch.toString(),
      'timestamp': DateTime.now().toIso8601String(),
      'is_read': false,
    };
    
    existing.insert(0, jsonEncode(newNotification));
    
    if (existing.length > 100) {
      existing = existing.take(100).toList();
    }
    
    await prefs.setStringList(_notificationsKey, existing);
    await _updateUnreadCount();
  }
  
  // Get notifications
  Future<List<Map<String, dynamic>>> getNotifications() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> notifications = prefs.getStringList(_notificationsKey) ?? [];
    return notifications.map((n) => jsonDecode(n) as Map<String, dynamic>).toList();
  }
  
  // Mark as read
  Future<void> markAsRead(String notificationId) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> notifications = prefs.getStringList(_notificationsKey) ?? [];
    
    for (int i = 0; i < notifications.length; i++) {
      final notification = jsonDecode(notifications[i]);
      if (notification['id'] == notificationId) {
        notification['is_read'] = true;
        notifications[i] = jsonEncode(notification);
        break;
      }
    }
    
    await prefs.setStringList(_notificationsKey, notifications);
    await _updateUnreadCount();
  }
  
  // Mark all as read
  Future<void> markAllAsRead() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> notifications = prefs.getStringList(_notificationsKey) ?? [];
    
    for (int i = 0; i < notifications.length; i++) {
      final notification = jsonDecode(notifications[i]);
      notification['is_read'] = true;
      notifications[i] = jsonEncode(notification);
    }
    
    await prefs.setStringList(_notificationsKey, notifications);
    await _updateUnreadCount();
  }
  
  // Get unread count
  Future<int> getUnreadCount() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_unreadCountKey) ?? 0;
  }
  
  // Update unread count
  Future<void> _updateUnreadCount() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> notifications = prefs.getStringList(_notificationsKey) ?? [];
    
    int unreadCount = 0;
    for (String notification in notifications) {
      final data = jsonDecode(notification);
      if (data['is_read'] == false) {
        unreadCount++;
      }
    }
    
    await prefs.setInt(_unreadCountKey, unreadCount);
  }
  
  // Clear all
  Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_notificationsKey);
    await prefs.remove(_unreadCountKey);
  }
}
```

## Step 4: FCM Service

### 4.1 Create FCM Service
```dart
// lib/services/fcm_service.dart
import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'local_notification_storage.dart';

class FcmService {
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();
  final LocalNotificationStorage _localStorage = LocalNotificationStorage();
  
  static const String _baseUrl = 'http://chonapp.net';
  static String? _userToken;
  
  // Initialize
  Future<void> initialize() async {
    await _initializeLocalNotifications();
    await _requestPermissions();
    await _getAndSendToken();
    await _setupMessageHandlers();
    await _setupTokenRefreshListener();
  }
  
  // Set user token
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
  
  // Request permissions
  Future<void> _requestPermissions() async {
    NotificationSettings settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );
    
    print('User granted permission: ${settings.authorizationStatus}');
  }
  
  // Get and send token
  Future<void> _getAndSendToken() async {
    try {
      String? token = await _messaging.getToken();
      
      if (token != null) {
        print('FCM Token: $token');
        await _sendTokenToServer(token);
      }
    } catch (e) {
      print('Error getting FCM token: $e');
    }
  }
  
  // Send token to server
  Future<void> _sendTokenToServer(String token) async {
    if (_userToken == null) return;
    
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
        }),
      );
      
      if (response.statusCode == 200) {
        print('FCM token saved to server');
      }
    } catch (e) {
      print('Error sending FCM token: $e');
    }
  }
  
  // Setup message handlers
  Future<void> _setupMessageHandlers() async {
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleBackgroundMessage);
    
    RemoteMessage? initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleInitialMessage(initialMessage);
    }
  }
  
  // Handle foreground message
  void _handleForegroundMessage(RemoteMessage message) async {
    print('Foreground message: ${message.data}');
    
    // Store locally
    await _localStorage.storeNotification({
      'title': message.notification?.title ?? 'New Notification',
      'title_kurdish': message.data['title_kurdish'] ?? '',
      'message': message.notification?.body ?? 'You have a new message',
      'message_kurdish': message.data['message_kurdish'] ?? '',
      'type': message.data['type'] ?? 'info',
      'priority': message.data['priority'] ?? 'normal',
      'data': message.data,
      'fcm_message_id': message.messageId,
    });
    
    // Show local notification
    _showLocalNotification(message);
  }
  
  // Handle background message
  void _handleBackgroundMessage(RemoteMessage message) {
    print('Background message: ${message.data}');
    _handleNotificationNavigation(message.data);
  }
  
  // Handle initial message
  void _handleInitialMessage(RemoteMessage message) {
    print('Initial message: ${message.data}');
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
  
  // Handle navigation
  void _handleNotificationNavigation(Map<String, dynamic> data) {
    if (data['screen'] != null) {
      switch (data['screen']) {
        case 'competition':
          print('Navigate to competition: ${data['competition_id']}');
          break;
        case 'home':
          print('Navigate to home');
          break;
        default:
          print('Unknown screen: ${data['screen']}');
      }
    }
  }
  
  // Setup token refresh
  Future<void> _setupTokenRefreshListener() async {
    _messaging.onTokenRefresh.listen((newToken) {
      print('Token refreshed: $newToken');
      _sendTokenToServer(newToken);
    });
  }
  
  // Subscribe to topic
  Future<void> subscribeToTopic(String topic) async {
    await _messaging.subscribeToTopic(topic);
  }
  
  // Get local notifications
  Future<List<Map<String, dynamic>>> getLocalNotifications() async {
    return await _localStorage.getNotifications();
  }
  
  // Mark as read
  Future<void> markAsRead(String notificationId) async {
    await _localStorage.markAsRead(notificationId);
  }
  
  // Mark all as read
  Future<void> markAllAsRead() async {
    await _localStorage.markAllAsRead();
  }
  
  // Get unread count
  Future<int> getUnreadCount() async {
    return await _localStorage.getUnreadCount();
  }
  
  // Handle notification tap
  void _onNotificationTapped(NotificationResponse response) {
    if (response.payload != null) {
      final data = jsonDecode(response.payload!);
      _handleNotificationNavigation(data);
    }
  }
}

final fcmService = FcmService();
```

## Step 5: Notification UI

### 5.1 Create Notification Screen
```dart
// lib/screens/notifications_screen.dart
import 'package:flutter/material.dart';
import '../services/local_notification_storage.dart';

class NotificationsScreen extends StatefulWidget {
  @override
  _NotificationsScreenState createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final LocalNotificationStorage _storage = LocalNotificationStorage();
  List<Map<String, dynamic>> _notifications = [];
  int _unreadCount = 0;
  
  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }
  
  Future<void> _loadNotifications() async {
    final notifications = await _storage.getNotifications();
    final unreadCount = await _storage.getUnreadCount();
    
    setState(() {
      _notifications = notifications;
      _unreadCount = unreadCount;
    });
  }
  
  Future<void> _markAsRead(String notificationId) async {
    await _storage.markAsRead(notificationId);
    await _loadNotifications();
  }
  
  Future<void> _markAllAsRead() async {
    await _storage.markAllAsRead();
    await _loadNotifications();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Notifications'),
        actions: [
          if (_unreadCount > 0)
            TextButton(
              onPressed: _markAllAsRead,
              child: Text('Mark All Read'),
            ),
        ],
      ),
      body: _notifications.isEmpty
          ? Center(child: Text('No notifications yet'))
          : ListView.builder(
              itemCount: _notifications.length,
              itemBuilder: (context, index) {
                final notification = _notifications[index];
                final isRead = notification['is_read'] ?? false;
                
                return ListTile(
                  leading: Icon(
                    _getNotificationIcon(notification['type']),
                    color: isRead ? Colors.grey : Colors.blue,
                  ),
                  title: Text(
                    notification['title'] ?? 'No Title',
                    style: TextStyle(
                      fontWeight: isRead ? FontWeight.normal : FontWeight.bold,
                    ),
                  ),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(notification['message'] ?? 'No Message'),
                      Text(
                        _formatTimestamp(notification['timestamp']),
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    ],
                  ),
                  trailing: isRead
                      ? null
                      : Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: Colors.red,
                            shape: BoxShape.circle,
                          ),
                        ),
                  onTap: () {
                    if (!isRead) {
                      _markAsRead(notification['id']);
                    }
                    _handleNotificationTap(notification);
                  },
                );
              },
            ),
    );
  }
  
  IconData _getNotificationIcon(String? type) {
    switch (type) {
      case 'info':
        return Icons.info;
      case 'warning':
        return Icons.warning;
      case 'success':
        return Icons.check_circle;
      case 'error':
        return Icons.error;
      default:
        return Icons.notifications;
    }
  }
  
  String _formatTimestamp(String? timestamp) {
    if (timestamp == null) return '';
    try {
      final date = DateTime.parse(timestamp);
      final now = DateTime.now();
      final difference = now.difference(date);
      
      if (difference.inDays > 0) {
        return '${difference.inDays}d ago';
      } else if (difference.inHours > 0) {
        return '${difference.inHours}h ago';
      } else if (difference.inMinutes > 0) {
        return '${difference.inMinutes}m ago';
      } else {
        return 'Just now';
      }
    } catch (e) {
      return '';
    }
  }
  
  void _handleNotificationTap(Map<String, dynamic> notification) {
    if (notification['data'] != null) {
      final data = notification['data'];
      if (data['screen'] != null) {
        print('Navigate to: ${data['screen']}');
      }
    }
  }
}
```

## Step 6: Laravel Integration

### 6.1 Add API Route
```php
// routes/web.php
Route::post('/api/player/fcm-token', [PlayerController::class, 'updateFcmToken']);
```

### 6.2 Create Controller Method
```php
// app/Http/Controllers/PlayerController.php
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string|max:1000',
        'device_type' => 'required|in:android,ios,web'
    ]);
    
    try {
        $player = auth()->user(); // Get current player
        
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not authenticated'
            ], 401);
        }
        
        $player->update(['fcm_token' => $request->fcm_token]);
        
        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update FCM token'
        ], 500);
    }
}
```

## Step 7: Main App Integration

### 7.1 Update main.dart
```dart
// lib/main.dart
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'firebase_options.dart';
import 'services/fcm_service.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  print('Background message: ${message.messageId}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  
  await fcmService.initialize();
  
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Chon App',
      home: HomeScreen(),
    );
  }
}
```

### 7.2 Update Home Screen
```dart
// lib/screens/home_screen.dart
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
    
    // Set user token after login
    _setUserToken();
    
    // Subscribe to notifications
    fcmService.subscribeToTopic('all_users');
  }
  
  void _setUserToken() {
    String? userToken = 'your_user_auth_token_here';
    if (userToken != null) {
      fcmService.setUserToken(userToken);
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Chon App'),
        actions: [
          IconButton(
            icon: Icon(Icons.notifications),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => NotificationsScreen(),
                ),
              );
            },
          ),
        ],
      ),
      body: Center(
        child: Text('Welcome to Chon!'),
      ),
    );
  }
}
```

## Testing

### 1. Test FCM Connection
```bash
# In Laravel project
php artisan fcm:test --title="Test" --message="Hello FCM!"
```

### 2. Test Local Storage
- Run Flutter app
- Check console for FCM token
- Verify token sent to Laravel
- Send test notification
- Check local storage

### 3. Test Notification Flow
- Foreground: App open, notification appears
- Background: App closed, notification in system tray
- Tapped: Opens app and navigates

## Summary

This implementation provides:
- ✅ **Local notification storage** - No additional APIs needed
- ✅ **FCM delivery** - Direct from Laravel to Flutter
- ✅ **Read/unread tracking** - Local management
- ✅ **Notification history** - Stored locally
- ✅ **Badge counts** - Local calculation
- ✅ **Offline support** - Works without internet

**Only API needed**: `POST /api/player/fcm-token` for storing FCM token.

---

**Last Updated**: August 20, 2025  
**Version**: 1.0  
**Maintainer**: Chon Development Team
