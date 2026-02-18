<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force PHP timezone to match Laravel config
        // This ensures PHP date functions use the same timezone as Carbon/Laravel
        date_default_timezone_set(config('app.timezone'));

        // Enable WAL mode for SQLite to allow concurrent reads during writes
        // This prevents the RFID reader writes from blocking frontend read queries
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA journal_mode=WAL');
            DB::statement('PRAGMA busy_timeout=5000');
            DB::statement('PRAGMA synchronous=NORMAL');
        }
    }
}
