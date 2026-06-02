<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class FlushRedisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clear 
                            {--db= : Specify a distinct Redis database number to flush (e.g., --db=1)} 
                            {--force : Force the operation to run without confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely flush the application Redis database(s)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Production environment safety check
        if (app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('WARNING: You are in PRODUCTION! Are you sure you want to flush Redis?')) {
                $this->comment('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $db = $this->option('db');

        try {
            if ($db !== null) {
                // Flush only a specific database number
                $this->info("Connecting to Redis database index: {$db}...");

                $redis = Redis::connection('default');
                $redis->select($db);
                $redis->flushdb();

                $this->info("Successfully flushed Redis database [{$db}].");
            } else {
                // Default: Flush the primary connection mapped to the Laravel app
                $this->info('Flushing the default Laravel Redis connection...');

                Redis::connection('default')->flushdb();

                $this->info('Successfully flushed default Redis database.');
            }
        } catch (\Exception $e) {
            $this->error('Redis Flush Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
