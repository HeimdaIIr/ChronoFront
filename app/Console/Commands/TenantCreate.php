<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class TenantCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create {username : The username of the tenant account} {email : The email for the tenant account} {--password= : The password for the tenant account} {--seed : Seed the database after creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant account with database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = $this->argument('username');
        $email = $this->argument('email');
        $password = $this->option('password') ?? 'password'; // Default password

        // Configure system connection
        Config::set('database.connections.system.database', database_path('system.sqlite'));
        DB::purge('system');

        // Check if username already exists
        $existingAccount = DB::connection('system')->table('accounts')->where('username', $username)->first();
        if ($existingAccount) {
            $this->error("An account with username {$username} already exists!");
            return 1;
        }

        // Check if email already exists
        if ($email) {
            $existingEmail = DB::connection('system')->table('accounts')->where('email', $email)->first();
            if ($existingEmail) {
                $this->error("An account with email {$email} already exists!");
                return 1;
            }
        }

        // Find next available account ID
        $maxId = DB::connection('system')->table('accounts')->max('id') ?? 0;
        $accountId = $maxId + 1;

        $databaseFile = "account_{$accountId}.sqlite";
        $dbPath = database_path($databaseFile);

        $this->info("Creating new tenant account...");
        $this->info("Username: {$username}");
        $this->info("Email: {$email}");
        $this->info("Database: {$databaseFile}");
        $this->newLine();

        // Create database file
        if (!file_exists($dbPath)) {
            touch($dbPath);
            chmod($dbPath, 0664);
            $this->info("✓ Database file created: {$databaseFile}");
        } else {
            $this->error("Database file already exists: {$databaseFile}");
            return 1;
        }

        // Create account record in system database
        try {
            DB::connection('system')->table('accounts')->insert([
                'username' => $username,
                'password' => bcrypt($password),
                'email' => $email,
                'database_file' => $databaseFile,
                'role' => 'chrono',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✓ Account created in system database");
        } catch (\Exception $e) {
            $this->error("Failed to create account in system database");
            $this->error($e->getMessage());
            // Clean up database file
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }
            return 1;
        }

        // Run migrations on new database
        $this->info("Running migrations on new database...");
        $this->newLine();

        Config::set('database.connections.tenant.database', $dbPath);
        DB::purge('tenant');

        try {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            $this->line(Artisan::output());
            $this->info("✓ Migrations completed successfully");

            // Seed if requested
            if ($this->option('seed')) {
                $this->info("Seeding database...");
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
                $this->line(Artisan::output());
                $this->info("✓ Database seeded successfully");
            }
        } catch (\Exception $e) {
            $this->error("Failed to migrate database");
            $this->error($e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info("🎉 Tenant account created successfully!");
        $this->info("Account ID: {$accountId}");
        $this->info("Database: {$databaseFile}");

        return 0;
    }
}
