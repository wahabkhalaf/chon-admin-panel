<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\PlayerOtp;
use Illuminate\Database\Seeder;

class GameMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if GameMaster already exists
        $existingPlayer = Player::where('whatsapp_number', '+96407508961058')->first();

        if ($existingPlayer) {
            // Update existing player to be verified
            $existingPlayer->update([
                'nickname' => 'GameMaster',
                'is_verified' => true,
            ]);

            $this->command->info('Updated existing GameMaster player and set as verified.');
            return;
        }

        // Create the GameMaster player
        $gameMaster = Player::create([
            'whatsapp_number' => '+96407508961058',
            'nickname' => 'GameMaster',
            'total_score' => 1000, // Give them a high score
            'level' => 10, // Advanced level
            'experience_points' => 5000,
            'is_verified' => true, // Verified player
            'language' => 'en', // Default language
        ]);

        // Create a verified OTP for login testing
        PlayerOtp::create([
            'player_id' => $gameMaster->id,
            'otp_code' => '111111',
            'purpose' => 'login',
            'expires_at' => now()->addHours(24), // Valid for 24 hours for testing
            'is_verified' => true,
        ]);

        // Create a fresh OTP for immediate use
        PlayerOtp::create([
            'player_id' => $gameMaster->id,
            'otp_code' => '123456',
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(30),
            'is_verified' => false,
        ]);

        $this->command->info('Created GameMaster player (+96407508961058) and set as verified.');
    }
}