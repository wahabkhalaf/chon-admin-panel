<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('provider')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(50),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Transaction Support')
                    ->schema([
                        Forms\Components\Toggle::make('supports_deposit')
                            ->required()
                            ->default(true),
                        Forms\Components\Toggle::make('supports_withdrawal')
                            ->required()
                            ->default(false),
                        Forms\Components\TextInput::make('min_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(10.00),
                        Forms\Components\TextInput::make('max_amount')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Fee Structure')
                    ->schema([
                        Forms\Components\TextInput::make('fee_fixed')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00),
                        Forms\Components\TextInput::make('fee_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->default(0.00),
                        Forms\Components\TextInput::make('processing_time_hours')
                            ->numeric()
                            ->suffix('hours')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('config')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('supports_deposit')
                    ->boolean(),
                Tables\Columns\IconColumn::make('supports_withdrawal')
                    ->boolean(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('No limit'),
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
                Tables\Filters\SelectFilter::make('provider')
                    ->options(fn() => PaymentMethod::distinct()->pluck('provider', 'provider')->toArray()),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('supports_deposit'),
                Tables\Filters\TernaryFilter::make('supports_withdrawal'),
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}