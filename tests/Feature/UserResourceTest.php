<?php

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

beforeEach(function() {
    $this->actingAs(User::factory()->create(['role' => 'player_manager']));
});

it('can render post titles', function () {
    User::factory()->count(10)->create();
    livewire(UserResource\Pages\ListUsers::class)
        ->assertCanRenderTableColumn('name');
});
test('can list users', function () {
    $users = User::factory()->count(3)->create();
    
    livewire(UserResource\Pages\ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

test('can create user', function () {
    $newUserData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'player_manager',
        'password' => 'password123'
    ];

    livewire(UserResource\Pages\CreateUser::class)
        ->fillForm($newUserData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'name' => $newUserData['name'],
        'email' => $newUserData['email'],
        'role' => $newUserData['role'],
    ]);
});

test('can update user', function () {
    $user = User::factory()->create();
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'competition_manager',
    ];

    livewire(UserResource\Pages\EditUser::class, [
        'record' => $user->id,
    ])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => $updateData['name'],
        'email' => $updateData['email'],
        'role' => $updateData['role'],
    ]);
});

test('can delete user', function () {
    $user = User::factory()->create();

    livewire(UserResource\Pages\ListUsers::class)
        ->callTableAction('delete', $user);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id
    ]);
});

test('validates required fields when creating user', function () {
    livewire(UserResource\Pages\CreateUser::class)
        ->fillForm([
            'name' => '',
            'email' => '',
            'role' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'role' => 'required',
        ]);
});

test('validates email format', function () {
    livewire(UserResource\Pages\CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'role' => 'admin',
            'password' => 'password123'
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'email']);
});
