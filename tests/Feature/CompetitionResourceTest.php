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
    $openTime = now()->addDay()->startOfHour();
    $startTime = now()->addDays(2)->startOfHour();
    $endTime = now()->addDays(3)->startOfHour();

    $newCompetition = [
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'open_time' => $openTime,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'game_type' => 'action'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($newCompetition)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('competitions', [
        'name' => 'Test Competition',
        'entry_fee' => '50.00',
        'game_type' => 'action'
    ]);
});


test('cannot create competition with invalid dates', function () {
    $openTime = now()->addDay()->startOfHour();
    $startTime = now()->addDays(3)->startOfHour();
    $endTime = now()->addDays(2)->startOfHour(); // End time before start time

    $invalidCompetition = [
        'name' => 'Invalid Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'open_time' => $openTime,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'game_type' => 'action'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($invalidCompetition)
        ->call('create')
        ->assertHasErrors(['data.end_time']);
});

test('cannot create competition with start time before open time', function () {
    $openTime = now()->addDays(2)->startOfHour();
    $startTime = now()->addDay()->startOfHour(); // Start time before open time
    $endTime = now()->addDays(3)->startOfHour();

    $invalidCompetition = [
        'name' => 'Invalid Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'open_time' => $openTime,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'game_type' => 'action'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($invalidCompetition)
        ->call('create')
        ->assertHasErrors(['data.start_time']);
});

test('validates negative values', function () {
    $openTime = now()->addDay()->startOfHour();
    $startTime = now()->addDays(2)->startOfHour();
    $endTime = now()->addDays(3)->startOfHour();

    $competition = [
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => '-50.00',
        'open_time' => $openTime,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'game_type' => 'action'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($competition)
        ->call('create')
        ->assertHasErrors(['data.entry_fee']);

    $this->assertDatabaseMissing('competitions', [
        'name' => 'Test Competition'
    ]);
});

test('can delete upcoming competition', function () {
    // Create a competition that hasn't opened for registration yet
    $competition = Competition::factory()->upcoming()->create();

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->assertActionVisible('delete')
        ->callAction('delete');

    $this->assertModelMissing($competition);
});

test('cannot delete active competition', function () {
    // Create a competition that is currently active
    $competition = Competition::factory()->active()->create();

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->assertActionHidden('delete');

    $this->assertModelExists($competition);
});

test('cannot delete open for registration competition', function () {
    // Create a competition that is open for registration
    $competition = Competition::factory()->openForRegistration()->create();

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->assertActionHidden('delete');

    $this->assertModelExists($competition);
});

test('cannot modify protected fields of active competition', function () {
    // Create a competition that is currently active
    $competition = Competition::factory()->active()->create([
        'entry_fee' => 50.00,
        'max_users' => 100
    ]);

    $updatedData = [
        'name' => 'Updated Name',
        'entry_fee' => 75.00,
        'open_time' => $competition->open_time,
        'start_time' => $competition->start_time,
        'end_time' => $competition->end_time,
        'max_users' => 200,
        'game_type' => 'strategy'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->fillForm($updatedData)
        ->call('save');

    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'name' => 'Updated Name',
        'entry_fee' => 50.00,  // Should remain unchanged
        'max_users' => 100,    // Should remain unchanged
        'game_type' => 'strategy' // Should be updated
    ]);
});

test('can view competition status based on time', function () {
    // Create competitions in different states
    $upcomingCompetition = Competition::factory()->upcoming()->create();
    $openCompetition = Competition::factory()->openForRegistration()->create();
    $activeCompetition = Competition::factory()->active()->create();
    $completedCompetition = Competition::factory()->completed()->create();

    // Check status calculations
    $this->assertEquals('upcoming', $upcomingCompetition->getStatus());
    $this->assertEquals('open', $openCompetition->getStatus());
    $this->assertEquals('active', $activeCompetition->getStatus());
    $this->assertEquals('completed', $completedCompetition->getStatus());

    // Check helper methods
    $this->assertTrue($upcomingCompetition->isUpcoming());
    $this->assertTrue($openCompetition->isOpen());
    $this->assertTrue($activeCompetition->isActive());
    $this->assertTrue($completedCompetition->isCompleted());
});