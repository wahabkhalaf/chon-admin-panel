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
    $startTime = now()->addDays(2)->startOfHour();
    $endTime = now()->addDays(1)->startOfHour();

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
});
test('can update competition status completed or closed', function () {
    // Create a competition with active status
    $competition = Competition::factory()->create([
        'status' => 'closed',
        'start_time' => now()->subDay()->startOfHour(),
        'end_time' => now()->subHour()->startOfHour(),
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00,
        'max_users' => 100
    ]);

    // Update to completed
    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->fillForm([
            'status' => 'completed'
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert status changed to completed
    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'status' => 'completed',
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00
    ]);

    // Update to closed
    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->fillForm([
            'status' => 'closed'
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert status changed to closed
    $this->assertDatabaseHas('competitions', [
        'id' => $competition->id,
        'status' => 'closed',
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00
    ]);
});



test('validates negative values', function () {
    $startTime = now()->addDays(1)->startOfHour();
    $endTime = now()->addDays(2)->startOfHour();

    $competition = [
        'name' => 'Test Competition',
        'description' => 'Test Description',
        'entry_fee' => '-50.00',
        'prize_pool' => '-1000.00',
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_users' => 100,
        'status' => 'upcoming'
    ];

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\CreateCompetition::class)
        ->fillForm($competition)
        ->call('create')
        ->assertHasErrors(['data.entry_fee', 'data.prize_pool']);

    $this->assertDatabaseMissing('competitions', [
        'name' => 'Test Competition'
    ]);
});

test('can delete competition', function () {
    $competition = Competition::factory()->create([
        'status' => 'upcoming',
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(2),
    ]);

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->assertActionVisible('delete')
        ->callAction('delete');

    $this->assertModelMissing($competition);
});

test('cannot delete active competition', function () {
    $competition = Competition::factory()->create([
        'status' => 'active',
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
    ]);

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->assertActionHidden('delete');

    $this->assertModelExists($competition);
});

test('can delete upcoming competition', function () {
    $competition = Competition::factory()->create([
        'status' => 'upcoming',
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(2),
    ]);

    Livewire::actingAs($this->user)
        ->test(CompetitionResource\Pages\EditCompetition::class, [
            'record' => $competition->id,
        ])
        ->callAction('delete');

    $this->assertDatabaseMissing('competitions', [
        'id' => $competition->id,
    ]);
});

test('cannot modify protected fields of active competition', function () {
    $competition = Competition::factory()->create([
        'status' => 'active',
        'entry_fee' => 50.00,
        'prize_pool' => 1000.00,
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
        'max_users' => 100
    ]);

    $updatedData = [
        'name' => 'Updated Name',
        'entry_fee' => 75.00,
        'prize_pool' => 1500.00,
        'max_users' => 200
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
        'prize_pool' => 1000.00,  // Should remain unchanged
        'max_users' => 100  // Should remain unchanged
    ]);
});