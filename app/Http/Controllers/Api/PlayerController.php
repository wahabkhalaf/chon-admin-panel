<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlayerController extends Controller
{
    /**
     * Update FCM token for a player
     * 
     * POST /api/v1/player/fcm-token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        try {
            // Validate the request - at least one identifier must be provided
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string|max:1000',
                'device_type' => 'nullable|string|in:android,ios,web',
                'app_version' => 'nullable|string|max:20',
                'player_id' => 'nullable|integer|exists:players,id',
                'whatsapp_number' => 'nullable|string',
            ]);

            // Custom validation: require at least one identifier
            $validator->after(function ($validator) use ($request) {
                if (!$request->has('player_id') && !$request->has('whatsapp_number') && !$request->bearerToken()) {
                    $validator->errors()->add('player_id', 'Either player_id, whatsapp_number, or Bearer token is required.');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Try to identify the player
            $player = null;

            // Method 1: Get player by ID if provided
            if ($request->has('player_id') && $request->player_id) {
                $player = Player::find($request->player_id);
            }
            // Method 2: Get player by WhatsApp number if provided
            elseif ($request->has('whatsapp_number') && $request->whatsapp_number) {
                $player = Player::where('whatsapp_number', $request->whatsapp_number)->first();
            }
            // Method 3: Decode Bearer token (JWT) to extract player info
            elseif ($request->bearerToken()) {
                $player = $this->getPlayerFromBearerToken($request->bearerToken());
                
                if (!$player) {
                    Log::warning('FCM token update: Could not identify player from Bearer token', [
                        'token_prefix' => substr($request->bearerToken(), 0, 20) . '...',
                        'has_player_id' => $request->has('player_id'),
                        'has_whatsapp_number' => $request->has('whatsapp_number'),
                    ]);
                }
            }

            // If player not found, return error
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found. Please provide player_id or whatsapp_number in the request body.'
                ], 404);
            }

            // Update FCM token
            $player->update([
                'fcm_token' => $request->fcm_token
            ]);

            Log::info('FCM token updated successfully', [
                'player_id' => $player->id,
                'whatsapp_number' => $player->whatsapp_number,
                'device_type' => $request->device_type,
                'app_version' => $request->app_version,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'data' => [
                    'player_id' => $player->id,
                    'fcm_token' => $request->fcm_token,
                    'device_type' => $request->device_type ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update FCM token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Extract player information from Bearer token (JWT)
     * 
     * @param string $token
     * @return Player|null
     */
    protected function getPlayerFromBearerToken(string $token): ?Player
    {
        try {
            // JWT tokens have 3 parts: header.payload.signature
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                Log::warning('Invalid JWT format - expected 3 parts', [
                    'parts_count' => count($parts)
                ]);
                return null;
            }

            // Decode the payload (second part)
            // JWT uses base64url encoding (URL-safe base64)
            $payload = $parts[1];
            
            // Convert base64url to base64
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            
            // Add padding if needed
            $padding = strlen($payload) % 4;
            if ($padding > 0) {
                $payload .= str_repeat('=', 4 - $padding);
            }
            
            $decoded = json_decode(base64_decode($payload), true);
            
            if (!$decoded || !is_array($decoded)) {
                Log::warning('Failed to decode JWT payload', [
                    'payload_length' => strlen($payload)
                ]);
                return null;
            }

            Log::info('JWT payload decoded', [
                'keys' => array_keys($decoded),
                'has_player_id' => isset($decoded['player_id']),
                'has_id' => isset($decoded['id']),
                'has_whatsapp_number' => isset($decoded['whatsapp_number']),
            ]);

            // Try to find player by various possible JWT claims
            $player = null;

            // Try player_id claim
            if (isset($decoded['player_id'])) {
                $player = Player::find($decoded['player_id']);
            }
            // Try id claim (might be player ID)
            elseif (isset($decoded['id']) && !isset($decoded['user_id'])) {
                $player = Player::find($decoded['id']);
            }
            // Try whatsapp_number claim
            elseif (isset($decoded['whatsapp_number'])) {
                $player = Player::where('whatsapp_number', $decoded['whatsapp_number'])->first();
            }
            // Try sub claim (subject - might be player ID)
            elseif (isset($decoded['sub'])) {
                $player = Player::find($decoded['sub']);
            }

            if ($player) {
                Log::info('Player found from JWT token', [
                    'player_id' => $player->id,
                    'jwt_claims' => array_keys($decoded)
                ]);
            }

            return $player;

        } catch (\Exception $e) {
            Log::error('Error decoding Bearer token', [
                'error' => $e->getMessage(),
                'token_prefix' => substr($token, 0, 20) . '...'
            ]);
            return null;
        }
    }
}

