<?php

namespace Database\Seeders;

use App\Models\Advertising;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdvertisingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample advertisements
        Advertising::factory(5)->create();

        // Create a specific sample advertisement
        Advertising::create([
            'company_name' => 'Sample Company Ltd.',
            'phone_number' => '+964 750 123 4567',
            'image' => 'advertisements/sample-ad.jpg',
            'is_active' => true,
        ]);

        $this->command->info('Advertising data seeded successfully!');
    }
}
