# Competition Module Documentation

## Overview
The Competition module manages gaming competitions with features for creating, updating, and managing competition lifecycle states.

## Model: Competition

### Properties
- `id`: Auto-incrementing ID
- `name`: Competition name (string, max 100 chars)
- `description`: Optional description (text)
- `entry_fee`: Participation fee (decimal)
- `prize_pool`: Total prize amount (decimal)
- `start_time`: Competition start datetime
- `end_time`: Competition end datetime
- `max_users`: Maximum participant limit (integer)
- `status`: Competition state (upcoming/active/completed/closed)

### Status Flow
```
upcoming -> active -> completed/closed
```

### Model Methods

```php
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
```

## Filament Resource

### Available Pages
- List Competitions (`/admin/competitions`)
- Create Competition (`/admin/competitions/create`)
- Edit Competition (`/admin/competitions/{record}/edit`)

### Form Sections

1. Basic Information
   - Name
   - Description

2. Financial Details
   - Entry Fee
   - Prize Pool

3. Time & Capacity
   - Start Time
   - End Time
   - Max Users

4. Status
   - Competition Status

### Business Rules

#### Creation
- End time must be after start time
- Entry fee and prize pool cannot be negative
- Status defaults to 'upcoming'

#### Editing
Protected fields when competition is active/completed:
- Entry Fee
- Prize Pool
- Start Time
- End Time
- Max Users

#### Deletion
Competitions can be deleted only if:
- Status is 'upcoming' or 'closed'
- Competition hasn't started yet

## Testing

The module includes comprehensive tests covering:

1. CRUD Operations
```php
test('can view competition list')
test('can create competition')
test('can update competition status')
test('can delete competition')
```

2. Validation Rules
```php
test('cannot create competition with invalid dates')
test('validates negative values')
```

3. Business Logic
```php
test('cannot delete active competition')
test('cannot modify protected fields of active competition')
```

## Example Usage

### Creating a Competition

```php
use App\Models\Competition;

$competition = Competition::create([
    'name' => 'Weekend Tournament',
    'description' => 'Weekend gaming tournament',
    'entry_fee' => 50.00,
    'prize_pool' => 1000.00,
    'start_time' => now()->addDays(1),
    'end_time' => now()->addDays(2),
    'max_users' => 100,
    'status' => 'upcoming'
]);
```

### Managing Competition Status

```php
// Check if competition can be modified
if ($competition->canEditField('prize_pool')) {
    $competition->update(['prize_pool' => 2000.00]);
}

// Check if competition can be deleted
if ($competition->canDelete()) {
    $competition->delete();
}
```

## Error Handling

The module includes built-in error handling for:
- Invalid date ranges
- Negative financial values
- Protected field modifications
- Unauthorized deletion attempts

Error notifications are displayed using Filament's notification system.
