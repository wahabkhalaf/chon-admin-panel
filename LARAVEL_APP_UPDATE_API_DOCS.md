# Laravel App Update API Documentation

## Overview
This documentation explains how to implement a Laravel API for managing app updates that works seamlessly with your Flutter app's update system. The API allows admins to manage app versions and Flutter apps to check for updates automatically.

## Table of Contents
1. [Database Setup](#database-setup)
2. [Model Creation](#model-creation)
3. [Controller Implementation](#controller-implementation)
4. [API Routes](#api-routes)
5. [Middleware Setup](#middleware-setup)
6. [Usage Examples](#usage-examples)
7. [Testing](#testing)
8. [Deployment](#deployment)

---

## Database Setup

### 1. Create Migration
Run this command in your Laravel project:
```bash
php artisan make:migration create_app_versions_table
```

### 2. Migration Content
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // 'ios' or 'android'
            $table->string('version'); // e.g., '1.1.0'
            $table->integer('build_number'); // e.g., 10
            $table->string('app_store_url')->nullable(); // App Store/Play Store URL
            $table->text('release_notes')->nullable(); // What's new in this version
            $table->boolean('is_force_update')->default(false); // Force users to update
            $table->boolean('is_active')->default(true); // Enable/disable this version
            $table->timestamp('released_at')->nullable(); // When this version was released
            $table->timestamps();
            
            // Ensure unique platform + version combinations
            $table->unique(['platform', 'version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_versions');
    }
};
```

### 3. Run Migration
```bash
php artisan migrate
```

---

## Model Creation

### 1. Create Model
```bash
php artisan make:model AppVersion
```

### 2. Model Content
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'app_store_url',
        'release_notes',
        'is_force_update',
        'is_active',
        'released_at',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
        'released_at' => 'datetime',
        'build_number' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('build_number', 'desc');
    }

    // Get latest version for a specific platform
    public static function getLatestVersion($platform)
    {
        return static::active()
            ->platform($platform)
            ->latest()
            ->first();
    }

    // Check if update is required
    public function isUpdateRequired($currentBuildNumber)
    {
        return $this->build_number > $currentBuildNumber;
    }

    // Check if this is a force update
    public function isForceUpdate($currentBuildNumber)
    {
        return $this->is_force_update && $this->build_number > $currentBuildNumber;
    }
}
```

---

## Controller Implementation

### 1. Create Controller
```bash
php artisan make:controller Api/AppUpdateController
```

### 2. Controller Content
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AppUpdateController extends Controller
{
    /**
     * Check for app updates
     * This is the main endpoint your Flutter app calls
     */
    public function checkForUpdates(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|in:ios,android',
                'current_version' => 'required|string',
                'current_build_number' => 'required|integer',
                'app_version' => 'required|string', // Flutter app version
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $platform = $request->input('platform');
            $currentVersion = $request->input('current_version');
            $currentBuildNumber = $request->input('current_build_number');
            $appVersion = $request->input('app_version');

            // Log the update check
            Log::info('App update check', [
                'platform' => $platform,
                'current_version' => $currentVersion,
                'current_build_number' => $currentBuildNumber,
                'app_version' => $appVersion,
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
            ]);

            // Get latest version for this platform
            $latestVersion = AppVersion::getLatestVersion($platform);

            if (!$latestVersion) {
                return response()->json([
                    'success' => true,
                    'update_available' => false,
                    'message' => 'No updates available',
                    'data' => null
                ]);
            }

            // Check if update is required
            $updateRequired = $latestVersion->isUpdateRequired($currentBuildNumber);
            $forceUpdate = $latestVersion->isForceUpdate($currentBuildNumber);

            if (!$updateRequired) {
                return response()->json([
                    'success' => true,
                    'update_available' => false,
                    'message' => 'App is up to date',
                    'data' => null
                ]);
            }

            // Return update information
            return response()->json([
                'success' => true,
                'update_available' => true,
                'message' => 'Update available',
                'data' => [
                    'latest_version' => $latestVersion->version,
                    'latest_build_number' => $latestVersion->build_number,
                    'current_version' => $currentVersion,
                    'current_build_number' => $currentBuildNumber,
                    'is_force_update' => $forceUpdate,
                    'app_store_url' => $latestVersion->app_store_url,
                    'release_notes' => $latestVersion->release_notes,
                    'released_at' => $latestVersion->released_at?->toISOString(),
                    'update_message' => $forceUpdate 
                        ? 'This update is required to continue using the app.'
                        : 'A new version is available with exciting new features!',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('App update check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Get all app versions (Admin only)
     */
    public function index(): JsonResponse
    {
        try {
            $versions = AppVersion::orderBy('platform')
                ->orderBy('build_number', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $versions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch app versions'
            ], 500);
        }
    }

    /**
     * Store a new app version (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|in:ios,android',
                'version' => 'required|string|max:20',
                'build_number' => 'required|integer|min:1',
                'app_store_url' => 'nullable|url|max:500',
                'release_notes' => 'nullable|string|max:1000',
                'is_force_update' => 'boolean',
                'is_active' => 'boolean',
                'released_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check if version already exists for this platform
            $existingVersion = AppVersion::where('platform', $request->platform)
                ->where('version', $request->version)
                ->first();

            if ($existingVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Version already exists for this platform'
                ], 409);
            }

            $version = AppVersion::create($request->all());

            Log::info('New app version created', [
                'platform' => $version->platform,
                'version' => $version->version,
                'build_number' => $version->build_number,
                'created_by' => auth()->id() ?? 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'App version created successfully',
                'data' => $version
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create app version', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create app version'
            ], 500);
        }
    }

    /**
     * Update an existing app version (Admin only)
     */
    public function update(Request $request, AppVersion $appVersion): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'sometimes|in:ios,android',
                'version' => 'sometimes|string|max:20',
                'build_number' => 'sometimes|integer|min:1',
                'app_store_url' => 'nullable|url|max:500',
                'release_notes' => 'nullable|string|max:1000',
                'is_force_update' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
                'released_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $appVersion->update($request->all());

            Log::info('App version updated', [
                'id' => $appVersion->id,
                'platform' => $appVersion->platform,
                'version' => $appVersion->version,
                'updated_by' => auth()->id() ?? 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'App version updated successfully',
                'data' => $appVersion->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update app version', [
                'error' => $e->getMessage(),
                'version_id' => $appVersion->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update app version'
            ], 500);
        }
    }

    /**
     * Delete an app version (Admin only)
     */
    public function destroy(AppVersion $appVersion): JsonResponse
    {
        try {
            $appVersion->delete();

            Log::info('App version deleted', [
                'id' => $appVersion->id,
                'platform' => $appVersion->platform,
                'version' => $appVersion->version,
                'deleted_by' => auth()->id() ?? 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'App version deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete app version', [
                'error' => $e->getMessage(),
                'version_id' => $appVersion->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete app version'
            ], 500);
        }
    }

    /**
     * Get update statistics (Admin only)
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_versions' => AppVersion::count(),
                'ios_versions' => AppVersion::platform('ios')->count(),
                'android_versions' => AppVersion::platform('android')->count(),
                'active_versions' => AppVersion::active()->count(),
                'force_updates' => AppVersion::where('is_force_update', true)->count(),
                'latest_ios' => AppVersion::getLatestVersion('ios'),
                'latest_android' => AppVersion::getLatestVersion('android'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}
```

---

## API Routes

### 1. Add to `routes/api.php`
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUpdateController;

// App Update Routes
Route::prefix('app-updates')->group(function () {
    // Public route - Flutter app calls this
    Route::post('/check', [AppUpdateController::class, 'checkForUpdates']);
    
    // Admin routes - Protected by middleware
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/', [AppUpdateController::class, 'index']);
        Route::post('/', [AppUpdateController::class, 'store']);
        Route::put('/{appVersion}', [AppUpdateController::class, 'update']);
        Route::delete('/{appVersion}', [AppUpdateController::class, 'destroy']);
        Route::get('/statistics', [AppUpdateController::class, 'statistics']);
    });
});
```

---

## Middleware Setup

### 1. Create Admin Middleware
```bash
php artisan make:middleware AdminMiddleware
```

### 2. Middleware Content
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and is admin
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login'
            ], 401);
        }

        // Check if user has admin role (adjust this based on your user system)
        $user = auth()->user();
        
        // Option 1: Check for admin role in users table
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied - Admin privileges required'
            ], 403);
        }

        // Option 2: If you're using Spatie Laravel Permission
        // if (!$user->hasRole('admin')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Access denied - Admin privileges required'
        //     ], 403);
        // }

        return $next($request);
    }
}
```

### 3. Register Middleware in `app/Http/Kernel.php`
```php
protected $routeMiddleware = [
    // ... other middlewares
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];
```

---

## Usage Examples

### 1. Flutter App Update Check
```dart
// Your Flutter app calls this endpoint
POST /api/app-updates/check

{
    "platform": "android",
    "current_version": "1.0.8",
    "current_build_number": 8,
    "app_version": "1.0.8"
}
```

**Response:**
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

### 2. Admin Creates New Version
```bash
POST /api/app-updates
Authorization: Bearer {admin_token}

{
    "platform": "android",
    "version": "1.1.0",
    "build_number": 10,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "New features and bug fixes",
    "is_force_update": false,
    "is_active": true,
    "released_at": "2024-01-15T10:00:00Z"
}
```

### 3. Admin Gets Statistics
```bash
GET /api/app-updates/statistics
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_versions": 5,
        "ios_versions": 2,
        "android_versions": 3,
        "active_versions": 4,
        "force_updates": 1,
        "latest_ios": {
            "id": 3,
            "platform": "ios",
            "version": "1.1.0",
            "build_number": 10
        },
        "latest_android": {
            "id": 4,
            "platform": "android",
            "version": "1.1.0",
            "build_number": 10
        }
    }
}
```

---

## Testing

### 1. Test Update Check (Public Endpoint)
```bash
curl -X POST http://your-domain.com/api/app-updates/check \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "android",
    "current_version": "1.0.8",
    "current_build_number": 8,
    "app_version": "1.0.8"
  }'
```

### 2. Test Admin Endpoints
```bash
# Get all versions
curl -X GET http://your-domain.com/api/app-updates \
  -H "Authorization: Bearer {your_admin_token}"

# Create new version
curl -X POST http://your-domain.com/api/app-updates \
  -H "Authorization: Bearer {your_admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "android",
    "version": "1.1.0",
    "build_number": 10,
    "app_store_url": "https://play.google.com/store/apps/details?id=com.chon.app",
    "release_notes": "New features and bug fixes"
  }'
```

---

## Deployment

### 1. Database Setup
```bash
# Run migrations
php artisan migrate

# Optional: Seed with initial data
php artisan db:seed --class=AppVersionSeeder
```

### 2. Environment Variables
Add to your `.env` file:
```env
APP_DEBUG=false
APP_ENV=production
```

### 3. Cache Routes
```bash
php artisan route:cache
php artisan config:cache
```

---

## Integration with Flutter App

### 1. Update Your Flutter Service
Replace the hardcoded version check in your Flutter app with an API call:

```dart
// In your Flutter app's update service
Future<Map<String, dynamic>> checkForUpdates() async {
  try {
    final response = await http.post(
      Uri.parse('https://your-domain.com/api/app-updates/check'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'platform': Platform.isIOS ? 'ios' : 'android',
        'current_version': currentVersion,
        'current_build_number': currentBuildNumber,
        'app_version': appVersion,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data;
    }
  } catch (e) {
    print('Update check failed: $e');
  }
  
  return {'update_available': false};
}
```

### 2. Automatic Update Checks
Your Flutter app will now:
- Check for updates every 12 hours automatically
- Show beautiful update popups when new versions are available
- Redirect users to App Store/Google Play for downloads
- Handle force updates when critical

---

## Security Considerations

1. **Rate Limiting**: Consider adding rate limiting to the public endpoint
2. **Input Validation**: All inputs are validated and sanitized
3. **Admin Access**: Admin endpoints are protected by authentication and role checks
4. **Logging**: All update checks and admin actions are logged for audit purposes
5. **HTTPS**: Always use HTTPS in production

---

## Troubleshooting

### Common Issues:

1. **Migration Fails**: Ensure your database supports the required column types
2. **Admin Access Denied**: Check if the user has `is_admin = true` in the users table
3. **Route Not Found**: Clear route cache with `php artisan route:clear`
4. **Validation Errors**: Check the request payload matches the expected format

### Debug Mode:
Enable debug mode in `.env` to see detailed error messages:
```env
APP_DEBUG=true
```

---

## Support

For issues or questions:
1. Check the Laravel logs in `storage/logs/laravel.log`
2. Verify database connections and migrations
3. Test endpoints with Postman or similar tools
4. Check authentication and middleware configuration

---

## Version History

- **v1.0.0** - Initial release with basic CRUD operations
- **v1.1.0** - Added statistics endpoint and improved error handling
- **v1.2.0** - Added force update support and release notes
