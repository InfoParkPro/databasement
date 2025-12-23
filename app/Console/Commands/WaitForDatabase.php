<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WaitForDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:wait {--allow-missing-db : Return success if connection works but database is missing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wait for the database connection to be established';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Waiting for database connection...');

        $maxRetries = 60;
        $retryDelay = 1; // seconds

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                DB::connection()->getPdo();
                $this->info('Database connection established!');

                return 0;
            } catch (\Exception $e) {
                if ($this->option('allow-missing-db')) {
                    // if driver is sqlite, then the database does not exist yet
                    if (DB::connection()->getDriverName() === 'sqlite' && str_contains($e->getMessage(), 'Database file at path')) {
                        $this->info('Sqlite database file not found. (Database not created yet).');

                        return 0;
                    }
                    if (str_contains($e->getMessage(), 'Unknown database')) {
                        $this->info('Database connection established! (Database not created yet).');

                        return 0;
                    }
                }

                $this->warn("Database not ready yet. Retrying in {$retryDelay} seconds... ({$i}/{$maxRetries})");
                $this->error($e->getMessage()); // Print the actual error message

                // Force a fresh connection attempt on the next iteration
                try {
                    DB::purge();
                } catch (\Exception $purgeException) {
                    // Ignore purge errors
                }

                sleep($retryDelay);
            }
        }

        $this->error('Could not connect to the database after multiple attempts.');

        return 1;
    }
}
