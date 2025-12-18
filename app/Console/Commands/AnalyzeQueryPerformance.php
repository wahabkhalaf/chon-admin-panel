<?php

namespace App\Console\Commands;

use App\Services\QueryOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeQueryPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:analyze
                            {--table= : Analyze specific table}
                            {--suggest-indexes : Suggest missing indexes}
                            {--table-stats : Show table statistics}
                            {--index-usage : Show index usage statistics}
                            {--connections : Show connection pool statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze query performance and provide optimization recommendations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Query Performance Analysis');
        $this->newLine();

        // Connection statistics
        if ($this->option('connections')) {
            $this->showConnectionStats();
        }

        // Table-specific analysis
        if ($table = $this->option('table')) {
            $this->analyzeTable($table);
        }

        // Suggest missing indexes
        if ($this->option('suggest-indexes')) {
            $this->suggestIndexes();
        }

        // Show table statistics
        if ($this->option('table-stats')) {
            $this->showTableStats();
        }

        // Show index usage
        if ($this->option('index-usage') && $table = $this->option('table')) {
            $this->showIndexUsage($table);
        }

        // If no options provided, show general recommendations
        if (!$this->option('table') && !$this->option('suggest-indexes') && 
            !$this->option('table-stats') && !$this->option('connections')) {
            $this->showGeneralRecommendations();
        }

        return Command::SUCCESS;
    }

    /**
     * Show connection pool statistics
     */
    protected function showConnectionStats(): void
    {
        $this->info('📊 Database Connection Statistics:');
        
        $stats = QueryOptimizationService::getConnectionStats();
        
        if (!empty($stats)) {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Connections', $stats['total_connections'] ?? 'N/A'],
                    ['Active Connections', $stats['active_connections'] ?? 'N/A'],
                    ['Idle Connections', $stats['idle_connections'] ?? 'N/A'],
                ]
            );
        } else {
            $this->warn('No connection statistics available');
        }
        
        $this->newLine();
    }

    /**
     * Analyze specific table
     */
    protected function analyzeTable(string $table): void
    {
        $this->info("📋 Analyzing table: {$table}");
        
        $stats = QueryOptimizationService::getTableStats($table);
        
        if (!empty($stats)) {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Table Name', $stats['tablename'] ?? 'N/A'],
                    ['Row Count', number_format($stats['row_count'] ?? 0)],
                    ['Dead Rows', number_format($stats['dead_rows'] ?? 0)],
                    ['Last Vacuum', $stats['last_vacuum'] ?? 'Never'],
                    ['Last Auto Vacuum', $stats['last_autovacuum'] ?? 'Never'],
                    ['Last Analyze', $stats['last_analyze'] ?? 'Never'],
                ]
            );
            
            // Recommendations
            if (isset($stats['dead_rows']) && $stats['dead_rows'] > 1000) {
                $this->warn("⚠️  High number of dead rows detected. Consider running VACUUM ANALYZE.");
            }
        } else {
            $this->error("Table '{$table}' not found or no statistics available");
        }
        
        $this->newLine();
    }

    /**
     * Suggest missing indexes
     */
    protected function suggestIndexes(): void
    {
        $this->info('💡 Index Suggestions (Tables with high sequential scans):');
        
        $suggestions = QueryOptimizationService::suggestIndexes();
        
        if (!empty($suggestions)) {
            $data = [];
            foreach ($suggestions as $suggestion) {
                $data[] = [
                    'table' => $suggestion->tablename,
                    'seq_scans' => number_format($suggestion->seq_scan),
                    'rows_read' => number_format($suggestion->seq_tup_read),
                    'avg_rows_per_scan' => number_format($suggestion->avg_seq_read, 0),
                ];
            }
            
            $this->table(
                ['Table', 'Sequential Scans', 'Rows Read', 'Avg Rows/Scan'],
                $data
            );
            
            $this->warn('⚠️  Tables above may benefit from additional indexes');
        } else {
            $this->info('✅ No obvious missing indexes detected');
        }
        
        $this->newLine();
    }

    /**
     * Show table statistics for all major tables
     */
    protected function showTableStats(): void
    {
        $this->info('📊 Table Statistics:');
        
        $tables = [
            'players',
            'competitions',
            'competition_leaderboards',
            'competition_player_answers',
            'transactions',
            'questions',
            'notifications',
        ];
        
        $data = [];
        foreach ($tables as $table) {
            $stats = QueryOptimizationService::getTableStats($table);
            if (!empty($stats)) {
                $data[] = [
                    'table' => $table,
                    'rows' => number_format($stats['row_count'] ?? 0),
                    'dead_rows' => number_format($stats['dead_rows'] ?? 0),
                ];
            }
        }
        
        if (!empty($data)) {
            $this->table(['Table', 'Live Rows', 'Dead Rows'], $data);
        }
        
        $this->newLine();
    }

    /**
     * Show index usage for a specific table
     */
    protected function showIndexUsage(string $table): void
    {
        $this->info("📇 Index Usage for table: {$table}");
        
        $indexes = QueryOptimizationService::getIndexStats($table);
        
        if (!empty($indexes)) {
            $data = [];
            foreach ($indexes as $index) {
                $data[] = [
                    'index' => $index->indexname,
                    'scans' => number_format($index->idx_scan),
                    'tuples_read' => number_format($index->idx_tup_read),
                    'tuples_fetched' => number_format($index->idx_tup_fetch),
                ];
            }
            
            $this->table(
                ['Index Name', 'Scans', 'Tuples Read', 'Tuples Fetched'],
                $data
            );
            
            // Find unused indexes
            $unusedIndexes = array_filter($indexes, fn($idx) => $idx->idx_scan == 0);
            if (!empty($unusedIndexes)) {
                $this->warn('⚠️  Unused indexes detected (consider removing):');
                foreach ($unusedIndexes as $idx) {
                    $this->line("   - {$idx->indexname}");
                }
            }
        } else {
            $this->warn('No index statistics available');
        }
        
        $this->newLine();
    }

    /**
     * Show general optimization recommendations
     */
    protected function showGeneralRecommendations(): void
    {
        $this->info('💡 General Optimization Recommendations:');
        $this->newLine();
        
        $this->line('1. Use eager loading to prevent N+1 queries:');
        $this->line('   Model::with([\'relation1\', \'relation2\'])->get()');
        $this->newLine();
        
        $this->line('2. Select only needed columns:');
        $this->line('   Model::select([\'id\', \'name\'])->get()');
        $this->newLine();
        
        $this->line('3. Use query scopes for common filters:');
        $this->line('   Model::active()->recent()->get()');
        $this->newLine();
        
        $this->line('4. Cache frequently accessed data:');
        $this->line('   Cache::remember(\'key\', 3600, fn() => Model::all())');
        $this->newLine();
        
        $this->line('5. Use chunk() for large datasets:');
        $this->line('   Model::chunk(1000, fn($records) => /* process */)');
        $this->newLine();
        
        $this->info('Run with options for detailed analysis:');
        $this->line('  --table=<name>        Analyze specific table');
        $this->line('  --suggest-indexes     Suggest missing indexes');
        $this->line('  --table-stats         Show all table statistics');
        $this->line('  --index-usage         Show index usage (requires --table)');
        $this->line('  --connections         Show connection pool stats');
    }
}
