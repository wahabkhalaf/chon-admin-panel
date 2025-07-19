# Question Module Documentation

## Overview

The Question module manages quiz and test questions used in competitions. It provides features for creating, updating, and managing questions with various question types and difficulty levels. Questions can be attached to competitions but with restrictions on editing once they are part of active competitions.

## Model: Question

### Properties
-   `id`: Auto-incrementing ID
-   `question_text`: The actual question being asked (text)
-   `question_type`: Type of question (multi_choice, puzzle, pattern_recognition, true_false, math)
-   `options`: Array of options for multiple-choice questions (JSON)
-   `correct_answer`: The correct answer to the question (string)
-   `level`: Difficulty level (easy, medium, hard)
-   `created_at`: Timestamp when question was created
-   `updated_at`: Timestamp when question was last updated

### Question Types

```php
// Available question types
public const TYPES = [
    'multi_choice' => 'Multiple Choice',
    'puzzle' => 'Puzzle',
    'pattern_recognition' => 'Pattern Recognition',
    'true_false' => 'True/False',
    'math' => 'Math Problem',
];
```

### Difficulty Levels

```php
// Available difficulty levels
public const LEVELS = [
    'easy' => 'Easy',
    'medium' => 'Medium',
    'hard' => 'Hard',
];
```

### Relationships

```php
// Questions belong to many competitions
public function competitions(): BelongsToMany
{
    return $this->belongsToMany(Competition::class, 'competitions_questions')
        ->withTimestamps();
}
```

### Model Methods

```php
// Check if the question is new (less than 7 days old)
public function isNew(): bool

// Count competitions this question is attached to
public function getCompetitionsCountAttribute(): int

// Get all upcoming competitions this question is attached to
public function getUpcomingCompetitionsAttribute()

// Determine if the question can be edited
public function canEdit(): bool
```

## Filament Resource

### Available Pages

-   List Questions (`/admin/questions`)
-   Create Question (`/admin/questions/create`)
-   Edit Question (`/admin/questions/{record}/edit`)

### Form Sections

1. Question Details

    - Question Text (question_text)
    - Question Type (question_type)
    - Difficulty Level (level)
    - Answer Options (varies based on question type)

### Question Type Specific Fields

Each question type has specific form fields:

#### Multiple Choice Questions

-   Options Repeater with:
    -   Option text
    -   Is Correct toggle

#### Puzzle Questions

-   Correct Answer text field

#### Pattern Recognition Questions

-   Correct Pattern Answer text field

#### True/False Questions

-   True/False selector

#### Math Problems

-   Correct Math Answer numeric field

### Business Rules

#### Creation

-   All questions require question text, question type, and a difficulty level
-   Multiple choice questions require at least one option marked as correct
-   Each question type requires its specific answer format
-   The correct answer is automatically extracted from the marked option in multi-choice questions

#### Editing

-   Questions can only be edited if they are not part of any active competitions
-   Questions can only be edited if they are not part of competitions open for registration
-   Questions that are locked (used in active competitions) cannot be modified
-   For multi-choice questions, the correct answer is automatically updated when options are modified

#### Deletion

Questions can be deleted only if:

-   They are not part of any active competitions
-   They are not part of competitions open for registration

#### Multi-Choice Question Behavior

-   Only one option can be marked as correct at a time
-   When an option is marked as correct, all other options are automatically unchecked
-   At least one option must always be marked as correct
-   The correct answer field is automatically populated from the marked option
-   Both English and Kurdish versions maintain separate correct answer tracking

## Table Display

The Question resource table displays:

-   Question text (truncated to 50 characters)
-   Question type (color-coded badge)
-   Difficulty level (color-coded badge)
-   Competition usage count
-   Creation date

## Filters

The resource provides filtering by:

-   Question type
-   Difficulty level
-   New questions (created in the last 7 days)
-   Unused questions (not attached to any competitions)

## Validation

The module includes validation at multiple levels:

1. **Form-Level Validation**

    - Required fields checks
    - Question type-specific validation
    - Dynamic field visibility based on question type

2. **Model-Level Validation**
    - Boot method ensures options is always an array
    - Relationship integrity checks

## Testing

The module includes tests covering:

1. CRUD Operations

    ```php
    test('can view question list')
    test('can create different types of questions')
    test('can view question details')
    ```

2. Validation Rules

    ```php
    test('validates required fields')
    test('validates question type specific fields')
    ```

3. Business Logic
    ```php
    test('cannot edit question used in active competition')
    test('cannot delete question used in active competition')
    test('can detect new questions')
    ```

## Example Usage

### Creating a Question

```php
use App\Models\Question;

// Creating a multiple choice question
$multiChoiceQuestion = Question::create([
    'question_text' => 'What is the capital of France?',
    'question_type' => 'multi_choice',
    'options' => [
        ['option' => 'Paris', 'is_correct' => true],
        ['option' => 'London', 'is_correct' => false],
        ['option' => 'Berlin', 'is_correct' => false],
        ['option' => 'Madrid', 'is_correct' => false],
    ],
    'correct_answer' => 'Paris',
    'level' => 'easy',
]);

// Creating a true/false question
$trueFalseQuestion = Question::create([
    'question_text' => 'The sky is blue.',
    'question_type' => 'true_false',
    'correct_answer' => 'true',
    'level' => 'easy',
]);

// Creating a math problem
$mathQuestion = Question::create([
    'question_text' => 'What is 5 + 7?',
    'question_type' => 'math',
    'correct_answer' => '12',
    'level' => 'easy',
]);
```

### Checking Question Status

```php
// Check if a question can be edited
if ($question->canEdit()) {
    // Update the question
    $question->update([
        'question_text' => 'Updated question text'
    ]);
}

// Check if a question is new
if ($question->isNew()) {
    // Question was created in the last 7 days
}

// Get the number of competitions using this question
$competitionsCount = $question->competitions_count;

// Get all upcoming competitions using this question
$upcomingCompetitions = $question->upcoming_competitions;
```

## Error Handling

The module includes built-in error handling for:

-   Attempting to edit locked questions
-   Attempting to delete questions in use
-   Invalid question type formats
-   Bulk actions on protected questions

Error notifications are displayed using Filament's notification system with descriptive messages and color-coded indicators.

## View Components

The module includes a detailed question view component at:
`resources/views/filament/resources/question-resource/question-detail.blade.php`

This view provides a formatted display of question details, including:

-   Question text
-   Question type
-   Difficulty level
-   Answer options (for multiple choice)
-   Correct answer
-   Usage in competitions

## Troubleshooting

### Common Issues

#### "Correct answer field is required" Error

**Problem**: When creating or editing a multi-choice question, you get a validation error saying the correct answer field is required, even though you've marked an option as correct.

**Cause**: This typically happens when there's a disconnect between the form's state management and the validation system. The `is_correct` toggle in the options repeater isn't properly updating the hidden `correct_answer` field.

**Solution**: 
1. Ensure you've marked at least one option as correct using the toggle
2. Try refreshing the page and re-entering the data
3. Check that the question type is set to "Multi-choice"
4. Verify that both English and Kurdish options (if provided) have at least one correct answer marked

**Technical Details**: The form uses multiple validation layers:
- Form-level validation with `afterStateUpdated` callbacks
- Page-level validation in `CreateQuestion` and `EditQuestion` classes
- Hidden field `dehydrateStateUsing` for final data processing

If the issue persists, it may indicate a JavaScript error or form state corruption. Try clearing your browser cache or using a different browser.

#### Options Not Saving Correctly

**Problem**: Options are not being saved or are being reset when the form is submitted.

**Solution**:
1. Ensure all required fields are filled
2. Check that the question type is correctly selected
3. Verify that at least one option is marked as correct
4. Make sure the form is not being interrupted by validation errors

#### Kurdish Translation Issues

**Problem**: Kurdish translations are not being saved or are causing validation errors.

**Solution**:
1. Kurdish translations are optional - you can leave them empty
2. If you provide Kurdish options, ensure at least one is marked as correct
3. Check that the Kurdish question text is provided if you're adding Kurdish options
