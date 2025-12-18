<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerPaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\PlayerPaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlayerPaymentMethodResource extends Resource
{
    protected static ?string $model = PlayerPaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Players';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Player & Payment Method')
                    ->schema([
                        Forms\Components\Select::make('player_id')
                            ->relationship('player', 'nickname')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('payment_method_id')
                            ->relationship('paymentMethod', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('nickname')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_default')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\TextInput::make('token')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('external_id')
                            ->maxLength(255),
                        Forms\Components\KeyValue::make('details')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['player:id,nickname', 'paymentMethod:id,name']))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('player.nickname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('player')
                    ->relationship('player', 'nickname')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_default'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('setAsDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(PlayerPaymentMethod $record) => !$record->is_default)
                    ->action(function (PlayerPaymentMethod $record) {
                        $record->setAsDefault();
                    }),
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
            'index' => Pages\ListPlayerPaymentMethods::route('/'),
            'create' => Pages\CreatePlayerPaymentMethod::route('/create'),
            'edit' => Pages\EditPlayerPaymentMethod::route('/{record}/edit'),
        ];
    }
}