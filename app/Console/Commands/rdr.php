<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class rdr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-optimize
                            {--seed : Include database seeding}
                            {--no-optimize : Skip the optimize step}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe, migrate, seed (optional), and optimize the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Production safety check
        if (app()->environment('production')) {
            $this->alert('PRODUCTION ENVIRONMENT DETECTED!');
            if (!$this->confirm('This will DESTROY all database data. Continue?')) {
                return 0;
            }
        }

        // 1. Wipe the database
        $this->info('Wiping database...');
        $this->call('db:wipe');

        // 2. Run migrations
        $this->info('Running migrations...');
        $this->call('migrate');

        // 3. Seed if requested
        if ($this->option('seed')) {
            $this->info('Seeding database...');
            $this->call('db:seed');
        }

        // 4. Optimize unless skipped
        if (!$this->option('no-optimize')) {
            $this->info('Optimizing application...');
            $this->call('optimize');
        }

        $this->info('✅ Database reset and optimization complete!');

        return 0;
    }
}
