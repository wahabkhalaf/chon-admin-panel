<?php

namespace Tests\Unit;

use App\Models\Competition;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionCompetitionRelationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_question_can_belong_to_many_competitions()
    {
        $question = Question::factory()->create();
        $competitions = Competition::factory()->count(3)->create();

        $question->competitions()->attach($competitions->pluck('id'));

        $this->assertCount(3, $question->competitions);

        foreach ($competitions as $competition) {
            $this->assertTrue($question->competitions->contains($competition));
        }
    }

    #[Test]
    public function a_competition_can_have_many_questions()
    {
        $competition = Competition::factory()->create();
        $questions = Question::factory()->count(5)->create();

        $competition->questions()->attach($questions->pluck('id'));

        $this->assertCount(5, $competition->questions);

        foreach ($questions as $question) {
            $this->assertTrue($competition->questions->contains($question));
        }
    }

    #[Test]
    public function removing_a_question_from_competition_updates_relationship()
    {
        $question = Question::factory()->create();
        $competitions = Competition::factory()->count(3)->create();

        // Attach to all competitions
        $question->competitions()->attach($competitions->pluck('id'));
        $this->assertCount(3, $question->competitions);

        // Detach from one competition
        $question->competitions()->detach($competitions->first()->id);
        $question->refresh();

        $this->assertCount(2, $question->competitions);
        $this->assertFalse($question->competitions->contains($competitions->first()));
    }

    #[Test]
    public function removing_a_competition_detaches_questions()
    {
        $question = Question::factory()->create();
        $competition = Competition::factory()->create();

        // Attach the question to the competition
        $question->competitions()->attach($competition->id);
        $this->assertTrue($question->competitions->contains($competition));

        // Delete the competition
        $competition->delete();
        $question->refresh();

        // The relationship should be empty
        $this->assertCount(0, $question->competitions);
    }

    #[Test]
    public function competitions_questions_pivot_table_has_timestamps()
    {
        $question = Question::factory()->create();
        $competition = Competition::factory()->create();

        // Attach the question to the competition
        $question->competitions()->attach($competition->id);

        // Get the pivot record
        $pivot = $question->competitions()->first()->pivot;

        // Check that timestamps exist
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    #[Test]
    public function can_sync_questions_to_competition()
    {
        $competition = Competition::factory()->create();
        $questions = Question::factory()->count(5)->create();

        // Attach all questions
        $competition->questions()->attach($questions->pluck('id'));
        $this->assertCount(5, $competition->questions);

        // Now sync with only 2 questions
        $newQuestionIds = $questions->take(2)->pluck('id')->toArray();
        $competition->questions()->sync($newQuestionIds);
        $competition->refresh();

        // Should only have 2 questions now
        $this->assertCount(2, $competition->questions);

        // Only the first 2 questions should be attached
        foreach ($questions->take(2) as $question) {
            $this->assertTrue($competition->questions->contains($question));
        }

        // The rest should be detached
        foreach ($questions->skip(2) as $question) {
            $this->assertFalse($competition->questions->contains($question));
        }
    }

    #[Test]
    public function competition_status_affects_question_edit_permissions()
    {
        // Create a question
        $question = Question::factory()->create();

        // Create competitions in different states
        $upcomingCompetition = Competition::factory()->state([
            'open_time' => now()->addDays(5),
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(15),
        ])->create();

        $openCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(2),
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(8),
        ])->create();

        $activeCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(5),
            'start_time' => now()->subDays(2),
            'end_time' => now()->addDays(3),
        ])->create();

        $completedCompetition = Competition::factory()->state([
            'open_time' => now()->subDays(15),
            'start_time' => now()->subDays(10),
            'end_time' => now()->subDays(5),
        ])->create();

        // Initially the question can be edited
        $this->assertTrue($question->canEdit());

        // Attach to an upcoming competition - should still be editable
        $question->competitions()->attach($upcomingCompetition->id);
        $question->refresh();
        $this->assertTrue($question->canEdit());

        // Attach to an open competition - should not be editable
        $question->competitions()->attach($openCompetition->id);
        $question->refresh();
        $this->assertFalse($question->canEdit());

        // Detach from the open competition
        $question->competitions()->detach($openCompetition->id);
        $question->refresh();
        $this->assertTrue($question->canEdit());

        // Attach to an active competition - should not be editable
        $question->competitions()->attach($activeCompetition->id);
        $question->refresh();
        $this->assertFalse($question->canEdit());

        // Detach from the active competition
        $question->competitions()->detach($activeCompetition->id);
        $question->refresh();
        $this->assertTrue($question->canEdit());

        // Attach to a completed competition - should be editable again
        $question->competitions()->attach($completedCompetition->id);
        $question->refresh();
        $this->assertTrue($question->canEdit());
    }
}