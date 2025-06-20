# Kurdish Language Support

This document explains how to use the Kurdish language functionality that has been added to the system.

## Overview

The system now supports Kurdish language translations for:

-   Questions (question text, options, and correct answers)
-   Competitions (names and descriptions)
-   Payment Methods (names and instructions)

## Database Changes

The following columns have been added to support Kurdish translations:

### Questions Table

-   `question_text_kurdish` - Kurdish version of the question text
-   `options_kurdish` - Kurdish version of multiple choice options (JSON)
-   `correct_answer_kurdish` - Kurdish version of the correct answer

### Competitions Table

-   `name_kurdish` - Kurdish version of the competition name
-   `description_kurdish` - Kurdish version of the competition description

### Payment Methods Table

-   `name_kurdish` - Kurdish version of the payment method name
-   `instructions_kurdish` - Kurdish version of the payment instructions

## Models and Traits

### HasKurdishTranslation Trait

All models that support Kurdish translations use the `HasKurdishTranslation` trait, which provides the following methods:

```php
// Get content in specified language
$question->getQuestionText('ku'); // Kurdish
$question->getQuestionText('en'); // English (default)

$competition->getName('ku'); // Kurdish
$competition->getName('en'); // English (default)

$paymentMethod->getInstructions('ku'); // Kurdish
$paymentMethod->getInstructions('en'); // English (default)

// Check if Kurdish translation is available
$model->hasKurdishTranslation();

// Get available languages
$model->getAvailableLanguages(); // Returns ['en', 'ku'] if Kurdish is available
```

## Admin Panel Integration

### Filament Resources

The Filament admin panel has been updated to include Kurdish translation fields:

1. **QuestionResource**:

    - Collapsible "Kurdish Translation" section
    - Kurdish question text, options, and correct answer fields
    - Automatic handling of multiple choice options in Kurdish

2. **CompetitionResource**:

    - Collapsible "Kurdish Translation" section
    - Kurdish name and description fields

3. **PaymentMethodResource**:
    - Collapsible "Kurdish Translation" section
    - Kurdish name and instructions fields

### Usage in Admin Panel

1. Navigate to any resource (Questions, Competitions, or Payment Methods)
2. Create or edit a record
3. Expand the "Kurdish Translation" section
4. Fill in the Kurdish translations
5. Save the record

## API Endpoints

The system provides API endpoints to retrieve content in different languages:

### Get Questions in Specific Language

```
GET /api/language/questions?language=ku
GET /api/language/questions?language=en
```

### Get Competitions in Specific Language

```
GET /api/language/competitions?language=ku
GET /api/language/competitions?language=en
```

### Get Payment Methods in Specific Language

```
GET /api/language/payment-methods?language=ku
GET /api/language/payment-methods?language=en
```

### Get Available Languages

```
GET /api/language/available-languages
```

## Example API Response

```json
{
    "success": true,
    "language": "ku",
    "data": [
        {
            "id": 1,
            "question_text": "پرسیارێک لە کوردی: What is 2+2?",
            "options": [
                { "option": "بژاردەی کوردی: 3", "is_correct": false },
                { "option": "بژاردەی کوردی: 4", "is_correct": true },
                { "option": "بژاردەی کوردی: 5", "is_correct": false }
            ],
            "correct_answer": "وەڵامی کوردی: 4",
            "question_type": "multi_choice",
            "level": "easy",
            "has_kurdish": true,
            "available_languages": ["en", "ku"]
        }
    ]
}
```

## Seeding Sample Data

A seeder has been created to add sample Kurdish translations:

```bash
php artisan db:seed --class=KurdishLanguageSeeder
```

This will add Kurdish translations to existing questions, competitions, and payment methods.

## Best Practices

1. **Always provide English content first** - Kurdish translations are optional
2. **Use consistent terminology** - Maintain consistent Kurdish terms across the application
3. **Test both languages** - Ensure the application works correctly with both English and Kurdish content
4. **Consider RTL support** - Kurdish text may require right-to-left (RTL) layout support in the frontend

## Future Enhancements

Potential future improvements:

-   Add support for more languages
-   Implement automatic translation services
-   Add language preference settings for users
-   Create language-specific validation rules
-   Add language switching UI components

## Troubleshooting

### Common Issues

1. **Kurdish text not displaying correctly**:

    - Ensure proper UTF-8 encoding
    - Check if the database supports Unicode characters
    - Verify font support for Kurdish characters

2. **Translation fields not appearing in admin panel**:

    - Clear application cache: `php artisan cache:clear`
    - Clear config cache: `php artisan config:clear`

3. **API returning English content when Kurdish is requested**:
    - Check if Kurdish translations exist in the database
    - Verify the language parameter is set to 'ku'
    - Ensure the model has the `HasKurdishTranslation` trait

### Support

For issues related to Kurdish language support, check:

1. Database migration status
2. Model trait implementation
3. Filament resource configuration
4. API endpoint configuration
