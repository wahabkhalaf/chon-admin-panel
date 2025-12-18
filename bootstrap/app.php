<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'cors' => \App\Http\Middleware\Cors::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'query.monitor' => \App\Http\Middleware\QueryMonitoring::class,
        ]);
        
        // Add query monitoring to web and api groups in development
        // Note: Using env() here as config() is not available yet during bootstrap
        if (env('APP_DEBUG', false) || env('QUERY_MONITORING_ENABLED', false)) {
            $middleware->web(append: [
                \App\Http\Middleware\QueryMonitoring::class,
            ]);
            $middleware->api(append: [
                \App\Http\Middleware\QueryMonitoring::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Process scheduled notifications every minute
        $schedule->command('notifications:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->create();
