# Flutter App Update Implementation Guide

## 📱 Overview
This guide explains how to implement automatic app updates in your Flutter app using the Laravel App Update API. Your app will automatically check for updates and guide users to download the latest version.

## 🚀 Features
- **Automatic update checks** on app startup
- **Beautiful update dialogs** with release notes
- **Force update support** for critical versions
- **Direct store redirects** to App Store/Google Play
- **Platform-specific** update handling (iOS/Android)
- **Offline fallback** when API is unavailable

---

## 📦 Required Dependencies

### **1. Add to `pubspec.yaml`:**
```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  shared_preferences: ^2.2.2
  url_launcher: ^6.2.1
  package_info_plus: ^4.2.0
  device_info_plus: ^9.1.1
  flutter_local_notifications: ^16.3.0
```

### **2. Install dependencies:**
```bash
flutter pub get
```

---

## 🔧 API Integration

### **1. API Endpoint:**
```
POST https://chonapp.net/api/app-updates/check
```

### **2. Request Format:**
```json
{
  "platform": "android",
  "current_version": "1.0.8",
  "current_build_number": 8,
  "app_version": "1.0.8"
}
```

### **3. Response Format:**
```json
{
  "success": true,
  "update_available": true,
  "message": "Update available",
  "data": {
    "latest_version": "1.1.0",
    "latest_build_number": 10,
    "current_version": "1.0.8",
    "current_build_number": 8,
    "is_force_update": false,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "New features and bug fixes",
    "released_at": "2024-01-15T10:00:00Z",
    "update_message": "A new version is available with exciting new features!"
  }
}
```

---

## 📱 Implementation

### **1. Create App Update Service (`lib/services/app_update_service.dart`):**

```dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:package_info_plus/package_info_plus.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppUpdateService {
  static const String _baseUrl = 'https://chonapp.net';
  static const String _apiEndpoint = '/api/app-updates/check';
  
  // Cache key for last update check
  static const String _lastCheckKey = 'last_update_check';
  static const String _lastVersionKey = 'last_checked_version';
  
  // Minimum interval between update checks (24 hours)
  static const Duration _minCheckInterval = Duration(hours: 24);

  /// Check for app updates
  static Future<AppUpdateResult?> checkForUpdates({
    bool forceCheck = false,
    bool showDialog = true,
  }) async {
    try {
      // Check if we should skip this check
      if (!forceCheck && await _shouldSkipCheck()) {
        return null;
      }

      // Get current app info
      final packageInfo = await PackageInfo.fromPlatform();
      final deviceInfo = await _getDeviceInfo();
      
      // Prepare request data
      final requestData = {
        'platform': deviceInfo['platform'],
        'current_version': packageInfo.version,
        'current_build_number': int.tryParse(packageInfo.buildNumber) ?? 0,
        'app_version': packageInfo.version,
      };

      // Make API request
      final response = await http.post(
        Uri.parse('$_baseUrl$_apiEndpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(requestData),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          // Cache the check
          await _cacheUpdateCheck(packageInfo.version);
          
          if (data['update_available'] == true) {
            final updateInfo = AppUpdateInfo.fromJson(data['data']);
            
            if (showDialog) {
              await _showUpdateDialog(updateInfo);
            }
            
            return AppUpdateResult(
              updateAvailable: true,
              updateInfo: updateInfo,
            );
          } else {
            return AppUpdateResult(
              updateAvailable: false,
              updateInfo: null,
            );
          }
        } else {
          throw Exception('API returned success: false');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error checking for updates: $e');
      return null;
    }
  }

  /// Get device platform info
  static Future<Map<String, String>> _getDeviceInfo() async {
    final deviceInfo = DeviceInfoPlugin();
    
    if (Platform.isAndroid) {
      final androidInfo = await deviceInfo.androidInfo;
      return {'platform': 'android'};
    } else if (Platform.isIOS) {
      final iosInfo = await deviceInfo.iosInfo;
      return {'platform': 'ios'};
    } else {
      return {'platform': 'unknown'};
    }
  }

  /// Check if we should skip this update check
  static Future<bool> _shouldSkipCheck() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastCheck = prefs.getString(_lastCheckKey);
      final lastVersion = prefs.getString(_lastVersionKey);
      
      if (lastCheck == null || lastVersion == null) {
        return false;
      }

      final lastCheckTime = DateTime.parse(lastCheck);
      final packageInfo = await PackageInfo.fromPlatform();
      
      // Skip if:
      // 1. Not enough time has passed since last check
      // 2. App version hasn't changed
      return DateTime.now().difference(lastCheckTime) < _minCheckInterval ||
             lastVersion == packageInfo.version;
    } catch (e) {
      return false;
    }
  }

  /// Cache the update check
  static Future<void> _cacheUpdateCheck(String version) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastCheckKey, DateTime.now().toIso8601String());
      await prefs.setString(_lastVersionKey, version);
    } catch (e) {
      print('Error caching update check: $e');
    }
  }

  /// Show update dialog
  static Future<void> _showUpdateDialog(AppUpdateInfo updateInfo) async {
    // This will be implemented in the UI section
    // For now, just print the update info
    print('Update available: ${updateInfo.latestVersion}');
  }
}

/// App update result
class AppUpdateResult {
  final bool updateAvailable;
  final AppUpdateInfo? updateInfo;

  AppUpdateResult({
    required this.updateAvailable,
    this.updateInfo,
  });
}

/// App update information
class AppUpdateInfo {
  final String latestVersion;
  final int latestBuildNumber;
  final String currentVersion;
  final int currentBuildNumber;
  final bool isForceUpdate;
  final String? appStoreUrl;
  final String? releaseNotes;
  final DateTime? releasedAt;
  final String updateMessage;

  AppUpdateInfo({
    required this.latestVersion,
    required this.latestBuildNumber,
    required this.currentVersion,
    required this.currentBuildNumber,
    required this.isForceUpdate,
    this.appStoreUrl,
    this.releaseNotes,
    this.releasedAt,
    required this.updateMessage,
  });

  factory AppUpdateInfo.fromJson(Map<String, dynamic> json) {
    return AppUpdateInfo(
      latestVersion: json['latest_version'] ?? '',
      latestBuildNumber: json['latest_build_number'] ?? 0,
      currentVersion: json['current_version'] ?? '',
      currentBuildNumber: json['current_build_number'] ?? 0,
      isForceUpdate: json['is_force_update'] ?? false,
      appStoreUrl: json['app_store_url'],
      releaseNotes: json['release_notes'],
      releasedAt: json['released_at'] != null 
          ? DateTime.tryParse(json['released_at']) 
          : null,
      updateMessage: json['update_message'] ?? '',
    );
  }

  /// Check if this is a major version update
  bool get isMajorUpdate {
    final current = currentVersion.split('.');
    final latest = latestVersion.split('.');
    
    if (current.length >= 1 && latest.length >= 1) {
      return int.tryParse(latest[0]) ?? 0 > int.tryParse(current[0]) ?? 0;
    }
    return false;
  }

  /// Check if this is a minor version update
  bool get isMinorUpdate {
    final current = currentVersion.split('.');
    final latest = latestVersion.split('.');
    
    if (current.length >= 2 && latest.length >= 2) {
      return int.tryParse(latest[1]) ?? 0 > int.tryParse(current[1]) ?? 0;
    }
    return false;
  }
}
```

### **2. Create Update Dialog UI (`lib/widgets/app_update_dialog.dart`):**

```dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/app_update_service.dart';

class AppUpdateDialog extends StatelessWidget {
  final AppUpdateInfo updateInfo;
  final bool isForceUpdate;

  const AppUpdateDialog({
    Key? key,
    required this.updateInfo,
    this.isForceUpdate = false,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async => !isForceUpdate, // Prevent closing if force update
      child: AlertDialog(
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
                'Update Available',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Version info
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(Icons.info_outline, color: Colors.blue),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'New version ${updateInfo.latestVersion} is available!',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: Colors.blue.shade700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            
            SizedBox(height: 16),
            
            // Release notes
            if (updateInfo.releaseNotes?.isNotEmpty == true) ...[
              Text(
                'What\'s New:',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              SizedBox(height: 8),
              Text(
                updateInfo.releaseNotes!,
                style: TextStyle(
                  color: Colors.grey.shade700,
                  height: 1.4,
                ),
              ),
              SizedBox(height: 16),
            ],
            
            // Update message
            Text(
              updateInfo.updateMessage,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade600,
                fontStyle: FontStyle.italic,
              ),
            ),
            
            if (isForceUpdate) ...[
              SizedBox(height: 16),
              Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.warning, color: Colors.orange),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'This update is required to continue using the app.',
                        style: TextStyle(
                          color: Colors.orange.shade700,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (!isForceUpdate) ...[
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text(
                'Later',
                style: TextStyle(color: Colors.grey),
              ),
            ),
          ],
          ElevatedButton.icon(
            onPressed: () => _openAppStore(context),
            icon: Icon(Icons.download),
            label: Text('Update Now'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              foregroundColor: Colors.white,
              padding: EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }

  /// Open app store for update
  Future<void> _openAppStore(BuildContext context) async {
    try {
      final url = updateInfo.appStoreUrl;
      if (url != null && await canLaunchUrl(Uri.parse(url))) {
        await launchUrl(
          Uri.parse(url),
          mode: LaunchMode.externalApplication,
        );
        
        // Close dialog
        if (context.mounted) {
          Navigator.of(context).pop();
        }
      } else {
        // Show error if can't open store
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Could not open app store'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error opening app store: $e');
    }
  }
}
```

### **3. Integrate with Main App (`lib/main.dart`):**

```dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/app_update_service.dart';
import 'widgets/app_update_dialog.dart';

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
        useMaterial3: true,
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
  @override
  void initState() {
    super.initState();
    
    // Check for updates when app starts
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkForUpdates();
    });
  }

  /// Check for app updates
  Future<void> _checkForUpdates() async {
    try {
      final result = await AppUpdateService.checkForUpdates(
        forceCheck: false,
        showDialog: false, // We'll show it manually
      );

      if (result?.updateAvailable == true && result?.updateInfo != null) {
        // Show update dialog
        if (mounted) {
          showDialog(
            context: context,
            barrierDismissible: !result!.updateInfo!.isForceUpdate,
            builder: (context) => AppUpdateDialog(
              updateInfo: result.updateInfo!,
              isForceUpdate: result.updateInfo!.isForceUpdate,
            ),
          );
        }
      }
    } catch (e) {
      print('Error checking for updates: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Chon App'),
        actions: [
          IconButton(
            icon: Icon(Icons.system_update),
            onPressed: _checkForUpdates,
            tooltip: 'Check for updates',
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.phone_android,
              size: 100,
              color: Colors.blue,
            ),
            SizedBox(height: 20),
            Text(
              'Welcome to Chon App!',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 10),
            Text(
              'Your app is up to date',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            SizedBox(height: 30),
            ElevatedButton.icon(
              onPressed: _checkForUpdates,
              icon: Icon(Icons.refresh),
              label: Text('Check for Updates'),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 🔧 Configuration

### **1. Android Configuration (`android/app/src/main/AndroidManifest.xml`):**
```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <!-- Add internet permission -->
    <uses-permission android:name="android.permission.INTERNET" />
    
    <application
        android:label="Chon App"
        android:name="${applicationName}"
        android:icon="@mipmap/ic_launcher">
        <!-- ... rest of your manifest ... -->
    </application>
</manifest>
```

### **2. iOS Configuration (`ios/Runner/Info.plist`):**
```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLName</key>
        <string>com.chon.app</string>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>chonapp</string>
        </array>
    </dict>
</array>
```

---

## 🧪 Testing

### **1. Test with Different Versions:**
```dart
// Test with old version
final result = await AppUpdateService.checkForUpdates(
  forceCheck: true,
  showDialog: true,
);
```

### **2. Test Force Update:**
```dart
// In your admin panel, set is_force_update: true
// Then test with an older version
```

### **3. Test Offline:**
```dart
// Disconnect internet and test
// Should handle gracefully
```

---

## 🚀 Deployment

### **1. Build Release:**
```bash
# Android
flutter build apk --release

# iOS
flutter build ios --release
```

### **2. Update Version in `pubspec.yaml`:**
```yaml
version: 1.1.0+10  # version+build_number
```

### **3. Create New App Version in Admin Panel:**
- Go to `https://chonapp.net/admin`
- Navigate to "App Versions"
- Create new version with:
  - Platform: android/ios
  - Version: 1.1.0
  - Build Number: 10
  - Release Notes: "New features and bug fixes"
  - App Store URL: Your store link

---

## 📱 Features Summary

✅ **Automatic Update Checks** - On app startup  
✅ **Smart Caching** - Avoids unnecessary API calls  
✅ **Beautiful UI** - Material Design update dialogs  
✅ **Force Updates** - Critical version enforcement  
✅ **Store Integration** - Direct app store redirects  
✅ **Offline Handling** - Graceful error handling  
✅ **Platform Detection** - iOS/Android specific logic  
✅ **Version Comparison** - Smart update detection  

---

## 🔍 Troubleshooting

### **Common Issues:**

1. **API Returns "No updates available"**
   - Check if app versions are seeded in database
   - Verify build numbers are correct
   - Check if versions are marked as active

2. **Update Dialog Not Showing**
   - Ensure `showDialog: true` is set
   - Check if app is mounted when calling
   - Verify API response format

3. **Store Link Not Opening**
   - Check `app_store_url` in database
   - Verify URL format is correct
   - Test on real device (not simulator)

4. **Build Number Issues**
   - Ensure `build_number` is integer in database
   - Check `pubspec.yaml` version format
   - Verify version comparison logic

---

## 📞 Support

If you encounter any issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify API endpoint: `https://chonapp.net/api/app-updates/check`
3. Test with Postman or curl
4. Check admin panel for app version configuration

---

**🎉 Your Flutter app is now ready for automatic updates!**
