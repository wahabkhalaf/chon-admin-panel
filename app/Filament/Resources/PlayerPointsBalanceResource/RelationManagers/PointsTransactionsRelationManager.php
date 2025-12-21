<?php

namespace App\Filament\Resources\PlayerPointsBalanceResource\RelationManagers;

use App\Models\PointsTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PointsTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'pointsTransactions';

    protected static ?string $title = 'Transaction History';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PointsTransaction::TYPE_PURCHASE => 'success',
                        PointsTransaction::TYPE_SPEND => 'danger',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'info',
                        PointsTransaction::TYPE_REFUND => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PointsTransaction::TYPE_PURCHASE => 'Purchase',
                        PointsTransaction::TYPE_SPEND => 'Spend',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'Admin Credit',
                        PointsTransaction::TYPE_REFUND => 'Refund',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable()
                    ->color(fn (PointsTransaction $record): string => 
                        $record->isCredit() ? 'success' : 'danger'
                    )
                    ->formatStateUsing(fn (PointsTransaction $record): string => 
                        ($record->isCredit() ? '+' : '-') . number_format($record->amount)
                    ),
                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Before')
                    ->numeric(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('After')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Ref Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        PointsTransaction::REF_COMPETITION => 'Competition',
                        PointsTransaction::REF_PACKAGE_PURCHASE => 'Package',
                        PointsTransaction::REF_ADMIN_ACTION => 'Admin',
                        default => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PointsTransaction::TYPE_PURCHASE => 'Purchase',
                        PointsTransaction::TYPE_SPEND => 'Spend',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'Admin Credit',
                        PointsTransaction::TYPE_REFUND => 'Refund',
                    ]),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
