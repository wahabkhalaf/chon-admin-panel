<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the competition seeder which creates questions and competitions
        $this->call(CompetitionFullSeeder::class);
    }
}
