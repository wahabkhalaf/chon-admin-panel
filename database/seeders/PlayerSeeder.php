<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\PlayerOtp;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 advanced players
        Player::factory()
            ->advanced()
            ->count(5)
            ->create();

        // Create 10 regular players
        Player::factory()
            ->count(10)
            ->create();

        // Create 5 newbie players
        Player::factory()
            ->newbie()
            ->count(5)
            ->create();

        // Create a test player with a predefined WhatsApp number for testing
        $testPlayer = Player::factory()->create([
            'whatsapp_number' => '+1234567890',
            'nickname' => 'TestPlayer',
        ]);

        // Create active OTPs for the test player
        PlayerOtp::factory()
            ->forLogin()
            ->for($testPlayer)
            ->create([
                'otp_code' => '123456',
                'expires_at' => now()->addMinutes(30),
            ]);

        PlayerOtp::factory()
            ->forRegistration()
            ->for($testPlayer)
            ->create([
                'otp_code' => '654321',
                'expires_at' => now()->addMinutes(30),
            ]);

        // Create some verified OTPs
        PlayerOtp::factory()
            ->verified()
            ->count(10)
            ->create();

        // Create some expired OTPs
        PlayerOtp::factory()
            ->expired()
            ->count(5)
            ->create();
    }
}