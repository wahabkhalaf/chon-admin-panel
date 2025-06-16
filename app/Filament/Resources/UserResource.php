<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->autofocus()
                    ->label('Name'),
                TextInput::make('email')
                    ->email()
                    ->autocomplete(false)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Email'),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin', // For secure login and high-level privileges
                        'player_manager' => 'Player Manager', // To manage user/player data
                        'competition_manager' => 'Competition Manager', // For competition & session management
                        'finance_manager' => 'Finance Manager', // For prize & transaction management
                        'reporting_manager' => 'Reporting Manager', // For reporting, analytics & notifications
                    ])
                    ->default('admin')
                    ->required()
                    ->label('Role'),
                TextInput::make('password')
                    ->password()
                    ->autocomplete(false)
                    ->label('Password')
                    ->dehydrateStateUsing(fn($state) => !empty($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->hint('Leave blank if not changing')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('email')
                    ->label('Email'),
                TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null) {
                            return '-';
                        }
                        return match ($state) {
                            'admin' => 'Admin',
                            'player_manager' => 'Player Manager',
                            'competition_manager' => 'Competition Manager',
                            'finance_manager' => 'Finance Manager',
                            'reporting_manager' => 'Reporting Manager',
                            default => $state,
                        };
                    })
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
