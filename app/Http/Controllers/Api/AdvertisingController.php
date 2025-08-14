<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertising;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdvertisingController extends Controller
{
    /**
     * Get all active advertisements
     */
    public function index(): JsonResponse
    {
        $advertisements = Advertising::active()
            ->select(['id', 'company_name', 'phone_number', 'image', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'company_name' => $ad->company_name,
                    'phone_number' => $ad->phone_number,
                    'image_url' => $ad->image_url,
                    'created_at' => $ad->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $advertisements,
            'count' => $advertisements->count(),
        ]);
    }

    /**
     * Get a specific advertisement
     */
    public function show(Advertising $advertising): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $advertising->id,
                'company_name' => $advertising->company_name,
                'phone_number' => $advertising->phone_number,
                'image_url' => $advertising->image_url,
                'is_active' => $advertising->is_active,
                'created_at' => $advertising->created_at->toISOString(),
                'updated_at' => $advertising->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get random active advertisement
     */
    public function random(): JsonResponse
    {
        $advertisement = Advertising::active()->inRandomOrder()->first();

        if (!$advertisement) {
            return response()->json([
                'success' => false,
                'message' => 'No active advertisements found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $advertisement->id,
                'company_name' => $advertisement->company_name,
                'phone_number' => $advertisement->phone_number,
                'image_url' => $advertisement->image_url,
            ],
        ]);
    }

    /**
     * Get only the image URL from the active advertisement
     */
    public function image(): JsonResponse
    {
        $advertisement = Advertising::active()->first();

        if (!$advertisement || !$advertisement->image) {
            return response()->json([
                'success' => false,
                'message' => 'No active advertisement image found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image_url' => $advertisement->production_image_url, // Use production URL for API
            ],
        ]);
    }

    /**
     * Get base64 encoded image (alternative to avoid CORS issues)
     */
    public function imageBase64(): JsonResponse
    {
        $advertisement = Advertising::active()->first();

        if (!$advertisement || !$advertisement->image) {
            return response()->json([
                'success' => false,
                'message' => 'No active advertisement image found',
            ], 404);
        }

        $imagePath = storage_path('app/public/advertisements/' . $advertisement->image);
        
        if (!file_exists($imagePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Image file not found',
            ], 404);
        }

        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        $mimeType = mime_content_type($imagePath);

        return response()->json([
            'success' => true,
            'data' => [
                'image_base64' => $base64,
                'mime_type' => $mimeType,
            ],
        ]);
    }
}
