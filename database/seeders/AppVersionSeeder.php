<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AppVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Seeding app versions...');

        // Create initial Android versions
        AppVersion::create([
            'platform' => 'android',
            'version' => '1.0.0',
            'build_number' => 1,
            'app_store_url' => 'https://play.google.com/store/apps/details?id=com.chon.app',
            'release_notes' => 'Initial release of Chon app',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonths(3),
        ]);

        AppVersion::create([
            'platform' => 'android',
            'version' => '1.1.0',
            'build_number' => 2,
            'app_store_url' => 'https://play.google.com/store/apps/details?id=com.chon.app',
            'release_notes' => 'Bug fixes and performance improvements',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonths(2),
        ]);

        AppVersion::create([
            'platform' => 'android',
            'version' => '1.2.0',
            'build_number' => 3,
            'app_store_url' => 'https://play.google.com/store/apps/details?id=com.chon.app',
            'release_notes' => 'New features and UI improvements',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonth(),
        ]);

        // Create initial iOS versions
        AppVersion::create([
            'platform' => 'ios',
            'version' => '1.0.0',
            'build_number' => 1,
            'app_store_url' => 'https://apps.apple.com/app/id123456789',
            'release_notes' => 'Initial release of Chon app for iOS',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonths(3),
        ]);

        AppVersion::create([
            'platform' => 'ios',
            'version' => '1.1.0',
            'build_number' => 2,
            'app_store_url' => 'https://apps.apple.com/app/id123456789',
            'release_notes' => 'Bug fixes and performance improvements',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonths(2),
        ]);

        AppVersion::create([
            'platform' => 'ios',
            'version' => '1.2.0',
            'build_number' => 3,
            'app_store_url' => 'https://apps.apple.com/app/id123456789',
            'release_notes' => 'New features and UI improvements',
            'is_force_update' => false,
            'is_active' => true,
            'released_at' => Carbon::now()->subMonth(),
        ]);

        $this->command?->info('App versions seeded successfully!');
        $this->command?->info('Android versions: ' . AppVersion::where('platform', 'android')->count());
        $this->command?->info('iOS versions: ' . AppVersion::where('platform', 'ios')->count());
    }
}
