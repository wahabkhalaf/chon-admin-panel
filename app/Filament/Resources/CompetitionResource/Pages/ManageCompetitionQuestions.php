<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Models\Competition;
use App\Models\Question;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ManageCompetitionQuestions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CompetitionResource::class;

    protected static string $view = 'filament.resources.competition-resource.pages.manage-competition-questions';

    public Competition $record;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('attach_questions')
                ->label('Attach Questions')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn() => $this->record->isUpcoming())
                ->form([
                    Forms\Components\Select::make('questions')
                        ->label('Questions')
                        ->multiple()
                        ->options(function () {
                            // Get questions that are not already attached to this competition
                            $existingQuestionIds = $this->record->questions()->pluck('questions.id')->toArray();
                            return Question::whereNotIn('id', $existingQuestionIds)
                                ->get()
                                ->pluck('question_text', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Attach selected questions to the competition
                    $attachedCount = 0;
                    foreach ($data['questions'] as $questionId) {
                        // Check if question is already attached (double check)
                        if (!$this->record->questions()->where('questions.id', $questionId)->exists()) {
                            $this->record->questions()->attach($questionId);
                            $attachedCount++;
                        }
                    }

                    Notification::make()
                        ->title($attachedCount . ' questions attached successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('back')
                ->label('Back to Competition')
                ->url(fn() => CompetitionResource::getUrl('edit', ['record' => $this->record]))
                ->color('secondary'),
        ];
    }

    public function getTitle(): string
    {
        return 'Manage Questions for: ' . $this->record->name;
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Add any additional stats or charts here if needed
        ];
    }

    protected function getTableQuery(): Builder
    {
        return $this->record->questions()->getQuery();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('question_text')
                ->label('Question')
                ->searchable()
                ->limit(50),

            Tables\Columns\TextColumn::make('question_type')
                ->badge()
                ->formatStateUsing(fn(string $state): string => Question::TYPES[$state] ?? $state)
                ->colors([
                    'primary' => fn(string $state): bool => $state === 'multi_choice',
                    'success' => fn(string $state): bool => $state === 'true_false',
                    'warning' => fn(string $state): bool => $state === 'puzzle',
                    'danger' => fn(string $state): bool => $state === 'math',
                    'gray' => fn(string $state): bool => $state === 'pattern_recognition',
                ]),

            Tables\Columns\TextColumn::make('level')
                ->badge()
                ->label('Difficulty')
                ->formatStateUsing(fn(string $state): string => ucfirst($state))
                ->colors([
                    'success' => fn(string $state): bool => $state === 'easy',
                    'warning' => fn(string $state): bool => $state === 'medium',
                    'danger' => fn(string $state): bool => $state === 'hard',
                ]),

            Tables\Columns\TextColumn::make('correct_answer')
                ->label('Answer')
                ->limit(20),

            Tables\Columns\TextColumn::make('age')
                ->label('Age')
                ->badge()
                ->getStateUsing(function ($record) {
                    return $record->created_at->diffInDays(now()) < 7 ? 'new' : 'old';
                })
                ->colors([
                    'success' => 'new',
                    'secondary' => 'old',
                ]),

            Tables\Columns\TextColumn::make('competitions_count')
                ->label('Used In')
                ->getStateUsing(function ($record) {
                    return $record->competitions()->count();
                })
                ->badge()
                ->color('primary'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('question_type')
                ->options(Question::TYPES)
                ->label('Question Type'),

            Tables\Filters\SelectFilter::make('level')
                ->options(Question::LEVELS)
                ->label('Difficulty'),

            Tables\Filters\Filter::make('new_questions')
                ->label('New Questions')
                ->query(fn(Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->modalContent(fn(Question $record) => view('filament.resources.question-resource.question-detail', [
                    'question' => $record
                ])),

            Tables\Actions\DetachAction::make()
                ->visible(fn() => $this->record->isUpcoming()),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DetachBulkAction::make()
                    ->visible(fn() => $this->record->isUpcoming())
                    ->before(function (Tables\Actions\DetachBulkAction $action, array $data): void {
                        // Show notification about how many questions are being detached
                        $count = count($data['records']);

                        if (!$this->record->isUpcoming()) {
                            Notification::make()
                                ->title('Cannot detach questions')
                                ->body('Questions can only be detached from upcoming competitions.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }

                        Notification::make()
                            ->title("Detaching $count questions")
                            ->info()
                            ->send();
                    }),
            ]),
        ];
    }
}
