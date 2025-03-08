# Competition Module Documentation

## Overview

The Competition module manages gaming competitions with features for creating, updating, and managing competition lifecycle states. The system supports a complete competition lifecycle from registration to completion.

## Model: Competition

### Properties

-   `id`: Auto-incrementing ID
-   `name`: Competition name (string, max 100 chars)
-   `description`: Optional description (text)
-   `entry_fee`: Participation fee (decimal)
-   `open_time`: Registration opening datetime
-   `start_time`: Competition start datetime
-   `end_time`: Competition end datetime
-   `max_users`: Maximum participant limit (integer)
-   `game_type`: Type of game (action, strategy, puzzle, racing, sports, rpg, other)

### Status Flow

The status is dynamically calculated based on time:

```
upcoming -> open -> active -> completed
```

-   **Upcoming**: Before registration opens (`current < open_time`)
-   **Open**: Registration period (`open_time <= current < start_time`)
-   **Active**: Competition is running (`start_time <= current < end_time`)
-   **Completed**: Competition has ended (`current >= end_time`)

### Model Methods

```php
// Get the current status based on time
public function getStatus(): string

// Check if competition is open for registration
public function isOpen(): bool

// Check if competition is active
public function isActive(): bool

// Check if competition is upcoming
public function isUpcoming(): bool

// Check if competition is completed
public function isCompleted(): bool

// Check if competition can be deleted
public function canDelete(): bool

// Check if specific field can be edited
public function canEditField(string $field): bool

// Check if users can register for this competition
public function canRegister(): bool

// Get current participant count
public function getCurrentParticipants(): int
```

## Filament Resource

### Available Pages

-   List Competitions (`/admin/competitions`)
-   Create Competition (`/admin/competitions/create`)
-   Edit Competition (`/admin/competitions/{record}/edit`)

### Form Sections

1. Basic Information

    - Name
    - Description
    - Game Type

2. Financial Details

    - Entry Fee

3. Time & Capacity
    - Registration Opens (open_time)
    - Competition Starts (start_time)
    - Competition Ends (end_time)
    - Max Users

### Business Rules

#### Creation

-   Registration opening time must be in the future
-   Start time must be after registration opening time
-   End time must be after start time
-   Entry fee cannot be negative
-   Max users must be at least 1

#### Editing

-   Only competitions in 'upcoming' status can be edited
-   For all other statuses (open, active, completed), no fields can be edited
-   The form displays a clear read-only indicator for non-editable competitions

#### Deletion

Competitions can be deleted only if:

-   Status is 'upcoming'
-   Competition registration hasn't opened yet

## Validation

The module includes multi-layered validation:

1. **Form-Level Validation**

    - Live validation as users type
    - Helper text explaining constraints
    - Automatic field clearing when dependencies change

2. **Controller-Level Validation**

    - Validation in beforeCreate and beforeValidate methods
    - InvalidArgumentException for error handling

3. **Model-Level Validation**
    - Validation in the boot method
    - Consistent error messages across all levels

## Testing

The module includes comprehensive tests covering:

1. CRUD Operations

```php
test('can view competition list')
test('can create competition')
test('can delete upcoming competition')
```

2. Validation Rules

```php
test('cannot create competition with invalid dates')
test('cannot create competition with start time before open time')
test('validates negative values')
```

3. Business Logic

```php
test('cannot delete active competition')
test('cannot delete open for registration competition')
test('cannot modify fields of non-upcoming competition')
test('can view competition status based on time')
```

## Example Usage

### Creating a Competition

```php
use App\Models\Competition;

$competition = Competition::create([
    'name' => 'Weekend Tournament',
    'description' => 'Weekend gaming tournament',
    'entry_fee' => 50.00,
    'open_time' => now()->addDays(1),
    'start_time' => now()->addDays(2),
    'end_time' => now()->addDays(3),
    'max_users' => 100,
    'game_type' => 'action'
]);
```

### Managing Competition Status

```php
// Get the current status
$status = $competition->getStatus();

// Check if registration is open
if ($competition->isOpen()) {
    // Allow users to register
}

// Check if competition can be modified
if ($competition->canEditField('name')) {
    $competition->update(['name' => 'Updated Tournament Name']);
}

// Check if competition can be deleted
if ($competition->canDelete()) {
    $competition->delete();
}
```

### User Registration

```php
// Check if a user can register for the competition
if ($competition->canRegister()) {
    // Register the user
    $user->competitions()->attach($competition->id);
}

// Check if a user is already registered
if ($user->isRegisteredFor($competition)) {
    // User is already registered
}
```

## Error Handling

The module includes built-in error handling for:

-   Invalid date ranges
-   Negative financial values
-   Protected field modifications
-   Unauthorized deletion attempts

Error notifications are displayed using Filament's notification system with color-coded indicators:

-   Yellow for warnings
-   Blue for information
-   Green for success
-   Red for errors

## Timezone Handling

The application uses the Asia/Baghdad timezone for all date and time operations. This ensures consistent date handling across the application.
