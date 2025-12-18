<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\CompetitionQuestion;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $recordTitleAttribute = 'question_text';

    protected static ?string $title = 'Questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('question_id')
                    ->label('Question')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Question::where('question_text', 'like', "%{$search}%")
                        ->orWhere('question_text_arabic', 'like', "%{$search}%")
                        ->orWhere('question_text_kurdish', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('question_text', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => Question::find($value)?->question_text)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(function () {
                $competition = $this->getOwnerRecord();

                return new HtmlString(view('filament.resources.competition-resource.relation-managers.questions-header', [
                    'competition' => $competition,
                ]));
            })
            ->recordTitleAttribute('id')
            ->columns([
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
                // Tables\Columns\BadgeColumn::make('age')
                //     ->label('Freshness')
                //     ->getStateUsing(function ($record) {
                //         // Consider questions created within the last 7 days as new
                //         return $record->created_at->diffInDays(now()) < 7 ? 'new' : 'old';
                //     })
                //     ->colors([
                //         'success' => 'new',
                //         'secondary' => 'old',
                //     ]),

                Tables\Columns\TextColumn::make('competitions_count')
                    ->label('Used In')
                    ->getStateUsing(function ($record) {
                        return $record->competitions()->count();
                    })
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(Question::TYPES)
                    ->label('Question Type'),

                Tables\Filters\SelectFilter::make('level')
                    ->options(Question::LEVELS)
                    ->label('Difficulty'),

                Tables\Filters\Filter::make('new_questions')
                    ->label('New Questions')
                    ->query(fn(Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordTitle(fn(Question $record): string =>
                        $record->question_text . ' (' . Question::TYPES[$record->question_type] . ' - ' . ucfirst($record->level) . ')')
                    ->recordSelectSearchColumns(['question_text'])
                    ->form(fn(Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Select Questions')
                            ->helperText('Search by question text')
                            ->searchDebounce(500)
                            ->searchPrompt('Type to search questions...')
                            ->optionsLimit(50),
                    ])
                    ->multiple()
                    ->label('Attach Questions')
                    ->visible(function (): bool {
                        // Only allow attaching questions if competition is upcoming
                        return $this->getOwnerRecord()->isUpcoming();
                    })
                    ->before(function (Tables\Actions\AttachAction $action, array $data): void {
                        // Check if the question is already attached to avoid duplicates
                        $questionId = $data['recordId'];
                        $competition = $this->getOwnerRecord();

                        if ($competition->questions()->where('questions.id', $questionId)->exists()) {
                            Notification::make()
                                ->title('Question already attached')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->color('primary')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn(Question $record) => view('filament.resources.question-resource.question-detail', [
                        'question' => $record
                    ])),
                Tables\Actions\DetachAction::make()
                    ->visible(function (Model $record): bool {
                        // Only allow detaching questions if competition is upcoming
                        return $this->getOwnerRecord()->isUpcoming();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->visible(function (): bool {
                            // Only allow detaching questions if competition is upcoming
                            return $this->getOwnerRecord()->isUpcoming();
                        })
                        ->before(function (Collection $records): void {
                            // Show notification about how many questions are being detached
                            Notification::make()
                                ->title('Detaching ' . $records->count() . ' questions')
                                ->info()
                                ->send();
                        }),
                ]),
            ]);
    }
}