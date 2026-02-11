<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InitMainDatabase extends Command
{
    protected $signature = 'main:init';
    protected $description = 'Initialize the main centralized database';

    public function handle()
    {
        $this->info('Initializing main database...');

        $dbPath = database_path('main.sqlite');

        // Create database file if it doesn't exist
        if (!file_exists($dbPath)) {
            touch($dbPath);
            chmod($dbPath, 0666);
            $this->info('Created main.sqlite database file');
        } else {
            $this->warn('main.sqlite already exists');
        }

        // Run migrations
        $this->info('Running migrations on main database...');
        Artisan::call('migrate', [
            '--database' => 'main',
            '--path' => 'database/migrations/main',
            '--force' => true,
        ]);

        $this->info('Main database initialized successfully!');

        return 0;
    }
}
