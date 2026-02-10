<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate {--fresh : Drop all tables and re-run migrations} {--seed : Seed the database after migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for all tenant databases';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get all accounts from system database
        Config::set('database.connections.system.database', database_path('system.sqlite'));
        DB::purge('system');

        $accounts = DB::connection('system')->table('accounts')->get();

        if ($accounts->isEmpty()) {
            $this->error('No tenant accounts found in system database');
            return 1;
        }

        $this->info('Found ' . $accounts->count() . ' tenant database(s)');
        $this->newLine();

        foreach ($accounts as $account) {
            $dbPath = database_path($account->database_file);

            if (!file_exists($dbPath)) {
                $this->warn("Database file not found for account {$account->id}: {$dbPath}");
                continue;
            }

            $this->info("Migrating database for account: {$account->username} ({$account->database_file})");

            // Configure tenant connection
            Config::set('database.connections.tenant.database', $dbPath);
            DB::purge('tenant');

            // Run migrations
            try {
                if ($this->option('fresh')) {
                    Artisan::call('migrate:fresh', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                } else {
                    Artisan::call('migrate', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                }

                $this->line(Artisan::output());

                // Seed if requested
                if ($this->option('seed')) {
                    $this->info("Seeding database for account: {$account->username}");
                    Artisan::call('db:seed', [
                        '--database' => 'tenant',
                        '--force' => true,
                    ]);
                    $this->line(Artisan::output());
                }

                $this->info("✓ Successfully migrated database for account: {$account->username}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to migrate database for account: {$account->username}");
                $this->error($e->getMessage());
            }

            $this->newLine();
        }

        $this->info('All tenant databases have been migrated!');
        return 0;
    }
}
