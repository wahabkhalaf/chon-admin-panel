# Flutter App Update API Reference

## 📱 Overview
Complete API reference and Flutter integration guide for the App Update system. This document provides all the details you need to integrate your Flutter app with the versioning API.

## 🚀 Quick Start

### 1. **Add Dependencies to `pubspec.yaml`:**
```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.2.2
  url_launcher: ^6.2.1
  package_info_plus: ^4.2.0
  device_info_plus: ^9.1.1
```

### 2. **Install Dependencies:**
```bash
flutter pub get
```

### 3. **Copy the Implementation Files:**
- `lib/models/app_update.dart`
- `lib/services/app_update_service.dart`
- `lib/widgets/update_dialog.dart`
- `lib/widgets/force_update_dialog.dart`
- `lib/managers/update_manager.dart`

### 4. **Integrate in Your Main App:**
```dart
// In your main.dart or home page
late UpdateManager _updateManager;

@override
void initState() {
  super.initState();
  _updateManager = UpdateManager(context);
  
  // Check for updates when app starts
  WidgetsBinding.instance.addPostFrameCallback((_) {
    _updateManager.checkForUpdates();
  });
}
```

## 📚 API Endpoints

### **Public Endpoint (No Authentication Required)**

#### Check for Updates
```
POST /api/app-updates/check
```

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "platform": "android",
  "current_version": "1.0.8",
  "current_build_number": 8,
  "app_version": "1.0.8"
}
```

**Parameters:**
- `platform` (required): `"android"` or `"ios"`
- `current_version` (required): Current app version (e.g., "1.0.8")
- `current_build_number` (required): Current build number (e.g., 8)
- `app_version` (required): Same as current_version

**Response (Update Available):**
```json
{
  "success": true,
  "update_available": true,
  "message": "Update available",
  "data": {
    "latest_version": "2.0.0",
    "latest_build_number": 20,
    "current_version": "1.0.8",
    "current_build_number": 8,
    "is_force_update": false,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "Major update with new design and features",
    "released_at": "2025-08-21T17:02:46.000000Z",
    "update_message": "A new version is available with exciting new features!"
  }
}
```

**Response (No Update Available):**
```json
{
  "success": true,
  "update_available": false,
  "message": "App is up to date",
  "data": null
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "platform": ["The platform field is required."]
  }
}
```

### **Admin Endpoints (Require Authentication)**

#### Get All App Versions
```
GET /api/app-updates
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "platform": "android",
      "version": "2.0.0",
      "build_number": 20,
      "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
      "release_notes": "Major update with new design and features",
      "is_force_update": false,
      "is_active": true,
      "released_at": "2025-08-21T17:02:46.000000Z",
      "created_at": "2025-08-21T17:02:46.000000Z",
      "updated_at": "2025-08-21T17:02:46.000000Z"
    }
  ]
}
```

#### Create New App Version
```
POST /api/app-updates
```

**Request Body:**
```json
{
  "platform": "android",
  "version": "2.1.0",
  "build_number": 25,
  "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
  "release_notes": "Bug fixes and performance improvements",
  "is_force_update": false,
  "is_active": true,
  "released_at": "2025-08-21T17:02:46.000000Z"
}
```

#### Update App Version
```
PUT /api/app-updates/{id}
```

#### Delete App Version
```
DELETE /api/app-updates/{id}
```

#### Get Statistics
```
GET /api/app-updates/statistics
```

## 🔧 Flutter Implementation Details

### **1. App Update Model**

```dart
class AppUpdate {
  final String latestVersion;
  final int latestBuildNumber;
  final String currentVersion;
  final int currentBuildNumber;
  final bool isForceUpdate;
  final String appStoreUrl;
  final String releaseNotes;
  final DateTime releasedAt;
  final String updateMessage;

  AppUpdate({
    required this.latestVersion,
    required this.latestBuildNumber,
    required this.currentVersion,
    required this.currentBuildNumber,
    required this.isForceUpdate,
    required this.appStoreUrl,
    required this.releaseNotes,
    required this.releasedAt,
    required this.updateMessage,
  });

  factory AppUpdate.fromJson(Map<String, dynamic> json) {
    return AppUpdate(
      latestVersion: json['latest_version'] ?? '',
      latestBuildNumber: json['latest_build_number'] ?? 0,
      currentVersion: json['current_version'] ?? '',
      currentBuildNumber: json['current_build_number'] ?? 0,
      isForceUpdate: json['is_force_update'] ?? false,
      appStoreUrl: json['app_store_url'] ?? '',
      releaseNotes: json['release_notes'] ?? '',
      releasedAt: DateTime.parse(json['released_at'] ?? DateTime.now().toIso8601String()),
      updateMessage: json['update_message'] ?? '',
    );
  }

  bool get hasUpdate => latestBuildNumber > currentBuildNumber;
  bool get isMajorUpdate => _isMajorVersionUpdate();
  
  bool _isMajorVersionUpdate() {
    final current = currentVersion.split('.');
    final latest = latestVersion.split('.');
    
    if (current.length >= 2 && latest.length >= 2) {
      return int.parse(latest[0]) > int.parse(current[0]) || 
             int.parse(latest[1]) > int.parse(current[1]);
    }
    return false;
  }
}
```

### **2. App Update Service**

```dart
class AppUpdateService {
  static const String _baseUrl = 'https://chonapp.net/api';
  static const String _checkUpdateEndpoint = '/app-updates/check';
  static const Duration _cacheDuration = Duration(hours: 24);
  
  final http.Client _httpClient = http.Client();

  Future<AppUpdate?> checkForUpdates() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();
      final deviceInfo = DeviceInfoPlugin();
      
      String platform;
      if (Platform.isAndroid) {
        platform = 'android';
      } else if (Platform.isIOS) {
        platform = 'ios';
      } else {
        throw Exception('Unsupported platform');
      }

      final requestData = {
        'platform': platform,
        'current_version': packageInfo.version,
        'current_build_number': int.parse(packageInfo.buildNumber),
        'app_version': packageInfo.version,
      };

      final response = await _httpClient.post(
        Uri.parse('$_baseUrl$_checkUpdateEndpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(requestData),
      );

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        
        if (responseData['success'] == true && responseData['update_available'] == true) {
          return AppUpdate.fromJson(responseData['data']);
        }
      }
      
      return null;
    } catch (e) {
      print('Error checking for updates: $e');
      return null;
    }
  }

  Future<bool> shouldCheckForUpdates() async {
    final prefs = await SharedPreferences.getInstance();
    final lastCheck = prefs.getInt('last_update_check') ?? 0;
    final now = DateTime.now().millisecondsSinceEpoch;
    
    return (now - lastCheck) > _cacheDuration.inMilliseconds;
  }

  Future<void> cacheUpdateCheck() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('last_update_check', DateTime.now().millisecondsSinceEpoch);
  }

  Future<void> clearUpdateCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('last_update_check');
  }
}
```

### **3. Update Manager**

```dart
class UpdateManager {
  final AppUpdateService _updateService = AppUpdateService();
  final BuildContext context;
  
  UpdateManager(this.context);

  Future<void> checkForUpdates({bool forceCheck = false}) async {
    try {
      if (!forceCheck && !await _updateService.shouldCheckForUpdates()) {
        return;
      }

      final update = await _updateService.checkForUpdates();
      
      if (update != null) {
        await _updateService.cacheUpdateCheck();
        
        if (update.isForceUpdate) {
          await _showForceUpdateDialog(update);
        } else {
          await _showUpdateDialog(update);
        }
      }
    } catch (e) {
      print('Error in update manager: $e');
    }
  }

  Future<void> _showUpdateDialog(AppUpdate update) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => UpdateDialog(
        update: update,
        onSkip: () => Navigator.of(context).pop(),
      ),
    );
  }

  Future<void> _showForceUpdateDialog(AppUpdate update) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => ForceUpdateDialog(update: update),
    );
  }

  Future<void> forceCheckForUpdates() async {
    await checkForUpdates(forceCheck: true);
  }

  Future<void> clearCache() async {
    await _updateService.clearUpdateCache();
  }
}
```

## 📱 Platform Configuration

### **Android Configuration**

**`android/app/src/main/AndroidManifest.xml`:**
```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.REQUEST_INSTALL_PACKAGES" />
    
    <application
        android:label="Chon App"
        android:name="${applicationName}"
        android:icon="@mipmap/ic_launcher">
        <!-- ... rest of your manifest ... -->
    </application>
</manifest>
```

### **iOS Configuration**

**`ios/Runner/Info.plist`:**
```xml
<key>LSApplicationQueriesSchemes</key>
<array>
    <string>itms-apps</string>
</array>
```

## 🎯 Usage Examples

### **1. Automatic Update Check on App Start**
```dart
class MyHomePage extends StatefulWidget {
  @override
  _MyHomePageState createState() => _MyHomePageState();
}

class _MyHomePageState extends State<MyHomePage> {
  late UpdateManager _updateManager;

  @override
  void initState() {
    super.initState();
    _updateManager = UpdateManager(context);
    
    // Check for updates when app starts
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _updateManager.checkForUpdates();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Chon App'),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh),
            onPressed: () => _updateManager.forceCheckForUpdates(),
            tooltip: 'Check for updates',
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Welcome to Chon App!'),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: () => _updateManager.forceCheckForUpdates(),
              child: Text('Check for Updates'),
            ),
            SizedBox(height: 10),
            ElevatedButton(
              onPressed: () => _updateManager.clearCache(),
              child: Text('Clear Update Cache'),
            ),
          ],
        ),
      ),
    );
  }
}
```

### **2. Custom Update Check in Settings**
```dart
class SettingsPage extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Settings')),
      body: ListView(
        children: [
          ListTile(
            leading: Icon(Icons.system_update),
            title: Text('Check for Updates'),
            subtitle: Text('Check if a new version is available'),
            onTap: () {
              final updateManager = UpdateManager(context);
              updateManager.forceCheckForUpdates();
            },
          ),
          ListTile(
            leading: Icon(Icons.clear_all),
            title: Text('Clear Update Cache'),
            subtitle: Text('Force check for updates on next app start'),
            onTap: () {
              final updateManager = UpdateManager(context);
              updateManager.clearCache();
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Update cache cleared')),
              );
            },
          ),
        ],
      ),
    );
  }
}
```

### **3. Background Update Check**
```dart
class BackgroundUpdateChecker {
  static Timer? _timer;
  
  static void startPeriodicCheck(BuildContext context) {
    _timer?.cancel();
    _timer = Timer.periodic(Duration(hours: 6), (timer) {
      final updateManager = UpdateManager(context);
      updateManager.checkForUpdates();
    });
  }
  
  static void stopPeriodicCheck() {
    _timer?.cancel();
    _timer = null;
  }
}

// In your main app
@override
void initState() {
  super.initState();
  
  // Start periodic update checks
  BackgroundUpdateChecker.startPeriodicCheck(context);
}

@override
void dispose() {
  BackgroundUpdateChecker.stopPeriodicCheck();
  super.dispose();
}
```

## 🧪 Testing

### **1. Test with Different App Versions**

**Temporarily change your app version in `pubspec.yaml`:**
```yaml
version: 1.0.8+8  # This will trigger update popup
```

**Then test the API:**
```bash
curl -X POST https://chonapp.net/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "android",
    "current_version": "1.0.8",
    "current_build_number": 8,
    "app_version": "1.0.8"
  }'
```

### **2. Test Force Update**

**In your admin panel:**
1. Go to App Versions
2. Edit a version
3. Check "Force Update"
4. Save changes
5. Test with older app version

### **3. Test Cache Behavior**

```dart
// First check
await _updateManager.checkForUpdates();

// Immediate second check (should be skipped due to cache)
await _updateManager.checkForUpdates();

// Force check (ignores cache)
await _updateManager.forceCheckForUpdates();

// Clear cache and check again
await _updateManager.clearCache();
await _updateManager.checkForUpdates();
```

## 🔍 Troubleshooting

### **Common Issues and Solutions**

#### 1. **No Update Dialog Appears**
**Possible Causes:**
- API endpoint URL is incorrect
- App version format doesn't match
- Network connectivity issues
- Cache is preventing checks

**Solutions:**
```dart
// Check API endpoint
print('API URL: ${AppUpdateService._baseUrl}${AppUpdateService._checkUpdateEndpoint}');

// Force check ignoring cache
await _updateManager.forceCheckForUpdates();

// Clear cache
await _updateManager.clearCache();
```

#### 2. **App Store Not Opening**
**Possible Causes:**
- Incorrect app store URLs in admin panel
- Missing platform permissions
- Testing on simulator instead of device

**Solutions:**
```dart
// Verify app store URL
print('App Store URL: ${update.appStoreUrl}');

// Test URL launcher directly
import 'package:url_launcher/url_launcher.dart';

final uri = Uri.parse('https://play.google.com/store/apps/details?id=com.chon.app');
if (await canLaunchUrl(uri)) {
  await launchUrl(uri, mode: LaunchMode.externalApplication);
}
```

#### 3. **API Returns Errors**
**Possible Causes:**
- Missing required fields
- Invalid platform value
- Server errors

**Solutions:**
```dart
// Add error logging
try {
  final update = await _updateService.checkForUpdates();
  if (update != null) {
    print('Update found: ${update.latestVersion}');
  } else {
    print('No update available');
  }
} catch (e) {
  print('API Error: $e');
  // Show user-friendly error message
}
```

#### 4. **Cache Issues**
**Possible Causes:**
- Shared preferences not working
- Cache duration too long
- Cache not being cleared properly

**Solutions:**
```dart
// Check cache status
final prefs = await SharedPreferences.getInstance();
final lastCheck = prefs.getInt('last_update_check') ?? 0;
final now = DateTime.now().millisecondsSinceEpoch;
final timeSinceLastCheck = now - lastCheck;

print('Time since last check: ${Duration(milliseconds: timeSinceLastCheck)}');

// Clear cache manually
await _updateManager.clearCache();
```

## 📊 Monitoring and Analytics

### **1. Add Update Check Logging**
```dart
class AppUpdateService {
  // ... existing code ...

  Future<AppUpdate?> checkForUpdates() async {
    try {
      print('🔍 Checking for updates...');
      
      final packageInfo = await PackageInfo.fromPlatform();
      print('📱 Current version: ${packageInfo.version} (${packageInfo.buildNumber})');
      
      // ... API call logic ...
      
      if (update != null) {
        print('✅ Update available: ${update.latestVersion} (${update.latestBuildNumber})');
        print('📝 Release notes: ${update.releaseNotes}');
        print('🔗 App store URL: ${update.appStoreUrl}');
      } else {
        print('✅ App is up to date');
      }
      
      return update;
    } catch (e) {
      print('❌ Error checking for updates: $e');
      return null;
    }
  }
}
```

### **2. Track Update Metrics**
```dart
class UpdateMetrics {
  static Future<void> trackUpdateCheck() async {
    final prefs = await SharedPreferences.getInstance();
    final checkCount = prefs.getInt('update_check_count') ?? 0;
    await prefs.setInt('update_check_count', checkCount + 1);
    
    final lastCheck = DateTime.now();
    await prefs.setString('last_update_check_time', lastCheck.toIso8601String());
    
    print('📊 Update check #${checkCount + 1} at ${lastCheck}');
  }
  
  static Future<void> trackUpdateDialogShown(String version) async {
    final prefs = await SharedPreferences.getInstance();
    final dialogCount = prefs.getInt('update_dialog_shown_count') ?? 0;
    await prefs.setInt('update_dialog_shown_count', dialogCount + 1);
    
    print('📱 Update dialog shown for version $version (${dialogCount + 1} total)');
  }
  
  static Future<void> trackAppStoreOpened(String version) async {
    final prefs = await SharedPreferences.getInstance();
    final storeOpenCount = prefs.getInt('app_store_opened_count') ?? 0;
    await prefs.setInt('app_store_opened_count', storeOpenCount + 1);
    
    print('🛒 App store opened for version $version (${storeOpenCount + 1} total)');
  }
}
```

## 🚀 Advanced Features

### **1. Custom Update Intervals**
```dart
class UpdateManager {
  // ... existing code ...
  
  Duration _updateCheckInterval = Duration(hours: 24);
  
  void setUpdateCheckInterval(Duration interval) {
    _updateCheckInterval = interval;
  }
  
  Future<bool> shouldCheckForUpdates() async {
    final prefs = await SharedPreferences.getInstance();
    final lastCheck = prefs.getInt('last_update_check') ?? 0;
    final now = DateTime.now().millisecondsSinceEpoch;
    
    return (now - lastCheck) > _updateCheckInterval.inMilliseconds;
  }
}

// Usage
final updateManager = UpdateManager(context);
updateManager.setUpdateCheckInterval(Duration(hours: 6)); // Check every 6 hours
```

### **2. Update Notifications**
```dart
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class UpdateNotificationService {
  static final FlutterLocalNotificationsPlugin _notifications = FlutterLocalNotificationsPlugin();
  
  static Future<void> initialize() async {
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings();
    
    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );
    
    await _notifications.initialize(initSettings);
  }
  
  static Future<void> showUpdateNotification(AppUpdate update) async {
    const androidDetails = AndroidNotificationDetails(
      'update_channel',
      'App Updates',
      channelDescription: 'Notifications for app updates',
      importance: Importance.high,
      priority: Priority.high,
    );
    
    const iosDetails = DarwinNotificationDetails();
    
    const details = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );
    
    await _notifications.show(
      0,
      'Update Available! 🚀',
      'New version ${update.latestVersion} is ready',
      details,
      payload: update.appStoreUrl,
    );
  }
}

// Initialize in main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await UpdateNotificationService.initialize();
  runApp(MyApp());
}

// Show notification when update is found
if (update != null) {
  await UpdateNotificationService.showUpdateNotification(update);
}
```

### **3. Offline Update Caching**
```dart
class OfflineUpdateCache {
  static const String _cacheKey = 'offline_update_cache';
  static const Duration _cacheValidity = Duration(days: 7);
  
  static Future<void> cacheUpdate(AppUpdate update) async {
    final prefs = await SharedPreferences.getInstance();
    final cacheData = {
      'update': update.toJson(),
      'cached_at': DateTime.now().toIso8601String(),
    };
    
    await prefs.setString(_cacheKey, jsonEncode(cacheData));
  }
  
  static Future<AppUpdate?> getCachedUpdate() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cacheString = prefs.getString(_cacheKey);
      
      if (cacheString != null) {
        final cacheData = jsonDecode(cacheString);
        final cachedAt = DateTime.parse(cacheData['cached_at']);
        
        if (DateTime.now().difference(cachedAt) < _cacheValidity) {
          return AppUpdate.fromJson(cacheData['update']);
        }
      }
      
      return null;
    } catch (e) {
      print('Error reading cached update: $e');
      return null;
    }
  }
  
  static Future<void> clearCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_cacheKey);
  }
}

// Usage in update service
Future<AppUpdate?> checkForUpdates() async {
  try {
    // Try online check first
    final onlineUpdate = await _checkOnline();
    if (onlineUpdate != null) {
      await OfflineUpdateCache.cacheUpdate(onlineUpdate);
      return onlineUpdate;
    }
  } catch (e) {
    print('Online check failed: $e');
  }
  
  // Fallback to offline cache
  return await OfflineUpdateCache.getCachedUpdate();
}
```

## 📋 Complete Implementation Checklist

### **✅ Dependencies Added**
- [ ] `http: ^1.1.0`
- [ ] `shared_preferences: ^2.2.2`
- [ ] `url_launcher: ^6.2.1`
- [ ] `package_info_plus: ^4.2.0`
- [ ] `device_info_plus: ^9.1.1`

### **✅ Files Created**
- [ ] `lib/models/app_update.dart`
- [ ] `lib/services/app_update_service.dart`
- [ ] `lib/widgets/update_dialog.dart`
- [ ] `lib/widgets/force_update_dialog.dart`
- [ ] `lib/managers/update_manager.dart`

### **✅ Platform Configuration**
- [ ] Android manifest permissions
- [ ] iOS Info.plist schemes
- [ ] App store URLs configured

### **✅ Integration Complete**
- [ ] Update manager initialized
- [ ] Automatic checks on app start
- [ ] Manual update check buttons
- [ ] Cache management
- [ ] Error handling

### **✅ Testing Complete**
- [ ] API endpoint working
- [ ] Update dialogs showing
- [ ] App store redirection working
- [ ] Force update enforcement
- [ ] Cache behavior correct

## 🎉 Success!

Your Flutter app is now fully integrated with the app update system! Users will:

1. **Automatically see update popups** when new versions are available
2. **Get beautiful dialogs** with release notes and update information
3. **Be redirected to app stores** with one tap
4. **Experience force updates** when critical versions are required
5. **Benefit from smart caching** to avoid excessive API calls

The system handles all edge cases and provides a smooth user experience for app updates. Happy coding! 🚀
