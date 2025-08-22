# Flutter App Update Implementation Guide

## 📱 Overview
This guide shows you how to integrate your Flutter app with the Laravel App Update API to automatically check for updates and show update popups to users.

## 🚀 Features
- **Automatic version checking** on app startup
- **Update popup dialogs** with release notes
- **Direct app store redirection** (iOS App Store / Google Play Store)
- **Force update support** (prevents app usage until updated)
- **Background update checking** with configurable intervals

## 📦 Required Dependencies

Add these to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # HTTP requests
  http: ^1.1.0
  
  # Local storage for caching
  shared_preferences: ^2.2.2
  
  # URL launcher for app store redirection
  url_launcher: ^6.2.1
  
  # Package info for current app version
  package_info_plus: ^4.2.0
  
  # Device info for platform detection
  device_info_plus: ^9.1.1
  
  # Local notifications (optional)
  flutter_local_notifications: ^16.3.0
```

## 🏗️ Project Structure

```
lib/
├── models/
│   └── app_update.dart
├── services/
│   └── app_update_service.dart
├── widgets/
│   ├── update_dialog.dart
│   └── force_update_dialog.dart
├── managers/
│   └── update_manager.dart
└── main.dart
```

## 📋 Step-by-Step Implementation

### 1. Create App Update Model

**`lib/models/app_update.dart`**

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

### 2. Create App Update Service

**`lib/services/app_update_service.dart`**

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:package_info_plus/package_info_plus.dart';
import 'package:device_info_plus/device_info_plus.dart';
import '../models/app_update.dart';

class AppUpdateService {
  static const String _baseUrl = 'https://chonapp.net/api';
  static const String _checkUpdateEndpoint = '/app-updates/check';
  
  // Cache duration (24 hours)
  static const Duration _cacheDuration = Duration(hours: 24);
  
  // HTTP client
  final http.Client _httpClient = http.Client();

  /// Check for app updates
  Future<AppUpdate?> checkForUpdates() async {
    try {
      // Get current app info
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

      // Prepare request data
      final requestData = {
        'platform': platform,
        'current_version': packageInfo.version,
        'current_build_number': int.parse(packageInfo.buildNumber),
        'app_version': packageInfo.version,
      };

      // Make API request
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

  /// Check if update check is needed (based on cache)
  Future<bool> shouldCheckForUpdates() async {
    final prefs = await SharedPreferences.getInstance();
    final lastCheck = prefs.getInt('last_update_check') ?? 0;
    final now = DateTime.now().millisecondsSinceEpoch;
    
    return (now - lastCheck) > _cacheDuration.inMilliseconds;
  }

  /// Cache the last update check time
  Future<void> cacheUpdateCheck() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('last_update_check', DateTime.now().millisecondsSinceEpoch);
  }

  /// Clear update check cache (force check)
  Future<void> clearUpdateCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('last_update_check');
  }
}
```

### 3. Create Update Dialog Widget

**`lib/widgets/update_dialog.dart`**

```dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/app_update.dart';

class UpdateDialog extends StatelessWidget {
  final AppUpdate update;
  final VoidCallback? onSkip;

  const UpdateDialog({
    Key? key,
    required this.update,
    this.onSkip,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      title: Row(
        children: [
          Icon(
            Icons.system_update,
            color: Colors.blue,
            size: 28,
          ),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Update Available! 🚀',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.blue[700],
              ),
            ),
          ),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'A new version is available:',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue[50],
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.blue[200]!),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.new_releases, color: Colors.blue[600], size: 20),
                    SizedBox(width: 8),
                    Text(
                      'Version ${update.latestVersion} (Build ${update.latestBuildNumber})',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue[700],
                      ),
                    ),
                  ],
                ),
                if (update.isMajorUpdate) ...[
                  SizedBox(height: 8),
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.orange[100],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      'MAJOR UPDATE',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: Colors.orange[800],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
          SizedBox(height: 16),
          if (update.releaseNotes.isNotEmpty) ...[
            Text(
              'What\'s New:',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            SizedBox(height: 8),
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey[300]!),
              ),
              child: Text(
                update.releaseNotes,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[700],
                ),
              ),
            ),
            SizedBox(height: 16),
          ],
          Text(
            update.updateMessage,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
      actions: [
        if (onSkip != null && !update.isForceUpdate)
          TextButton(
            onPressed: onSkip,
            child: Text(
              'Skip for now',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ),
        ElevatedButton.icon(
          onPressed: () => _launchAppStore(update.appStoreUrl),
          icon: Icon(Icons.download),
          label: Text('Update Now'),
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.blue[600],
            foregroundColor: Colors.white,
            padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          ),
        ),
      ],
    );
  }

  Future<void> _launchAppStore(String url) async {
    try {
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        throw 'Could not launch $url';
      }
    } catch (e) {
      print('Error launching app store: $e');
      // Fallback: show error dialog
      // You can implement a fallback dialog here
    }
  }
}
```

### 4. Create Force Update Dialog Widget

**`lib/widgets/force_update_dialog.dart`**

```dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/app_update.dart';

class ForceUpdateDialog extends StatelessWidget {
  final AppUpdate update;

  const ForceUpdateDialog({
    Key? key,
    required this.update,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async => false, // Prevent back button
      child: AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: Row(
          children: [
            Icon(
              Icons.warning_amber_rounded,
              color: Colors.orange,
              size: 28,
            ),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'Update Required! ⚠️',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.orange[700],
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.orange[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.orange[200]!),
              ),
              child: Row(
                children: [
                  Icon(Icons.info_outline, color: Colors.orange[600], size: 20),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'This update is required to continue using the app.',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.orange[700],
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            SizedBox(height: 16),
            Text(
              'New Version: ${update.latestVersion} (Build ${update.latestBuildNumber})',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            SizedBox(height: 8),
            if (update.releaseNotes.isNotEmpty) ...[
              Text(
                'What\'s New:',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 8),
              Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey[300]!),
                ),
                child: Text(
                  update.releaseNotes,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[700],
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          ElevatedButton.icon(
            onPressed: () => _launchAppStore(update.appStoreUrl),
            icon: Icon(Icons.download),
            label: Text('Update Now'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange[600],
              foregroundColor: Colors.white,
              padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _launchAppStore(String url) async {
    try {
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        throw 'Could not launch $url';
      }
    } catch (e) {
      print('Error launching app store: $e');
    }
  }
}
```

### 5. Create Update Manager

**`lib/managers/update_manager.dart`**

```dart
import 'package:flutter/material.dart';
import '../models/app_update.dart';
import '../services/app_update_service.dart';
import '../widgets/update_dialog.dart';
import '../widgets/force_update_dialog.dart';

class UpdateManager {
  final AppUpdateService _updateService = AppUpdateService();
  final BuildContext context;
  
  UpdateManager(this.context);

  /// Check for updates and show dialog if needed
  Future<void> checkForUpdates({bool forceCheck = false}) async {
    try {
      // Check if we should check for updates
      if (!forceCheck && !await _updateService.shouldCheckForUpdates()) {
        return;
      }

      // Check for updates
      final update = await _updateService.checkForUpdates();
      
      if (update != null) {
        // Cache the check time
        await _updateService.cacheUpdateCheck();
        
        // Show appropriate dialog
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

  /// Show regular update dialog
  Future<void> _showUpdateDialog(AppUpdate update) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => UpdateDialog(
        update: update,
        onSkip: () {
          Navigator.of(context).pop();
        },
      ),
    );
  }

  /// Show force update dialog
  Future<void> _showForceUpdateDialog(AppUpdate update) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => ForceUpdateDialog(update: update),
    );
  }

  /// Force check for updates (ignores cache)
  Future<void> forceCheckForUpdates() async {
    await checkForUpdates(forceCheck: true);
  }

  /// Clear update cache
  Future<void> clearCache() async {
    await _updateService.clearUpdateCache();
  }
}
```

### 6. Integrate with Main App

**`lib/main.dart`**

```dart
import 'package:flutter/material.dart';
import 'managers/update_manager.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Chon App',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: MyHomePage(),
    );
  }
}

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
          // Manual update check button
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
            Text(
              'Welcome to Chon App!',
              style: Theme.of(context).textTheme.headline4,
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: () => _updateManager.forceCheckForUpdates(),
              child: Text('Check for Updates'),
            ),
            SizedBox(height: 10),
            ElevatedButton(
              onPressed: () => _updateManager.clearCache(),
              child: Text('Clear Cache'),
            ),
          ],
        ),
      ),
    );
  }
}
```

## 🔧 Configuration

### 1. Platform Configuration

**Android (`android/app/src/main/AndroidManifest.xml`)**
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.REQUEST_INSTALL_PACKAGES" />
```

**iOS (`ios/Runner/Info.plist`)**
```xml
<key>LSApplicationQueriesSchemes</key>
<array>
    <string>itms-apps</string>
</array>
```

### 2. App Store URLs

Make sure your app store URLs are correct in the admin panel:

- **Android**: `https://play.google.com/store/apps/details?id=YOUR_PACKAGE_NAME`
- **iOS**: `https://apps.apple.com/app/idYOUR_APP_ID`

## 🚀 Usage Examples

### 1. Check for Updates on App Start
```dart
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

### 2. Manual Update Check
```dart
ElevatedButton(
  onPressed: () => _updateManager.forceCheckForUpdates(),
  child: Text('Check for Updates'),
)
```

### 3. Clear Update Cache
```dart
ElevatedButton(
  onPressed: () => _updateManager.clearCache(),
  child: Text('Clear Cache'),
)
```

## 📱 How It Works

### 1. **App Startup Flow:**
```
App Starts → Check Cache → API Call → Show Dialog → User Action
```

### 2. **Update Check Process:**
- App gets current version from `package_info_plus`
- Sends request to `/api/app-updates/check`
- Compares versions and shows appropriate dialog
- Caches check time to avoid excessive API calls

### 3. **Dialog Types:**
- **Regular Update**: User can skip or update
- **Force Update**: User must update to continue

### 4. **App Store Redirection:**
- Opens appropriate store (Google Play / App Store)
- Uses `url_launcher` package
- Handles errors gracefully

## 🎯 Testing

### 1. **Test with Old Version:**
```dart
// In your app, temporarily change version to 1.0.8
// This will trigger update popup
```

### 2. **Test Force Update:**
```dart
// In admin panel, set a version as "Force Update"
// App will show force update dialog
```

### 3. **Test Cache:**
```dart
// Check updates, then check again immediately
// Second check should be skipped due to cache
```

## 🔍 Troubleshooting

### Common Issues:

1. **No Update Dialog:**
   - Check API endpoint URL
   - Verify app version format
   - Check network connectivity

2. **App Store Not Opening:**
   - Verify app store URLs in admin panel
   - Check platform permissions
   - Test with `url_launcher` directly

3. **Cache Issues:**
   - Use `clearCache()` method
   - Check shared preferences
   - Verify cache duration

## 📚 API Reference

### Check for Updates
```
POST /api/app-updates/check
Content-Type: application/json

{
  "platform": "android|ios",
  "current_version": "1.0.8",
  "current_build_number": 8,
  "app_version": "1.0.8"
}
```

### Response Format
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
    "app_store_url": "https://...",
    "release_notes": "New features...",
    "released_at": "2025-08-21T17:02:46.000000Z",
    "update_message": "A new version is available..."
  }
}
```

## 🎉 That's It!

Your Flutter app is now fully integrated with the app update system! Users will automatically see update popups when new versions are available, and they can easily navigate to the app store to download updates.

The system handles:
- ✅ Automatic update checking
- ✅ Smart caching (24-hour intervals)
- ✅ Force update enforcement
- ✅ Platform-specific app store redirection
- ✅ Beautiful update dialogs
- ✅ Release notes display
- ✅ Error handling and fallbacks

Happy coding! 🚀
