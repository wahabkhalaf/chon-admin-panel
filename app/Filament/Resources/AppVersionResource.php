<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppVersionResource\Pages;
use App\Models\AppVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'App Management';

    protected static ?string $navigationLabel = 'App Versions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Version Information')
                    ->schema([
                        Forms\Components\Select::make('platform')
                            ->options([
                                'android' => 'Android',
                                'ios' => 'iOS',
                            ])
                            ->required()
                            ->searchable()
                            ->placeholder('Select platform'),

                        Forms\Components\TextInput::make('version')
                            ->label('Version Number')
                            ->placeholder('e.g., 1.1.0')
                            ->required()
                            ->maxLength(20)
                            ->helperText('Semantic version number (e.g., 1.1.0, 2.0.0)'),

                        Forms\Components\TextInput::make('build_number')
                            ->label('Build Number')
                            ->placeholder('e.g., 10')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Integer build number for version comparison'),

                        Forms\Components\TextInput::make('app_store_url')
                            ->label('Store URL')
                            ->placeholder('https://play.google.com/store/apps/details?id=...')
                            ->url()
                            ->maxLength(500)
                            ->helperText('App Store or Google Play Store URL'),

                        Forms\Components\Textarea::make('release_notes')
                            ->label('Release Notes')
                            ->placeholder('What\'s new in this version...')
                            ->maxLength(1000)
                            ->rows(4)
                            ->helperText('Describe new features, bug fixes, and improvements'),

                        Forms\Components\DateTimePicker::make('released_at')
                            ->label('Release Date')
                            ->placeholder('When this version was released')
                            ->helperText('Optional: Set the release date for this version'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Update Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_force_update')
                            ->label('Force Update')
                            ->helperText('Users must update to this version to continue using the app')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Version')
                            ->helperText('Enable this version for update checks')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'android' => 'success',
                        'ios' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('build_number')
                    ->label('Build')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('released_at')
                    ->label('Released')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_force_update')
                    ->label('Force Update')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'android' => 'Android',
                        'ios' => 'iOS',
                    ])
                    ->label('Platform'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\TernaryFilter::make('is_force_update')
                    ->label('Force Update Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_store')
                    ->label('View Store')
                    ->icon('heroicon-o-external-link')
                    ->url(fn (AppVersion $record): string => $record->app_store_url ?? '#')
                    ->openUrlInNewTab()
                    ->visible(fn (AppVersion $record): bool => !empty($record->app_store_url))
                    ->color('info'),

                Tables\Actions\Action::make('toggle_active')
                    ->label('Toggle Active')
                    ->icon('heroicon-o-power')
                    ->color(fn (AppVersion $record): string => $record->is_active ? 'warning' : 'success')
                    ->action(function (AppVersion $record): void {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Status Updated')
                            ->body("Version {$record->version} is now " . ($record->is_active ? 'active' : 'inactive'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Toggle Version Status')
                    ->modalDescription(fn (AppVersion $record): string => 
                        "Are you sure you want to " . ($record->is_active ? 'deactivate' : 'activate') . " version {$record->version}?"
                    )
                    ->modalSubmitActionLabel('Yes, update status'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Versions Activated')
                                ->body("{$records->count()} version(s) have been activated.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Activate Versions')
                        ->modalDescription('Are you sure you want to activate the selected versions?')
                        ->modalSubmitActionLabel('Yes, activate'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Versions Deactivated')
                                ->body("{$records->count()} version(s) have been deactivated.")
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Versions')
                        ->modalDescription('Are you sure you want to deactivate the selected versions?')
                        ->modalSubmitActionLabel('Yes, deactivate'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('platform', 'asc')
            ->defaultSort('build_number', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppVersions::route('/'),
            'create' => Pages\CreateAppVersion::route('/create'),
            'edit' => Pages\EditAppVersion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('platform')
            ->orderBy('build_number', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 ? 'success' : 'warning';
    }
}
