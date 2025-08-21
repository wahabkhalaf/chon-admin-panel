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
