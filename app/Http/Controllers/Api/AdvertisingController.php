<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertising;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdvertisingController extends Controller
{
    /**
     * Add CORS headers to response
     */
    private function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'false');
        
        return $response;
    }

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

        $response = response()->json([
            'success' => true,
            'data' => $advertisements,
            'count' => $advertisements->count(),
        ]);

        return $this->addCorsHeaders($response);
    }

    /**
     * Get a specific advertisement
     */
    public function show(Advertising $advertising): JsonResponse
    {
        $response = response()->json([
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

        return $this->addCorsHeaders($response);
    }

    /**
     * Get random active advertisement
     */
    public function random(): JsonResponse
    {
        $advertisement = Advertising::active()->inRandomOrder()->first();

        if (!$advertisement) {
            $response = response()->json([
                'success' => false,
                'message' => 'No active advertisements found',
            ], 404);
            
            return $this->addCorsHeaders($response);
        }

        $response = response()->json([
            'success' => true,
            'data' => [
                'id' => $advertisement->id,
                'company_name' => $advertisement->company_name,
                'phone_number' => $advertisement->phone_number,
                'image_url' => $advertisement->image_url,
            ],
        ]);

        return $this->addCorsHeaders($response);
    }

    /**
     * Get only the image URL from the active advertisement
     */
    public function image(): JsonResponse
    {
        $advertisement = Advertising::active()->first();

        if (!$advertisement || !$advertisement->image) {
            $response = response()->json([
                'success' => false,
                'message' => 'No active advertisement image found',
            ], 404);
            
            return $this->addCorsHeaders($response);
        }

        $response = response()->json([
            'success' => true,
            'data' => [
                'image_url' => $advertisement->production_image_url, // Use production URL for API
            ],
        ]);

        return $this->addCorsHeaders($response);
    }
}
