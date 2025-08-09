<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use App\Services\ExpressApiClient;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Communications';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Notification Title')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Enter notification title')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\TextInput::make('title_kurdish')
                            ->label('Notification Title (Kurdish)')
                            ->maxLength(100)
                            ->placeholder('Navê ragihandinê (Kurdî)')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Textarea::make('message')
                            ->label('Notification Message')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Enter notification message')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Textarea::make('message_kurdish')
                            ->label('Notification Message (Kurdish)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Peyama ragihandinê (Kurdî)')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Select::make('type')
                            ->label('Notification Type')
                            ->options([
                                'general' => 'General',
                                'competition' => 'Competition',
                                'announcement' => 'Announcement',
                                'maintenance' => 'Maintenance',
                                'update' => 'Update',
                                'personal' => 'Personal',
                            ])
                            ->default('general')
                            ->required()
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Select::make('priority')
                            ->label('Priority Level')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                            ])
                            ->default('normal')
                            ->required()
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Textarea::make('data')
                            ->label('Additional Data (JSON)')
                            ->placeholder('{"key": "value"}')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT);
                                }
                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                                }
                                return $state;
                            })
                            ->visible(fn($record) => !$record || $record->status === 'pending'),

                        Forms\Components\Textarea::make('api_response')
                            ->label('API Response (JSON)')
                            ->placeholder('API response will appear here after sending')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT);
                                }
                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                                }
                                return $state;
                            })
                            ->visible(fn($record) => $record && in_array($record->status, ['sent', 'failed']))
                            ->disabled(),

                        Forms\Components\TextInput::make('user_ids')
                            ->label('Target User IDs (optional)')
                            ->placeholder('e.g. 1,2,3')
                            ->helperText('Comma-separated player IDs. Leave empty to send to all users (if supported by API).')
                            ->disabled(fn($record) => $record && $record->status === 'sent')
                            ->visible(fn($get) => (bool) $get('send_immediately')),

                        Forms\Components\Toggle::make('send_immediately')
                            ->label('Send Immediately')
                            ->default(true)
                            ->reactive()
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->visible(fn($get) => !$get('send_immediately'))
                            ->after('now')
                            ->required(fn($get) => !$get('send_immediately'))
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Actions::make([
                            Action::make('send_test')
                                ->label('Send Test Notification')
                                ->icon('heroicon-o-paper-airplane')
                                ->color('warning')
                                ->action(function (array $data) {
                                    return self::sendTestNotification($data);
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Send Test Notification')
                                ->modalDescription('This will send a test notification to the specified users (or all, if supported).')
                        ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'competition',
                        'warning' => 'announcement',
                        'danger' => 'maintenance',
                        'info' => 'update',
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'normal',
                        'danger' => 'high',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled For')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'general' => 'General',
                        'competition' => 'Competition',
                        'announcement' => 'Announcement',
                        'maintenance' => 'Maintenance',
                        'update' => 'Update',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Notification $record) {
                        return self::resendNotification($record);
                    })
                    ->visible(fn($record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Notification')
                    ->modalDescription('This will attempt to resend the failed notification.'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }

    public static function sendTestNotification(array $data): void
    {
        try {
            $apiClient = app(ExpressApiClient::class);

            $notificationData = [
                'title' => $data['title'] ?? 'Test Notification',
                'title_kurdish' => $data['title_kurdish'] ?? null,
                'message' => $data['message'] ?? 'This is a test notification from the admin panel.',
                'message_kurdish' => $data['message_kurdish'] ?? null,
                'type' => $data['type'] ?? 'general',
                'priority' => $data['priority'] ?? 'normal',
                'data' => $data['data'] ?? [],
            ];

            // Collect optional target user IDs
            $userIds = [];
            if (!empty($data['user_ids'])) {
                $userIds = collect(explode(',', (string) $data['user_ids']))
                    ->map(fn($id) => trim($id))
                    ->filter()
                    ->values()
                    ->all();
            }

            $result = $apiClient->sendNotification($notificationData, $userIds);

            if ($result['success']) {
                FilamentNotification::make()
                    ->title('Test notification sent successfully!')
                    ->success()
                    ->send();
            } else {
                FilamentNotification::make()
                    ->title('Failed to send test notification')
                    ->body($result['error'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Log::error('Error sending test notification', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            FilamentNotification::make()
                ->title('Error sending test notification')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function resendNotification(Notification $notification): void
    {
        try {
            $apiClient = app(ExpressApiClient::class);

            $notificationData = [
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'priority' => $notification->priority,
                'data' => $notification->data ?? [],
            ];

            $result = $apiClient->sendNotification($notificationData);

            $notification->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'api_response' => $result,
                'sent_at' => $result['success'] ? now() : null,
            ]);

            if ($result['success']) {
                FilamentNotification::make()
                    ->title('Notification resent successfully!')
                    ->success()
                    ->send();
            } else {
                FilamentNotification::make()
                    ->title('Failed to resend notification')
                    ->body($result['error'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Log::error('Error resending notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            $notification->update([
                'status' => 'failed',
                'api_response' => ['error' => $e->getMessage()],
            ]);

            FilamentNotification::make()
                ->title('Error resending notification')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
