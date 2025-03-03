<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email' => 'test@example.com']);
    }

    public function testCanViewCompetitionList(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/competitions')
            ->assertSuccessful();
    }

    public function testCanCreateCompetition(): void
    {
        $newCompetition = [
            'name' => 'Test Competition',
            'description' => 'Test Description',
            'entry_fee' => 50.00,
            'prize_pool' => 1000.00,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(2),
            'max_users' => 100,
            'status' => 'upcoming'
        ];

        $response = $this->actingAs($this->user)
            ->post('/admin/competitions', [
                'data' => $newCompetition,
            ]);

        $this->assertDatabaseHas('competitions', [
            'name' => 'Test Competition',
            'entry_fee' => 50.00,
            'prize_pool' => 1000.00,
        ]);
    }

    public function testCannotCreateCompetitionWithInvalidDates(): void
    {
        $invalidCompetition = [
            'name' => 'Invalid Competition',
            'description' => 'Test Description',
            'entry_fee' => 50.00,
            'prize_pool' => 1000.00,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(1), // End time before start time
            'max_users' => 100,
            'status' => 'upcoming'
        ];

        $response = $this->actingAs($this->user)
            ->post('/admin/competitions', [
                'data' => $invalidCompetition,
            ]);

        $this->assertDatabaseMissing('competitions', [
            'name' => 'Invalid Competition',
        ]);
    }

    public function testCanUpdateCompetition(): void
    {
        $competition = Competition::factory()->create();

        $updatedData = [
            'data' => [
                'name' => 'Updated Competition',
                'entry_fee' => 75.00,
                'prize_pool' => 1500.00,
                'status' => 'active'
            ]
        ];

        $this->actingAs($this->user)
            ->patch("/admin/competitions/{$competition->id}", $updatedData);

        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'name' => 'Updated Competition',
            'entry_fee' => 75.00,
            'prize_pool' => 1500.00,
            'status' => 'active'
        ]);
    }

    public function testCanDeleteCompetition(): void
    {
        $competition = Competition::factory()->create();

        $this->actingAs($this->user)
            ->delete("/admin/competitions/{$competition->id}");

        $this->assertDatabaseMissing('competitions', [
            'id' => $competition->id,
        ]);
    }

    public function testValidatesNegativeValues(): void
    {
        $invalidCompetition = [
            'data' => [
                'name' => 'Test Competition',
                'entry_fee' => -50.00, // Negative value
                'prize_pool' => -1000.00, // Negative value