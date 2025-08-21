# Flutter App Update API Reference

## 📱 API Endpoint
```
POST https://chonapp.net/api/app-updates/check
```

## 🔧 Request Format

### **Headers:**
```
Content-Type: application/json
Accept: application/json
```

### **Request Body:**
```json
{
  "platform": "android",
  "current_version": "1.0.0",
  "current_build_number": 1,
  "app_version": "1.0.0"
}
```

### **Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `platform` | string | ✅ | Platform: `"android"` or `"ios"` |
| `current_version` | string | ✅ | Current app version (e.g., "1.0.0") |
| `current_build_number` | integer | ✅ | Current build number (e.g., 1) |
| `app_version` | string | ✅ | Flutter app version (e.g., "1.0.0") |

---

## 📥 Response Format

### **Success Response Structure:**
```json
{
  "success": true,
  "update_available": boolean,
  "message": "string",
  "data": object | null
}
```

---

## 🎯 Response Examples

### **1. Update Available (Regular Update)**

**Request:**
```json
{
  "platform": "android",
  "current_version": "0.8.0",
  "current_build_number": 0,
  "app_version": "0.8.0"
}
```

**Response:**
```json
{
  "success": true,
  "update_available": true,
  "message": "Update available",
  "data": {
    "latest_version": "1.2.0",
    "latest_build_number": 3,
    "current_version": "0.8.0",
    "current_build_number": 0,
    "is_force_update": false,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "New features and UI improvements",
    "released_at": "2025-07-21T14:43:24.000000Z",
    "update_message": "A new version is available with exciting new features!"
  }
}
```

### **2. Update Available (Force Update)**

**Request:**
```json
{
  "platform": "android",
  "current_version": "1.2.0",
  "current_build_number": 3,
  "app_version": "1.2.0"
}
```

**Response:**
```json
{
  "success": true,
  "update_available": true,
  "message": "Update available",
  "data": {
    "latest_version": "1.3.0",
    "latest_build_number": 4,
    "current_version": "1.2.0",
    "current_build_number": 3,
    "is_force_update": true,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "Critical security update - force update required",
    "released_at": "2025-08-21T16:45:00.000000Z",
    "update_message": "This update is required to continue using the app."
  }
}
```

### **3. No Update Available**

**Request:**
```json
{
  "platform": "android",
  "current_version": "1.2.0",
  "current_build_number": 3,
  "app_version": "1.2.0"
}
```

**Response:**
```json
{
  "success": true,
  "update_available": false,
  "message": "App is up to date",
  "data": null
}
```

### **4. iOS Platform Example**

**Request:**
```json
{
  "platform": "ios",
  "current_version": "0.8.0",
  "current_build_number": 0,
  "app_version": "0.8.0"
}
```

**Response:**
```json
{
  "success": true,
  "update_available": true,
  "message": "Update available",
  "data": {
    "latest_version": "1.2.0",
    "latest_build_number": 3,
    "current_version": "0.8.0",
    "current_build_number": 0,
    "is_force_update": false,
    "app_store_url": "https://apps.apple.com/app/id123456789",
    "release_notes": "New features and UI improvements",
    "released_at": "2025-07-21T14:43:24.000000Z",
    "update_message": "A new version is available with exciting new features!"
  }
}
```

---

## 📋 Response Data Fields

### **Main Response Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `true` for successful requests |
| `update_available` | boolean | Whether an update is available |
| `message` | string | Human-readable message about the update status |
| `data` | object \| null | Update information (null if no update) |

### **Data Object Fields (when update_available: true):**
| Field | Type | Description |
|-------|------|-------------|
| `latest_version` | string | Latest available version (e.g., "1.2.0") |
| `latest_build_number` | integer | Latest build number (e.g., 3) |
| `current_version` | string | Current app version (e.g., "0.8.0") |
| `current_build_number` | integer | Current build number (e.g., 0) |
| `is_force_update` | boolean | Whether this is a forced update |
| `app_store_url` | string | Direct link to app store |
| `release_notes` | string | What's new in this version |
| `released_at` | string | ISO 8601 timestamp of release |
| `update_message` | string | User-friendly update message |

---

## 🔍 Error Responses

### **Validation Error (400):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "platform": ["The platform field is required."],
    "current_version": ["The current version field is required."]
  }
}
```

### **Server Error (500):**
```json
{
  "success": false,
  "message": "Internal server error",
  "error": "Something went wrong"
}
```

---

## 🚀 Flutter Integration Examples

### **1. Basic Update Check:**
```dart
final response = await http.post(
  Uri.parse('https://chonapp.net/api/app-updates/check'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'platform': 'android',
    'current_version': '1.0.0',
    'current_build_number': 1,
    'app_version': '1.0.0',
  }),
);

if (response.statusCode == 200) {
  final data = jsonDecode(response.body);
  if (data['success'] && data['update_available']) {
    // Show update dialog
    final updateInfo = data['data'];
    print('Update available: ${updateInfo['latest_version']}');
  }
}
```

### **2. Handle Force Updates:**
```dart
if (data['data']['is_force_update']) {
  // Show force update dialog (user cannot dismiss)
  showForceUpdateDialog(context, data['data']);
} else {
  // Show regular update dialog (user can dismiss)
  showUpdateDialog(context, data['data']);
}
```

### **3. Open App Store:**
```dart
final appStoreUrl = data['data']['app_store_url'];
if (appStoreUrl != null) {
  await launchUrl(Uri.parse(appStoreUrl));
}
```

---

## 📱 Platform-Specific URLs

### **Android:**
- **Google Play Store:** `https://play.google.com/store/apps/details?id=com.chon.app`
- **Format:** `https://play.google.com/store/apps/details?id=PACKAGE_NAME`

### **iOS:**
- **App Store:** `https://apps.apple.com/app/id123456789`
- **Format:** `https://apps.apple.com/app/idAPP_ID`

---

## 🔧 Testing

### **Test with Different Versions:**
```bash
# Test with old version (should trigger update)
curl -X POST https://chonapp.net/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{"platform":"android","current_version":"0.9.0","current_build_number":0,"app_version":"0.9.0"}'

# Test with current version (should return no update)
curl -X POST https://chonapp.net/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{"platform":"android","current_version":"1.2.0","current_build_number":3,"app_version":"1.2.0"}'
```

### **Test Both Platforms:**
```bash
# Test Android
curl -X POST https://chonapp.net/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{"platform":"android","current_version":"0.9.0","current_build_number":0,"app_version":"0.9.0"}'

# Test iOS
curl -X POST https://chonapp.net/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{"platform":"ios","current_version":"0.9.0","current_build_number":0,"app_version":"0.9.0"}'
```

---

## 📊 Response Status Codes

| Status Code | Description |
|-------------|-------------|
| `200` | Success - Update check completed |
| `400` | Bad Request - Validation failed |
| `500` | Internal Server Error |

---

## 🔒 Security Notes

- **No authentication required** for public endpoint
- **Rate limiting** may apply
- **CORS enabled** for cross-origin requests
- **Input validation** on all parameters

---

## 📞 Support

If you encounter issues:
1. **Check API endpoint:** `https://chonapp.net/api/app-updates/check`
2. **Verify request format** matches examples above
3. **Test with curl** to isolate Flutter-specific issues
4. **Check Laravel logs** on server for errors

---

**🎉 Your Flutter app is now ready to integrate with the App Update API!**
