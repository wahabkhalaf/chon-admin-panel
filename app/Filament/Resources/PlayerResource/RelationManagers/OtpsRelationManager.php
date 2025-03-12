<?php

namespace App\Filament\Resources\PlayerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OtpsRelationManager extends RelationManager
{
    protected static string $relationship = 'otps';

    protected static ?string $recordTitleAttribute = 'otp_code';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('otp_code')
                    ->required()
                    ->maxLength(8),
                Forms\Components\Select::make('purpose')
                    ->options([
                        'login' => 'Login',
                        'registration' => 'Registration',
                        'verification' => 'Verification',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_verified')
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('otp_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'login' => 'primary',
                        'registration' => 'success',
                        'verification' => 'warning',
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('purpose')
                    ->options([
                        'login' => 'Login',
                        'registration' => 'Registration',
                        'verification' => 'Verification',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
                Tables\Filters\TernaryFilter::make('is_expired')
                    ->label('Expired')
                    ->queries(
                        true: fn(Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn(Builder $query) => $query->where('expires_at', '>=', now()),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}