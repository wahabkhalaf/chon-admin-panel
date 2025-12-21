<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointsPackageResource\Pages;
use App\Models\PointsPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PointsPackageResource extends Resource
{
    protected static ?string $model = PointsPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Points Packages';

    protected static ?string $navigationGroup = 'Points Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Points Package';

    protected static ?string $pluralModelLabel = 'Points Packages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Premium Package'),
                        Forms\Components\Textarea::make('description')
                            ->nullable()
                            ->rows(3)
                            ->placeholder('Optional description for the package'),
                    ]),
                Forms\Components\Section::make('Points & Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('points_amount')
                            ->label('Points Amount')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('points'),
                        Forms\Components\TextInput::make('price_iqd')
                            ->label('Price (IQD)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('IQD'),
                    ])->columns(2),
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active packages will be shown to players'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_amount')
                    ->label('Points')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('price_iqd')
                    ->label('Price (IQD)')
                    ->numeric()
                    ->sortable()
                    ->money('IQD', locale: 'en'),
                Tables\Columns\TextColumn::make('price_per_point')
                    ->label('IQD/Point')
                    ->getStateUsing(fn (PointsPackage $record): string => 
                        number_format($record->price_per_point, 2)
                    ),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['active' => false]))
                        ->requiresConfirmation(),
                ]),
            ]);
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
            'index' => Pages\ListPointsPackages::route('/'),
            'create' => Pages\CreatePointsPackage::route('/create'),
            'edit' => Pages\EditPointsPackage::route('/{record}/edit'),
        ];
    }
}
