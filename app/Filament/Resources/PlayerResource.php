<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Players';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Player Information')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp_number')
                            ->required()
                            ->tel()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('nickname')
                            ->nullable(),
                    ]),
                Forms\Components\Section::make('Stats')
                    ->schema([
                        Forms\Components\TextInput::make('total_score')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('level')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('experience_points')
                            ->numeric()
                            ->default(0),
                    ]),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('joined_at')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->required()
                            ->default(now()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('experience_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('joined_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('generate_otp')
                    ->label('Generate OTP')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('purpose')
                            ->options([
                                'login' => 'Login',
                                'registration' => 'Registration',
                                'verification' => 'Verification',
                            ])
                            ->default('login')
                            ->required(),
                        Forms\Components\TextInput::make('length')
                            ->label('OTP Length')
                            ->default(6)
                            ->required()
                            ->numeric()
                            ->minValue(4)
                            ->maxValue(8),
                        Forms\Components\TextInput::make('expiry_minutes')
                            ->label('Expires after (minutes)')
                            ->default(10)
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60),
                    ])
                    ->action(function (Player $record, array $data): void {
                        $otp = $record->generateOtp(
                            $data['purpose'],
                            $data['length'],
                            $data['expiry_minutes']
                        );

                        // Show a notification with the OTP
                        \Filament\Notifications\Notification::make()
                            ->title('OTP Generated')
                            ->body("OTP: {$otp->otp_code}\nExpires at: {$otp->expires_at->format('Y-m-d H:i:s')}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
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
            PlayerResource\RelationManagers\OtpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'edit' => Pages\EditPlayer::route('/{record}/edit'),
            'view' => Pages\ViewPlayer::route('/{record}'),
        ];
    }
}