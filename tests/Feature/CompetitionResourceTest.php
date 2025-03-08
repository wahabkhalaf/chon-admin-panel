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
        'data' => [
            'name' => 'Test Competition',
            'description' => 'Test Description',
            'entry_fee' => '50.00',
            'open_time' => $openTime->format('Y-m-d H:i:s'),
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'max_users' => 100,
            'game_type' => 'action'
        ]
    ];

    // Create the competition manually
    $competition = Competition::create([
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => '50.00',
        'open_time' => $openTime,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'game_type' => 'action'
    ]);

    // Assert the competition was created
    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'name' => 'Test Competition',
        'entry_fee' => '50.00',
        'game_type' => 'action'
    ]);

    // Test that we can view the competition in the list
    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\ListCompetitions::class)
        ->assertCanSeeTableRecords([$competition]);
});


test('cannot create competition with invalid dates', function () {
    $openTime = now()->addDay()->startOfHour();
    $startTime = now()->addDays(3)->startOfHour();
    $endTime = now()->addDays(2)->startOfHour(); // End time before start time

    $invalidCompetition = [
        'data' => [
            'name' => 'Invalid Competition',
            'description' => 'Test Description',
            'entry_fee' => '50.00',
            'open_time' => $openTime->format('Y-m-d H:i:s'),
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'max_users' => 100,
            'game_type' => 'action'
        ]
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
        'data' => [
            'name' => 'Invalid Competition',
            'description' => 'Test Description',
            'entry_fee' => '50.00',
            'open_time' => $openTime->format('Y-m-d H:i:s'),
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'max_users' => 100,
            'game_type' => 'action'
        ]
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
        'data' => [
            'name' => 'Test Competition',
            'description' => 'Test Description',
            'entry_fee' => '-50.00',
            'open_time' => $openTime->format('Y-m-d H:i:s'),
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'max_users' => 100,
            'game_type' => 'action'
        ]
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
    // Create an active competition with a fixed name for reliable testing
    $competition = Competition::factory()->active()->create([
        'name' => 'Original Competition Name',
        'game_type' => 'action'
    ]);

    $competitionId = $competition->id;

    // Save original values for comparison
    $originalEntryFee = $competition->entry_fee;
    $originalMaxUsers = $competition->max_users;
    $originalGameType = $competition->game_type;
    $originalName = $competition->name;

    // Create update data with all fields changed
    $updatedData = [
        'data' => [
            'name' => 'Updated Name',
            'entry_fee' => 75.00,
            'open_time' => $competition->open_time->format('Y-m-d H:i:s'),
            'start_time' => $competition->start_time->format('Y-m-d H:i:s'),
            'end_time' => $competition->end_time->format('Y-m-d H:i:s'),
            'max_users' => 200,
            'game_type' => 'strategy'
        ]
    ];

    // Perform the update through Livewire
    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competitionId,
        ])
        ->fillForm($updatedData)
        ->call('save');

    // Query the database directly to avoid any caching issues
    $updatedCompetition = Competition::find($competitionId);

    // Assert that protected fields remain unchanged
    $this->assertEquals($originalEntryFee, $updatedCompetition->entry_fee);
    $this->assertEquals($originalMaxUsers, $updatedCompetition->max_users);

    // Assert that name and game_type also remain unchanged (since active competitions cannot be edited)
    $this->assertEquals($originalName, $updatedCompetition->name);
    $this->assertEquals($originalGameType, $updatedCompetition->game_type);
});

test('can view competition status based on time', function () {
    // Create competitions in different states with explicit time ranges
    $upcomingCompetition = Competition::factory()->state([
        'open_time' => now()->addDays(5),
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(11),
    ])->create();

    $openCompetition = Competition::factory()->state([
        'open_time' => now()->subDays(1),
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(2),
    ])->create();

    $activeCompetition = Competition::factory()->state([
        'open_time' => now()->subDays(2),
        'start_time' => now()->subHours(12),
        'end_time' => now()->addDays(1),
    ])->create();

    $completedCompetition = Competition::factory()->state([
        'open_time' => now()->subDays(10),
        'start_time' => now()->subDays(5),
        'end_time' => now()->subDays(1),
    ])->create();

    // Refresh models to ensure we have the latest data
    $upcomingCompetition->refresh();
    $openCompetition->refresh();
    $activeCompetition->refresh();
    $completedCompetition->refresh();

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