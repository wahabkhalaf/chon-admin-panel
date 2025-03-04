<?php

use App\Filament\Resources\CompetitionResource;
use \App\Filament\Resources\CompetitionResource\Pages\CreateCompetition;
use App\Models\Competition;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'test@example.com']);
});

test('can view competition list', function () {
    $this->actingAs($this->user)
        ->get('/admin/competitions')
        ->assertSuccessful();
});

test('can create competition', function () {
    $newCompetition = [
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00,
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(2)->toDateTimeString(),
        'max_users' => 100,
        'status' => 'upcoming'
    ];

    Livewire::actingAs($this->user)
        ->component(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($newCompetition)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('competitions', [
        'name' => 'Test Competition',
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00,
    ]);
});

test('cannot create competition with invalid dates', function () {
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
});

test('can update competition', function () {
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
});

test('can delete competition', function () {
    $competition = Competition::factory()->create();

    $this->actingAs($this->user)
        ->delete("/admin/competitions/{$competition->id}");

    $this->assertDatabaseMissing('competitions', [
        'id' => $competition->id,
    ]);
});

test('validates negative values', function () {
    $invalidCompetition = [
        'data' => [
            'name' => 'Test Competition',
            'entry_fee' => -50.00, // Negative value
            'prize_pool' => -1000.00, // Negative value
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(2),
            'max_users' => 100,
            'status' => 'upcoming'
        ]
    ];

    $response = $this->actingAs($this->user)
        ->post('/admin/competitions', $invalidCompetition);

    $this->assertDatabaseMissing('competitions', [
        'name' => 'Test Competition'
    ]);
});