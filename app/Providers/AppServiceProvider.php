<?php

namespace App\Providers;

use App\Models\Competition;
use App\Observers\CompetitionObserver;
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
    }
}
