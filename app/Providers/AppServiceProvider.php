<?php

namespace App\Providers;

use App\Models\Competition;
use App\Observers\CompetitionObserver;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Competition::observe(CompetitionObserver::class);

        $displayTimezone = config('app.display_timezone');

        DateTimePicker::configureUsing(
            fn (DateTimePicker $component) => $component->timezone($displayTimezone),
        );

        TextColumn::configureUsing(
            fn (TextColumn $column) => $column->timezone($displayTimezone),
        );

        TextEntry::configureUsing(
            fn (TextEntry $entry) => $entry->timezone($displayTimezone),
        );
    }
}
