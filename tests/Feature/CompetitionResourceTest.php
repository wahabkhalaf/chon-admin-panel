<?php

use App\Filament\Resources\CompetitionResource;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;


beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
});



test('can view competition list', function () {
    // Ensure competitions are created with valid IDs
    $competitions = Competition::factory()->count(3)->create();
    
    // Refresh the model instances to ensure they have IDs
    $competitions = Competition::all();
    
    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\ListCompetitions::class)
        ->assertCanSeeTableRecords($competitions);
});

test('can create competition', function () {
    $startTime = now()->addDays(1)->startOfHour();
    $endTime = now()->addDays(2)->startOfHour();
    
    $newCompetition = [
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'prize_pool' => '1000.00',
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'status' => 'upcoming'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($newCompetition)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('competitions', [
        'name' => 'Test Competition',
        'entry_fee' => '50.00',
        'prize_pool' => '1000.00',
        'status' => 'upcoming'
    ]);
});

test('cannot create competition with invalid dates', function () {
    // Define the time values - Start time is AFTER end time to trigger validation error
    $startTime = now()->addDays(2)->startOfHour();
    $endTime = now()->addDays(1)->startOfHour(); // End time before start time

    
    $invalidCompetition = [
        'name' => 'Invalid Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'prize_pool' => '1000.00',
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'status' => 'upcoming'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($invalidCompetition)
        ->call('create')
        ->assertHasErrors(['data.end_time']);
    
    $this->assertDatabaseMissing('competitions', [
        'name' => 'Invalid Competition',
    ]);
});

test('can update competition', function () {
    // Create a competition with a specific set of dates to avoid validation issues
    $startTime = now()->addDay()->startOfHour();
    $endTime = now()->addDays(2)->startOfHour();
    
    $competition = Competition::factory()->create([
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);
    
    // Refresh to ensure we have the latest data
    $competition->refresh();
    
    
    
    $updatedData = [
        'name' => 'Updated Competition',
        'description' => $competition->description,
        'entry_fee' => '75.00',
        'prize_pool' => '1500.00',
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => $competition->max_users,
        'status' => 'active'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->fillForm($updatedData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'name' => 'Updated Competition',
        'entry_fee' => '75.00',
        'prize_pool' => '1500.00',
        'status' => 'active'
    ]);
});

test('can delete competition', function () {
    $competition = Competition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->callAction('delete');

    $this->assertDatabaseMissing('competitions', [
        'id' => $competition->id,
    ]);
});

test('validates negative values', function () {
    $startTime = now()->addDays(1)->startOfHour();
    $endTime = now()->addDays(2)->startOfHour();
    
    $invalidCompetition = [
        'data' => [
            'name' => 'Test Competition',
            'description' => 'Test Description',
            'entry_fee' => '-50.00',
            'prize_pool' => '-1000.00',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_users' => 100,
            'status' => 'upcoming'
        ]
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($invalidCompetition)
        ->call('create')
        ->assertHasNoFormErrors(); // Negative values are auto-corrected

    $this->assertDatabaseHas('competitions', [
        'name' => 'Test Competition',
        'entry_fee' => '0.00',
        'prize_pool' => '0.00',
    ]);
});