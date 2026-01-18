<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use App\Services\FcmNotificationService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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

                        Forms\Components\TextInput::make('title_arabic')
                            ->label('Notification Title (Arabic)')
                            ->maxLength(100)
                            ->placeholder('عنوان الإشعار (عربي)')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\TextInput::make('title_kurmanji')
                            ->label('Notification Title (Kurmanji)')
                            ->maxLength(100)
                            ->placeholder('Navê ragihandinê (Kurmanjî)')
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

                        Forms\Components\Textarea::make('message_arabic')
                            ->label('Notification Message (Arabic)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('رسالة الإشعار (عربي)')
                            ->disabled(fn($record) => $record && $record->status === 'sent'),

                        Forms\Components\Textarea::make('message_kurmanji')
                            ->label('Notification Message (Kurmanji)')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Peyama ragihandinê (Kurmanjî)')
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
                                ->action(function (array $data, Get $get) {
                                    // Pull live form values so we send what the user entered
                                    $payload = [
                                        'title' => $get('title'),
                                        'title_kurdish' => $get('title_kurdish'),
                                        'title_arabic' => $get('title_arabic'),
                                        'title_kurmanji' => $get('title_kurmanji'),
                                        'message' => $get('message'),
                                        'message_kurdish' => $get('message_kurdish'),
                                        'message_arabic' => $get('message_arabic'),
                                        'message_kurmanji' => $get('message_kurmanji'),
                                        'type' => $get('type'),
                                        'priority' => $get('priority'),
                                        'data' => $get('data'),
                                        'user_ids' => $get('user_ids'),
                                    ];
                                    return self::sendTestNotification($payload);
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
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->headerActions([
                Tables\Actions\Action::make('toggle_auto_notifications')
                    ->label(fn() => self::isAutoNotificationsEnabled() ? 'Disable Auto Notifications' : 'Enable Auto Notifications')
                    ->icon(fn() => self::isAutoNotificationsEnabled() ? 'heroicon-o-bell-slash' : 'heroicon-o-bell')
                    ->color(fn() => self::isAutoNotificationsEnabled() ? 'danger' : 'success')
                    ->action(function () {
                        self::toggleAutoNotifications();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn() => self::isAutoNotificationsEnabled() ? 'Disable Auto Notifications' : 'Enable Auto Notifications')
                    ->modalDescription(fn() => self::isAutoNotificationsEnabled() 
                        ? 'This will disable automatic notifications for new competitions and scheduled reminders. You can still send manual notifications.'
                        : 'This will enable automatic notifications for new competitions and scheduled reminders.')
                    ->modalSubmitActionLabel(fn() => self::isAutoNotificationsEnabled() ? 'Disable' : 'Enable')
            ])
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
            $fcmService = app(FcmNotificationService::class);

            $notificationData = [
                'title' => $data['title'] ?? '',
                'title_kurdish' => $data['title_kurdish'] ?? null,
                'title_arabic' => $data['title_arabic'] ?? null,
                'title_kurmanji' => $data['title_kurmanji'] ?? null,
                'message' => $data['message'] ?? '',
                'message_kurdish' => $data['message_kurdish'] ?? null,
                'message_arabic' => $data['message_arabic'] ?? null,
                'message_kurmanji' => $data['message_kurmanji'] ?? null,
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

            $result = $fcmService->sendNotificationToUsers($notificationData, $userIds);

            if ($result['success']) {
                FilamentNotification::make()
                    ->title('Test notification sent successfully!')
                    ->success()
                    ->send();
            } else {
                $errorMessage = $result['error'] ?? 'Unknown error';
                // Ensure error message is a string
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                } elseif (is_object($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }
                
                FilamentNotification::make()
                    ->title('Failed to send test notification')
                    ->body((string) $errorMessage)
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
            $fcmService = app(FcmNotificationService::class);

            $notificationData = [
                'title' => $notification->title,
                'title_kurdish' => $notification->title_kurdish,
                'title_arabic' => $notification->title_arabic,
                'title_kurmanji' => $notification->title_kurmanji,
                'message' => $notification->message,
                'message_kurdish' => $notification->message_kurdish,
                'message_arabic' => $notification->message_arabic,
                'message_kurmanji' => $notification->message_kurmanji,
                'type' => $notification->type,
                'priority' => $notification->priority,
                'data' => $notification->data ?? [],
            ];

            $result = $fcmService->sendBroadcastNotification($notificationData);

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
                $errorMessage = $result['error'] ?? 'Unknown error';
                // Ensure error message is a string
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                } elseif (is_object($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }
                
                FilamentNotification::make()
                    ->title('Failed to resend notification')
                    ->body((string) $errorMessage)
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

    /**
     * Check if auto notifications are enabled
     */
    public static function isAutoNotificationsEnabled(): bool
    {
        // Check cache first, then fall back to config
        if (\Cache::has('auto_notifications_enabled')) {
            return \Cache::get('auto_notifications_enabled');
        }
        
        return config('app.auto_notifications', true);
    }

    /**
     * Toggle auto notifications setting
     */
    public static function toggleAutoNotifications(): void
    {
        $currentValue = self::isAutoNotificationsEnabled();
        $newValue = !$currentValue;
        
        try {
            // Store the setting in cache (never expires until manually changed)
            \Cache::forever('auto_notifications_enabled', $newValue);
            
            // Show success notification
            FilamentNotification::make()
                ->title($newValue ? 'Auto Notifications Enabled' : 'Auto Notifications Disabled')
                ->body($newValue 
                    ? 'Automatic notifications for competitions are now enabled.'
                    : 'Automatic notifications for competitions are now disabled. You can still send manual notifications.')
                ->success()
                ->send();
                
            Log::info('Auto notifications toggled', [
                'enabled' => $newValue,
                'previous' => $currentValue,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling auto notifications', [
                'error' => $e->getMessage()
            ]);
            
            FilamentNotification::make()
                ->title('Error')
                ->body('Failed to update auto notifications setting: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
