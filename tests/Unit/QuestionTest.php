<?php

namespace Tests\Unit;

use App\Models\Competition;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_question_types()
    {
        $this->assertEquals([
            'multi_choice' => 'Multiple Choice',
            'puzzle' => 'Puzzle',
            'pattern_recognition' => 'Pattern Recognition',
            'true_false' => 'True/False',
            'math' => 'Math Problem',
        ], Question::TYPES);
    }

    #[Test]
    public function it_has_correct_difficulty_levels()
    {
        $this->assertEquals([
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard',
        ], Question::LEVELS);
    }

    #[Test]
    public function it_casts_options_as_array()
    {
        $question = Question::factory()->create([
            'options' => [
                ['option' => 'Option 1', 'is_correct' => true],
                ['option' => 'Option 2', 'is_correct' => false],
            ]
        ]);

        $this->assertIsArray($question->options);
        $this->assertCount(2, $question->options);
    }

    #[Test]
    public function it_defaults_options_to_empty_array_when_creating()
    {
        $question = Question::factory()->create([
            'options' => null,
        ]);

        $this->assertIsArray($question->options);
        $this->assertEmpty($question->options);
    }

    #[Test]
    public function it_can_determine_if_question_is_new()
    {
        $oldQuestion = Question::factory()->create([
            'created_at' => now()->subDays(10)
        ]);

        $newQuestion = Question::factory()->create([
            'created_at' => now()->subDays(3)
        ]);

        $this->assertFalse($oldQuestion->isNew());
        $this->assertTrue($newQuestion->isNew());
    }

    #[Test]
    public function it_can_count_competitions()
    {
        $question = Question::factory()->create();

        $this->assertEquals(0, $question->competitions_count);

        // Attach to competitions
        $competitions = Competition::factory()->count(3)->create();
        foreach ($competitions as $competition) {
            $question->competitions()->attach($competition->id);
        }

        // Refresh the model to update the count
        $question->refresh();

        $this->assertEquals(3, $question->competitions_count);
    }

    #[Test]
    public function it_can_get_upcoming_competitions()
    {
        $question = Question::factory()->create();

        // Create competitions in different states
        $upcomingCompetition1 = Competition::factory()->state([
            'open_time' => now()->addDays(5),
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(15),
        ])->create();

        $upcomingCompetition2 = Competition::factory()->state([
            'open_time' => now()->addDays(7),
            'start_time' => now()->addDays(12),
            'end_time' => now()->addDays(17),
        ])->create();

        $activeCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(5),
            'start_time' => now()->subDays(2),
            'end_time' => now()->addDays(3),
        ])->create();

        // Attach the question to all competitions
        $question->competitions()->attach([
            $upcomingCompetition1->id,
            $upcomingCompetition2->id,
            $activeCompetition->id
        ]);

        $upcomingCompetitions = $question->upcoming_competitions;

        $this->assertCount(2, $upcomingCompetitions);
        $this->assertTrue($upcomingCompetitions->contains($upcomingCompetition1));
        $this->assertTrue($upcomingCompetitions->contains($upcomingCompetition2));
        $this->assertFalse($upcomingCompetitions->contains($activeCompetition));
    }

    #[Test]
    public function it_determines_if_question_can_be_edited_when_not_in_competition()
    {
        $question = Question::factory()->create();

        $this->assertTrue($question->canEdit());
    }

    #[Test]
    public function it_determines_if_question_can_be_edited_when_in_active_competition()
    {
        $question = Question::factory()->create();

        // Create an active competition
        $activeCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(5),
            'start_time' => now()->subDays(2),
            'end_time' => now()->addDays(3),
        ])->create();

        // Attach the question to the active competition
        $question->competitions()->attach($activeCompetition->id);

        $this->assertFalse($question->canEdit());
    }

    #[Test]
    public function it_determines_if_question_can_be_edited_when_in_open_competition()
    {
        $question = Question::factory()->create();

        // Create a competition open for registration
        $openCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(2),
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(8),
        ])->create();

        // Attach the question to the open competition
        $question->competitions()->attach($openCompetition->id);

        $this->assertFalse($question->canEdit());
    }

    #[Test]
    public function it_determines_if_question_can_be_edited_when_in_upcoming_competition()
    {
        $question = Question::factory()->create();

        // Create an upcoming competition
        $upcomingCompetition = Competition::factory()->state([
            'open_time' => now()->addDays(5),
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(15),
        ])->create();

        // Attach the question to the upcoming competition
        $question->competitions()->attach($upcomingCompetition->id);

        $this->assertTrue($question->canEdit());
    }

    #[Test]
    public function it_determines_if_question_can_be_edited_when_in_mixed_competitions()
    {
        $question = Question::factory()->create();

        // Create competitions in different states
        $upcomingCompetition = Competition::factory()->state([
            'open_time' => now()->addDays(5),
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(15),
        ])->create();

        $activeCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(5),
            'start_time' => now()->subDays(2),
            'end_time' => now()->addDays(3),
        ])->create();

        // Attach the question to both competitions
        $question->competitions()->attach([
            $upcomingCompetition->id,
            $activeCompetition->id
        ]);

        // Should not be editable because it's in an active competition
        $this->assertFalse($question->canEdit());
    }
}