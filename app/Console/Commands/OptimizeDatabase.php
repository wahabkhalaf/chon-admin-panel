<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize
                            {--vacuum : Run VACUUM on PostgreSQL}
                            {--analyze : Update table statistics}
                            {--clear-cache : Clear query cache}
                            {--all : Run all optimizations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Database Optimization');
        $this->newLine();

        $runAll = $this->option('all');

        // Vacuum (PostgreSQL)
        if ($this->option('vacuum') || $runAll) {
            $this->vacuum();
        }

        // Analyze tables
        if ($this->option('analyze') || $runAll) {
            $this->analyzeTables();
        }

        // Clear cache
        if ($this->option('clear-cache') || $runAll) {
            $this->clearCache();
        }

        if (!$this->option('vacuum') && !$this->option('analyze') && !$this->option('clear-cache') && !$runAll) {
            $this->warn('No optimization options specified. Use --all to run all optimizations.');
            $this->line('Available options:');
            $this->line('  --vacuum       Run VACUUM on PostgreSQL');
            $this->line('  --analyze      Update table statistics');
            $this->line('  --clear-cache  Clear query cache');
            $this->line('  --all          Run all optimizations');
        }

        $this->newLine();
        $this->info('✅ Optimization complete!');

        return Command::SUCCESS;
    }

    /**
     * Run VACUUM on PostgreSQL tables
     */
    protected function vacuum(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->warn('VACUUM is only available for PostgreSQL');
            return;
        }

        $this->info('Running VACUUM ANALYZE...');

        $tables = [
            'players',
            'competitions',
            'competition_leaderboards',
            'competition_player_answers',
            'competition_registrations',
            'transactions',
            'questions',
            'notifications',
            'player_notifications',
        ];

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            try {
                DB::statement("VACUUM ANALYZE {$table}");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to vacuum table {$table}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('✓ VACUUM ANALYZE completed');
        $this->newLine();
    }

    /**
     * Analyze tables to update statistics
     */
    protected function analyzeTables(): void
    {
        $this->info('Analyzing tables...');

        $connection = config('database.default');

        if ($connection === 'pgsql') {
            try {
                DB::statement('ANALYZE');
                $this->info('✓ Table statistics updated');
            } catch (\Exception $e) {
                $this->error('Failed to analyze tables: ' . $e->getMessage());
            }
        } elseif ($connection === 'mysql') {
            $tables = [
                'players',
                'competitions',
                'competition_leaderboards',
                'transactions',
                'questions',
            ];

            foreach ($tables as $table) {
                try {
                    DB::statement("ANALYZE TABLE {$table}");
                } catch (\Exception $e) {
                    $this->error("Failed to analyze table {$table}: " . $e->getMessage());
                }
            }
            $this->info('✓ Table statistics updated');
        }

        $this->newLine();
    }

    /**
     * Clear query cache
     */
    protected function clearCache(): void
    {
        $this->info('Clearing query cache...');

        try {
            Cache::flush();
            $this->info('✓ Query cache cleared');
        } catch (\Exception $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());
        }

        $this->newLine();
    }
}
