<?php

namespace Database\Seeders;

use App\Models\Competition;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 upcoming competitions
        Competition::factory()
            ->count(10)
            ->upcoming()
            ->create();

        // Create 5 active competitions
        Competition::factory()
            ->count(5)
            ->active()
            ->create();

        // Create 8 mixed status competitions
        Competition::factory()
            ->count(8)
            ->create();
    }
}
